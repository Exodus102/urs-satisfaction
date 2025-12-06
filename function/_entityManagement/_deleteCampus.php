<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['campus_id']) && is_numeric($_POST['campus_id'])) {
        $campusId = $_POST['campus_id'];

        try {
            // First, get the name of the campus for a more descriptive log message
            $getNameStmt = $pdo->prepare("SELECT campus_name FROM tbl_campus WHERE id = ?");
            $getNameStmt->execute([$campusId]);
            $campus = $getNameStmt->fetch();

            if ($campus) {
                $campusName = $campus['campus_name'];

                // Now, proceed with deletion
                $deleteStmt = $pdo->prepare("DELETE FROM tbl_campus WHERE id = ?");
                if ($deleteStmt->execute([$campusId])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Removed campus: " . $campusName);
                    $response['message'] = 'Campus deleted successfully!';
                } else {
                    $response['message'] = 'Failed to delete campus.';
                }
            } else {
                $response['message'] = 'Campus not found or already deleted.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid Campus ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
