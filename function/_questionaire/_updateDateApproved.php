<?php
header('Content-Type: application/json');

// Use absolute paths for reliability
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    $survey_id = $data['survey_id'] ?? null;
    $date_approved = $data['date_approved'] ?? null;

    if (!empty($survey_id) && isset($date_approved)) {
        try {
            // A null or empty date from the client will set the database field to NULL.
            $date_to_save = !empty($date_approved) ? $date_approved : null;

            $stmt = $pdo->prepare(
                "UPDATE tbl_questionaireform SET date_approved = ? WHERE id = ?"
            );

            if ($stmt->execute([$date_to_save, $survey_id])) {
                $response['success'] = true;
                $response['message'] = 'Approval date updated successfully!';
            } else {
                $response['message'] = 'Failed to update the approval date.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Survey ID and an approval date are required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
