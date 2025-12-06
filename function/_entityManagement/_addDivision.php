<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['division_name']) && !empty(trim($_POST['division_name']))) {
        $divisionName = trim($_POST['division_name']);

        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_division WHERE division_name = ?");
            $checkStmt->execute([$divisionName]);
            if ($checkStmt->fetchColumn() > 0) {
                $response['message'] = 'Division with this name already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbl_division (division_name) VALUES (?)");
                if ($stmt->execute([$divisionName])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Added new division: " . $divisionName);
                    $response['message'] = 'Division added successfully!';
                } else {
                    $response['message'] = 'Failed to add division.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Division name cannot be empty.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
