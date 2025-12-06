<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['campus_name']) && !empty(trim($_POST['campus_name']))) {
        $campusName = trim($_POST['campus_name']);

        try {
            // Check if campus already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_campus WHERE campus_name = ?");
            $checkStmt->execute([$campusName]);
            if ($checkStmt->fetchColumn() > 0) {
                $response['message'] = 'Campus with this name already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbl_campus (campus_name) VALUES (?)");
                if ($stmt->execute([$campusName])) {
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Added new campus: " . $campusName);

                    $response['success'] = true;
                    $response['message'] = 'Campus added successfully!';
                } else {
                    $response['message'] = 'Failed to add campus.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Campus name cannot be empty.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
