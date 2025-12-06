<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has a campus, basic security check
if (!isset($_SESSION['user_campus'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$response_id = $_GET['response_id'] ?? null;

if (!$response_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Response ID is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, response_id, sentiment_details FROM tbl_detail WHERE response_id = ?");
    $stmt->execute([$response_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        echo json_encode(['success' => true, 'data' => $details]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No sentiment details found for this response.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error fetching sentiment details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred while fetching details.']);
}
