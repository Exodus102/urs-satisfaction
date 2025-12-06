<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['campus_id'], $_POST['campus_name']) && !empty(trim($_POST['campus_name'])) && is_numeric($_POST['campus_id'])) {
        $campusId = $_POST['campus_id'];
        $campusName = trim($_POST['campus_name']);

        try {
            // First, get the old campus name for logging
            $oldNameStmt = $pdo->prepare("SELECT campus_name FROM tbl_campus WHERE id = ?");
            $oldNameStmt->execute([$campusId]);
            $oldCampus = $oldNameStmt->fetch();

            if (!$oldCampus) {
                $response['message'] = 'Campus not found.';
            } else {
                $oldCampusName = $oldCampus['campus_name'];

                // Check if the new name already exists for a DIFFERENT campus
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_campus WHERE campus_name = ? AND id != ?");
                $checkStmt->execute([$campusName, $campusId]);
                if ($checkStmt->fetchColumn() > 0) {
                    $response['message'] = 'Another campus with this name already exists.';
                } else {
                    // Update the campus name
                    $stmt = $pdo->prepare("UPDATE tbl_campus SET campus_name = ? WHERE id = ?");
                    if ($stmt->execute([$campusName, $campusId])) {
                        $response['success'] = true;
                        // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                        log_audit_trail($pdo, "Updated campus name from '$oldCampusName' to '$campusName'");
                        $response['message'] = 'Campus updated successfully!';
                    } else {
                        $response['message'] = 'Failed to update campus.';
                    }
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid input. Campus name and ID are required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
