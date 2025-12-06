<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    $file_path = $data['file_path'] ?? null;

    if ($file_path) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_approved (file_path) VALUES (?)");
            if ($stmt->execute([$file_path])) {
                $response['success'] = true;
                $response['message'] = 'Report approved successfully!';
            } else {
                $response['message'] = 'Failed to approve the report.';
            }
        } catch (PDOException $e) {
            // Handle potential duplicate entry error (code 23000)
            $response['message'] = ($e->getCode() == '23000') ? 'This report has already been approved.' : 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'File path is required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
