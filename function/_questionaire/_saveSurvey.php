<?php

// --- Robust Error Handling ---
// This will catch fatal errors (like a failed require) and parse errors.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // If an error occurred, check if headers have been sent before sending a JSON response.
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => "Server Error: " . $error['message'],
            'error_details' => $error,
        ]);
    }
});

// This file is expected to create a PDO connection object named $pdo
require_once '../_databaseConfig/_dbConfig.php';

header('Content-Type: application/json');

// Check if the database connection was successful and is a PDO instance
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $errorMessage = 'Database connection object not found or is not a PDO instance. Check _dbConfig.php.';
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

// Get the raw POST data from the JavaScript fetch
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
    exit;
}

$survey_name = $data['survey_name'] ?? '';
$survey_id = $data['survey_id'] ?? null; // Check for survey_id for updates
$questions = $data['questions'] ?? [];

if (empty($survey_name) || empty($questions)) {
    echo json_encode(['success' => false, 'message' => 'Survey name and questions cannot be empty.']);
    exit;
}

$pdo->beginTransaction();

try {
    if ($survey_id) {
        // --- INTELLIGENT UPDATE LOGIC ---

        // 1. Get original survey name and all existing question IDs for this survey
        $stmt_get_old_name = $pdo->prepare("SELECT question_survey FROM tbl_questionaireform WHERE id = ?");
        $stmt_get_old_name->execute([$survey_id]);
        $old_survey_name = $stmt_get_old_name->fetchColumn();

        if (!$old_survey_name) {
            throw new Exception("Survey with ID {$survey_id} not found for update.");
        }

        $stmt_get_ids = $pdo->prepare("SELECT question_id FROM tbl_questionaire WHERE question_survey = ?");
        $stmt_get_ids->execute([$old_survey_name]);
        $existing_db_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);

        // 2. Update the survey form entry itself
        $change_log_message = 'Updated survey questions and/or name.';
        $stmt_update_form = $pdo->prepare("UPDATE tbl_questionaireform SET question_survey = ?, change_log = ? WHERE id = ?");
        $stmt_update_form->execute([$survey_name, $change_log_message, $survey_id]);

        // 3. Process submitted questions: update existing ones and identify new ones.
        $submitted_ids = [];
        $stmt_update_question = $pdo->prepare(
            "UPDATE tbl_questionaire SET question_survey = ?, question = ?, question_type = ?, required = ?, header = ?, transaction_type = ?, question_rendering = ? WHERE question_id = ?"
        );
        $stmt_del_choices = $pdo->prepare("DELETE FROM tbl_choices WHERE question_id = ?");

        foreach ($questions as $q) {
            if (isset($q['question_id'])) {
                // This is an existing question, so UPDATE it.
                $current_question_id = $q['question_id'];
                $submitted_ids[] = $current_question_id;

                $stmt_update_question->execute([
                    $survey_name,
                    $q['question'],
                    $q['type'],
                    $q['required'] ?? 1,
                    $q['header'] ?? 0,
                    $q['transaction_type'] ?? '2',
                    $q['question_rendering'] ?? 'None',
                    $current_question_id
                ]);

                // Clear its old choices and re-insert the new ones.
                $stmt_del_choices->execute([$current_question_id]);
                if (!empty($q['choices'])) {
                    $stmt_choice = $pdo->prepare("INSERT INTO tbl_choices (question_id, choice_text) VALUES (?, ?)");
                    foreach ($q['choices'] as $choice_text) {
                        $stmt_choice->execute([$current_question_id, $choice_text]);
                    }
                }
            }
            // New questions will be handled in the re-insertion logic below.
        }

        // 4. Determine which questions were deleted and remove them.
        $ids_to_delete = array_diff($existing_db_ids, $submitted_ids);
        if (!empty($ids_to_delete)) {
            // Placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));

            // Important: Delete from tbl_choices first due to foreign key constraints.
            $stmt_del_choices_batch = $pdo->prepare("DELETE FROM tbl_choices WHERE question_id IN ($placeholders)");
            $stmt_del_choices_batch->execute($ids_to_delete);

            $stmt_del_questions_batch = $pdo->prepare("DELETE FROM tbl_questionaire WHERE question_id IN ($placeholders)");
            $stmt_del_questions_batch->execute($ids_to_delete);
        }

        $message = 'Survey updated successfully!';
    } else {
        // --- CREATE LOGIC ---
        // Check if the survey name already exists.
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tbl_questionaireform WHERE question_survey = ?");
        $stmt_check->execute([$survey_name]);
        if ($stmt_check->fetchColumn() > 0) {
            throw new Exception("A survey with the name '$survey_name' already exists.");
        }

        // The survey is new, so add it to the form table.
        $initial_change_log = 'Initial survey creation.';
        $stmt_form = $pdo->prepare(
            "INSERT INTO tbl_questionaireform (question_survey, change_log, date_approved, timestamp) VALUES (?, ?, NULL, NOW())"
        );
        $stmt_form->execute([$survey_name, $initial_change_log]);
        $message = 'Survey saved successfully!';
    }

    // --- INSERT NEW QUESTIONS AND CHOICES ---
    $stmt_question = $pdo->prepare(
        "INSERT INTO tbl_questionaire (question_survey, section, question, status, question_type, required, header, transaction_type, question_rendering) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt_choice = $pdo->prepare(
        "INSERT INTO tbl_choices (question_id, choice_text) VALUES (?, ?)"
    );

    foreach ($questions as $q) {
        // Skip existing questions on an update, as they were handled above.
        if (isset($q['question_id'])) {
            continue;
        }

        // This is a new question, so INSERT it.
        $question_text = $q['question'];
        $question_type = $q['type'];
        $choices = $q['choices'] ?? [];
        $required = $q['required'] ?? 1;
        $section = 'Section 2';
        $status = 0; // Default status
        $header = $q['header'] ?? 0; // Get header value, default to 0
        $transaction_type = $q['transaction_type'] ?? '2';
        $question_rendering = $q['question_rendering'] ?? 'None';

        $stmt_question->execute([
            $survey_name,
            $section,
            $question_text,
            $status,
            $question_type,
            $required,
            $header,
            $transaction_type,
            $question_rendering
        ]);

        $last_question_id = $pdo->lastInsertId();
        if (!$last_question_id) {
            throw new Exception('Failed to insert question.');
        }

        if (!empty($choices)) {
            foreach ($choices as $choice_text) {
                $stmt_choice->execute([$last_question_id, $choice_text]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    $pdo->rollBack();
    // Use http_response_code for a proper status
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}

$pdo = null; // Close PDO connection