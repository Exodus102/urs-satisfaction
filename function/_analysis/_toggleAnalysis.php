<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['is_active']) || !in_array($data['is_active'], [0, 1], true)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid status provided.']);
    exit;
}

$new_status = (int)$data['is_active'];

try {
    // We assume there is a single row in tbl_active with id=1 to control this global setting.
    // The table should be created with:
    // CREATE TABLE tbl_active (id INT PRIMARY KEY, is_active TINYINT(1) NOT NULL DEFAULT 0);
    // And initialized with:
    // INSERT INTO tbl_active (id, is_active) VALUES (1, 0);
    $stmt = $pdo->prepare("UPDATE tbl_active SET status = ? WHERE id = 1");
    $stmt->execute([$new_status]);

    $message = $new_status === 1 ? 'Analysis has been enabled.' : 'Analysis has been disabled.';
    echo json_encode(['success' => true, 'message' => $message]);
} catch (PDOException $e) {
    error_log("Analysis toggle failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database update failed. Please check server logs.']);
}
