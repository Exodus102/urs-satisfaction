<?php
header('Content-Type: application/json');

// Use absolute paths for reliability
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    $survey_id = $data['survey_id'] ?? null;
    $change_log = $data['change_log'] ?? null; // Can be an empty string

    if (!empty($survey_id) && isset($change_log)) {
        try {
            // This query updates ONLY the change_log for the given survey ID
            $stmt = $pdo->prepare(
                "UPDATE tbl_questionaireform SET change_log = ? WHERE id = ?"
            );

            if ($stmt->execute([$change_log, $survey_id])) {
                $response['success'] = true;
                $response['message'] = 'Change log updated successfully!';
            } else {
                $response['message'] = 'Failed to update the change log.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Survey ID and a change log message are required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
