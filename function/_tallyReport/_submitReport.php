<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$filePath = $data['file_path'] ?? null;

if (empty($filePath)) {
    http_response_code(400); // Bad Request
    $response['message'] = 'File path is missing.';
    echo json_encode($response);
    exit;
}

try {
    // 1. Check if the report has already been submitted
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_submitted WHERE file_path = ?");
    $checkStmt->execute([$filePath]);
    $count = $checkStmt->fetchColumn();
    
    if ($count > 0) {
        // If the report exists, send a specific message.
        // We'll consider this a "successful" check, but with a different message.
        $response['success'] = true;
        $response['message'] = 'This report has already been submitted.';
    } else {
        // 2. If not, insert the new record.
        $stmt = $pdo->prepare("INSERT INTO tbl_submitted (file_path) VALUES (?)");
        $stmt->execute([$filePath]);
        $response['success'] = true;
        $response['message'] = 'Report submitted successfully!';
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Failed to submit report: ' . $e->getMessage());
}

echo json_encode($response);
