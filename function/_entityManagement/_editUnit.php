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
    } elseif (isset($_POST['unit_id'], $_POST['division_name'], $_POST['unit_name']) && is_numeric($_POST['unit_id']) && !empty(trim($_POST['division_name'])) && !empty(trim($_POST['unit_name']))) {
        $unitId = $_POST['unit_id'];
        $campusName = trim($_SESSION['user_campus']);
        $divisionName = trim($_POST['division_name']);
        $unitName = trim($_POST['unit_name']);

        try {
            // First, get the old unit details for logging
            $oldUnitStmt = $pdo->prepare("SELECT unit_name, division_name FROM tbl_unit WHERE id = ?");
            $oldUnitStmt->execute([$unitId]);
            $oldUnit = $oldUnitStmt->fetch();

            if (!$oldUnit) {
                $response['message'] = 'Unit not found.';
            } else {
                $oldUnitName = $oldUnit['unit_name'];
                $oldDivisionName = $oldUnit['division_name'];

                // Check if the new combination already exists for a DIFFERENT unit
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_unit WHERE LOWER(campus_name) = LOWER(?) AND LOWER(division_name) = LOWER(?) AND LOWER(unit_name) = LOWER(?) AND id != ?");
                $checkStmt->execute([$campusName, $divisionName, $unitName, $unitId]);
                if ($checkStmt->fetchColumn() > 0) {
                    $response['message'] = 'Another unit with this name already exists for the selected campus and division.';
                } else {
                    // Update the unit
                    $stmt = $pdo->prepare("UPDATE tbl_unit SET campus_name = ?, division_name = ?, unit_name = ? WHERE id = ?");
                    if ($stmt->execute([$campusName, $divisionName, $unitName, $unitId])) {
                        $response['success'] = true;
                        // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                        $logMessage = "Updated unit from '$oldUnitName' (Division: $oldDivisionName) to '$unitName' (Division: $divisionName) at '$campusName' campus";
                        log_audit_trail($pdo, $logMessage);
                        $response['message'] = 'Unit updated successfully!';
                    } else {
                        $response['message'] = 'Failed to update unit.';
                    }
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid input. All fields are required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
