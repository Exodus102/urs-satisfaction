<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload failed. Please choose a valid CSV file.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$file_info = pathinfo($_FILES['csv_file']['name']);

if (strtolower($file_info['extension']) !== 'csv') {
    $response['message'] = 'Invalid file type. Only .csv files are allowed.';
    echo json_encode($response);
    exit;
}

$handle = fopen($file, "r");
if ($handle === FALSE) {
    $response['message'] = 'Failed to open the uploaded file.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get the campus of the logged-in user from the session
    $user_campus = $_SESSION['user_campus'] ?? null;
    if (!$user_campus) {
        throw new Exception("User campus not found in session. Please log in again.");
    }

    // --- Step 1: Build a map of question names to question IDs from the database ---
    // Fetch Online questions based on user's criteria: transaction_type = 1, header = 1, and question_rendering is QoS or Su
    $stmtOnline = $pdo->prepare("SELECT question_id, header, transaction_type, question_rendering FROM tbl_questionaire WHERE transaction_type = 1 AND header = 1 AND question_rendering IN ('QoS', 'Su') AND status = 1 AND required = 1 ORDER BY question_id ASC");
    $stmtOnline->execute();
    $online_questions = $stmtOnline->fetchAll(PDO::FETCH_ASSOC);

    // Fetch QoS questions, excluding those that meet the 'Online' criteria
    $stmtQoS = $pdo->prepare("SELECT question_id, header, transaction_type, question_rendering FROM tbl_questionaire WHERE question_rendering = 'QoS' AND status = 1 AND required = 1 AND NOT (transaction_type = 1 AND header = 1) ORDER BY question_id ASC");
    $stmtQoS->execute();
    $qos_questions = $stmtQoS->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Su questions, excluding those that meet the 'Online' criteria
    $stmtSu = $pdo->prepare("SELECT question_id, header, transaction_type, question_rendering FROM tbl_questionaire WHERE question_rendering = 'Su' AND status = 1 AND required = 1 AND NOT (transaction_type = 1 AND header = 1) ORDER BY question_id ASC");
    $stmtSu->execute();
    $su_questions = $stmtSu->fetchAll(PDO::FETCH_ASSOC);

    // --- Step 1a: Fetch all other required questions that are NOT QoS or Su to insert blank responses for them. ---
    $stmtOtherQuestions = $pdo->prepare(
        "SELECT question_id, header, transaction_type, question_rendering FROM tbl_questionaire
         WHERE status = 1 AND required = 1 AND (question_rendering IS NULL OR question_rendering NOT IN ('QoS', 'Su')) AND NOT (transaction_type = 1 AND header = 1)
         ORDER BY question_id ASC"
    );
    $stmtOtherQuestions->execute();
    $other_questions = $stmtOtherQuestions->fetchAll(PDO::FETCH_ASSOC);

    // Map metadata headers to their special IDs
    $metadata_map = [
        'office' => -3,
        'respondents' => -4,
    ];
    // --- Pre-load a map of all units to their divisions for efficient lookup ---
    $unit_to_division_map = [];
    $stmtUnits = $pdo->query("SELECT unit_name, division_name FROM tbl_unit WHERE division_name IS NOT NULL AND division_name != ''");
    $all_units = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_units as $unit) {
        $unit_to_division_map[$unit['unit_name']] = $unit['division_name'];
    }

    // --- Step 2: Parse CSV headers to map column index to question/metadata ---
    $header_row1 = fgetcsv($handle, 0, ",");
    $header_row2 = fgetcsv($handle, 0, ",");

    if ($header_row1 === false || $header_row2 === false) {
        throw new Exception("CSV file is missing the required two header rows.");
    }

    $column_map = [];
    $qos_index = 0;
    $su_index = 0;
    $online_index = 0;
    $current_mode = null; // Can be 'QoS', 'Su', 'Online', or null
    $date_col_index = -1;

    foreach ($header_row1 as $index => $main_header) {
        $main_header_norm = trim(strtolower($main_header));
        $sub_header_norm = trim(strtolower($header_row2[$index]));

        // If the main header is not empty, update the current mode.
        if (!empty($main_header_norm)) {
            if ($main_header_norm === 'quality of services') {
                $current_mode = 'QoS';
            } elseif ($main_header_norm === 'service unit') {
                $current_mode = 'Su';
            } elseif ($main_header_norm === 'online') {
                $current_mode = 'Online';
            }
        }

        // Map special columns by their main header
        if ($main_header_norm === 'date') {
            $date_col_index = $index;
        }

        // Map metadata columns
        if (isset($metadata_map[$main_header_norm])) {
            $column_map[$index] = ['type' => 'metadata', 'id' => $metadata_map[$main_header_norm]];
        }

        // If we are in a question section and the sub-header is not empty, map it to the next available question ID.
        if ($current_mode === 'Online' && !empty($sub_header_norm) && isset($online_questions[$online_index])) {
            $column_map[$index] = ['type' => 'question', 'data' => $online_questions[$online_index]];
            $online_index++;
        } elseif ($current_mode === 'QoS' && !empty($sub_header_norm) && isset($qos_questions[$qos_index])) {
            $column_map[$index] = ['type' => 'question', 'data' => $qos_questions[$qos_index]];
            $qos_index++;
        } elseif ($current_mode === 'Su' && !empty($sub_header_norm) && isset($su_questions[$su_index])) {
            $column_map[$index] = ['type' => 'question', 'data' => $su_questions[$su_index]];
            $su_index++;
        }
    }

    // --- Step 3: Get the next available response_id and prepare for insertion ---
    $stmtMaxId = $pdo->query("SELECT MAX(response_id) as max_id FROM tbl_responses");
    $max_id = $stmtMaxId->fetchColumn();
    $next_response_id = ($max_id ?: 0) + 1;

    // Updated SQL statement to include all necessary columns
    $sql = "INSERT INTO tbl_responses (question_id, response_id, response, comment, analysis, timestamp, header, transaction_type, question_rendering, uploaded) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);

    $rowCount = 0;
    $inserted_responses = 0;
    $skipped_rows = [];
    $current_csv_row = 2; // We've already read two header rows

    // --- Step 4: Loop through the data rows in the CSV and process each response_id ---
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $current_csv_row++;

        // Basic validation: check if the row is mostly empty
        if (empty(array_filter($data))) {
            continue; // Skip empty rows
        }
        $db_response_id = $next_response_id;
        $responses_to_insert_for_this_id = []; // Collect all responses for this response_id
        $has_actual_csv_data = false; // Flag to check if the CSV row had any meaningful data

        // 0. Get timestamp from the 'Date' column
        $timestamp = date('Y-m-d H:i:s'); // Default to current timestamp if no date column or empty
        if ($date_col_index !== -1 && !empty($data[$date_col_index])) {
            $date_string = trim($data[$date_col_index]);
            $parsed_dateTime = null;

            // Try to parse as YYYY-MM-DD HH:MM:SS
            $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date_string);
            if ($dateTime && $dateTime->format('Y-m-d H:i:s') === $date_string) {
                $parsed_dateTime = $dateTime;
            } else {
                // If not, try to parse as YYYY-MM-DD HH:MM
                $dateTime = DateTime::createFromFormat('Y-m-d H:i', $date_string);
                if ($dateTime && $dateTime->format('Y-m-d H:i') === $date_string) {
                    $parsed_dateTime = $dateTime;
                } else {
                    // If not, try to parse as YYYY-MM-DD (and set time to 00:00:00)
                    $dateTime = DateTime::createFromFormat('Y-m-d', $date_string);
                    if ($dateTime && $dateTime->format('Y-m-d') === $date_string) {
                        $parsed_dateTime = $dateTime;
                    }
                }
            }

            if ($parsed_dateTime !== null) {
                $timestamp = $parsed_dateTime->format('Y-m-d H:i:s');
            } else {
                // If none of the strict formats matched
                $response['message'] = "Upload Error: Incorrect date format in CSV row {$current_csv_row}. Expected 'YYYY-MM-DD HH:MM:SS', 'YYYY-MM-DD HH:MM', or 'YYYY-MM-DD'. Found: '{$date_string}'.";
                echo json_encode($response);
                exit;
            }
        }
        // --- Order of insertion: Campus, Division, Other Questions (blanks), CSV Data (QoS, Su, Office, Respondents, Online) ---
        // 1. Prepare Campus metadata
        $responses_to_insert_for_this_id[] = [
            -1, // question_id for campus
            $db_response_id,
            $user_campus,
            $timestamp, // 3
            0,         // 4: header
            2,         // 5: transaction_type
            null       // 6: question_rendering
        ];

        // 2. Determine Office name from CSV to find its Division
        $office_name_from_csv = null;
        foreach ($data as $col_index => $value) {
            if (isset($column_map[$col_index]) && $column_map[$col_index]['type'] === 'metadata' && $column_map[$col_index]['id'] === -3) { // Check for office metadata
                $office_name_from_csv = trim($value);
                break;
            }
        }

        // 3. Prepare Division metadata (if office name found and has a division)
        if ($office_name_from_csv && isset($unit_to_division_map[$office_name_from_csv])) {
            $division_name = $unit_to_division_map[$office_name_from_csv];
            $responses_to_insert_for_this_id[] = [
                -2, // question_id for division
                $db_response_id,
                $division_name,
                $timestamp, // 3
                0,         // 4: header
                2,         // 5: transaction_type
                null       // 6: question_rendering
            ];
        }

        // 4. Prepare blank responses for all other required questions (not QoS/Su/Online)
        if (!empty($other_questions)) {
            foreach ($other_questions as $other_q) {
                $responses_to_insert_for_this_id[] = [
                    $other_q['question_id'],
                    $db_response_id,
                    ' ', // Insert a blank space as the response
                    $timestamp, // 3
                    $other_q['header'],           // 4
                    $other_q['transaction_type'], // 5
                    $other_q['question_rendering']  // 6
                ];
            }
        }

        // 5. Process each column in the current row for CSV-specific data (QoS, Su, Office, Respondents, Online)
        foreach ($data as $col_index => $value) {
            if (isset($column_map[$col_index])) {
                $map_info = $column_map[$col_index];
                $response_value = trim($value);

                if ($response_value === '') {
                    continue;
                }
                $has_actual_csv_data = true; // This row has at least one meaningful CSV data point

                $current_question_id = null;
                $current_header = 0;
                $current_transaction_type = 2;
                $current_question_rendering = null;

                if ($map_info['type'] === 'question') {
                    $question_data = $map_info['data'];
                    $current_question_id = $question_data['question_id'];
                    $current_header = $question_data['header'];
                    $current_transaction_type = $question_data['transaction_type'];
                    $current_question_rendering = $question_data['question_rendering'];
                } else { // type === 'metadata' (office or respondents)
                    $current_question_id = $map_info['id'];
                }

                // Add the CSV data to the collection
                $responses_to_insert_for_this_id[] = [
                    $current_question_id,
                    $db_response_id,
                    $response_value,
                    $timestamp, // 3
                    $current_header,           // 4
                    $current_transaction_type, // 5
                    $current_question_rendering  // 6
                ];
            }
        }

        // Execute all collected insertions for this response_id, but only if the CSV row had actual data
        if ($has_actual_csv_data) {
            // Get comment from the last column of the original data row
            $comment = !empty(end($data)) ? trim(end($data)) : '';

            foreach ($responses_to_insert_for_this_id as $params) {
                // Prepare final parameters for SQL: (question_id, response_id, response, comment, analysis, timestamp, header, transaction_type, question_rendering, uploaded)
                // The SQL has 9 placeholders, as 'uploaded' is hardcoded to 1.
                $final_params = [
                    $params[0], // question_id
                    $params[1], // response_id
                    $params[2], // response
                    $comment,   // comment
                    '',        // analysis
                    $params[3], // timestamp,
                    $params[4], // header
                    $params[5], // transaction_type
                    $params[6], // question_rendering
                ];
                $stmt->execute($final_params);
                $inserted_responses++;
            }
            $rowCount++;
            $next_response_id++;
        } else {
            $skipped_rows[] = $current_csv_row;
        }
    }

    $pdo->commit();

    // Prepare the final success message
    $response['success'] = true;
    $message = "Successfully processed and inserted {$rowCount} survey responses ({$inserted_responses} total answers).";
    if (!empty($skipped_rows)) {
        $message .= "\n\nWarning: Skipped " . count($skipped_rows) . " rows that appeared to be empty on lines: " . implode(', ', $skipped_rows) . ".";
    }

    // --- Add Debugging for Online Questions ---
    $online_questions_mapped = 0;
    foreach ($column_map as $map) {
        if ($map['type'] === 'question' && isset($map['data']['question_rendering']) && in_array($map['data']['question_rendering'], ['QoS', 'Su']) && $map['data']['transaction_type'] == 1 && $map['data']['header'] == 1) {
            $online_questions_mapped++;
        }
    }
    $message .= "\n\nDebug: Found and mapped {$online_questions_mapped} 'Online' question columns from the CSV header.";
    $response['message'] = $message;
} catch (PDOException $e) {
    $pdo->rollBack();
    $response['message'] = "Database error: " . $e->getMessage();
    error_log("CSV Upload Error: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = "An error occurred during processing: " . $e->getMessage();
} finally {
    fclose($handle);
}

echo json_encode($response);
