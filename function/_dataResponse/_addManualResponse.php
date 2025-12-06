<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$answers_data = $_POST['answers'] ?? [];
$user_campus = $_POST['user_campus'] ?? $_SESSION['user_campus'] ?? null;

if (empty($answers_data) || !$user_campus) {
    echo json_encode(['success' => false, 'message' => 'No response data or user campus provided.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get the highest existing response_id and increment it for the new set of responses.
    $stmt_max_id = $pdo->query("SELECT MAX(response_id) FROM tbl_responses");
    $max_id = $stmt_max_id->fetchColumn();
    $next_response_id = ($max_id) ? $max_id + 1 : 1;

    // Prepare the main insert statement.
    $sql = "INSERT INTO tbl_responses (question_id, response_id, response, comment, analysis, timestamp, uploaded) VALUES (?, ?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);

    $rows_processed = 0;

    foreach ($answers_data as $row_index => $row_answers) {
        // Extract special fields for this row.
        $timestamp = $row_answers['timestamp'] ?? date('Y-m-d H:i:s'); // Fallback to current time
        $comment = $row_answers['comment'] ?? '';
        $analysis = $row_answers['analysis'] ?? '';
        $division = $row_answers[-2] ?? '';
        $office = $row_answers[-3] ?? '';
        $customer_type = $row_answers[-4] ?? '';

        // Skip if essential metadata is missing for a row.
        if (empty($division) || empty($office) || empty($customer_type)) {
            continue;
        }

        $current_response_id = $next_response_id + $rows_processed;

        // --- Insert Metadata ---
        // -1: Campus
        $stmt->execute([-1, $current_response_id, $user_campus, $comment, $analysis, $timestamp]);
        // -2: Division
        $stmt->execute([-2, $current_response_id, $division, $comment, $analysis, $timestamp]);
        // -3: Office
        $stmt->execute([-3, $current_response_id, $office, $comment, $analysis, $timestamp]);
        // -4: Customer Type
        $stmt->execute([-4, $current_response_id, $customer_type, $comment, $analysis, $timestamp]);

        // --- Insert Question Answers ---
        foreach ($row_answers as $question_id => $response) {
            // Skip metadata and special fields we've already processed.
            if ($question_id < 1 || in_array($question_id, ['comment', 'analysis', 'timestamp'])) {
                continue;
            }

            // Only insert if there is a response.
            if (!empty($response)) {
                $stmt->execute([$question_id, $current_response_id, $response, $comment, $analysis, $timestamp]);
            }
        }

        $rows_processed++;
    }

    if ($rows_processed > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "$rows_processed response(s) added successfully!"]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No valid rows to add. Please ensure Division, Office, and Customer Type are filled for at least one row.']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Add Manual Response Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please check the server logs.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Add Manual Response Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
