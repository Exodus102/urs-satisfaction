<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_campus']) || empty(trim($_SESSION['user_campus']))) {
        $response['message'] = 'User campus not found in session. Please log in again.';
    } elseif (isset($_POST['division_name'], $_POST['unit_name']) && !empty(trim($_POST['division_name'])) && !empty(trim($_POST['unit_name']))) {
        $campusName = trim($_SESSION['user_campus']);
        $divisionName = trim($_POST['division_name']);
        $unitName = trim($_POST['unit_name']);

        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_unit WHERE LOWER(campus_name) = LOWER(?) AND LOWER(division_name) = LOWER(?) AND LOWER(unit_name) = LOWER(?)");
            $checkStmt->execute([$campusName, $divisionName, $unitName]);
            if ($checkStmt->fetchColumn() > 0) {
                $response['message'] = 'This unit already exists for the selected campus and division.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbl_unit (campus_name, division_name, unit_name) VALUES (?, ?, ?)");
                if ($stmt->execute([$campusName, $divisionName, $unitName])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Added new unit: " . $unitName . " under division: " . $divisionName . " at " . $campusName . " campus");
                    $response['message'] = 'Unit added successfully!';
                } else {
                    $response['message'] = 'Failed to add unit.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Division and Unit Name are required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
