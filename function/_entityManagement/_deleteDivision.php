<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['division_id']) && is_numeric($_POST['division_id'])) {
        $divisionId = $_POST['division_id'];

        try {
            // First, get the name for a more descriptive log message
            $getNameStmt = $pdo->prepare("SELECT division_name FROM tbl_division WHERE id = ?");
            $getNameStmt->execute([$divisionId]);
            $division = $getNameStmt->fetch();

            if ($division) {
                $divisionName = $division['division_name'];

                // Now, proceed with deletion
                $deleteStmt = $pdo->prepare("DELETE FROM tbl_division WHERE id = ?");
                if ($deleteStmt->execute([$divisionId])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Removed division: " . $divisionName);
                    $response['message'] = 'Division deleted successfully!';
                } else {
                    $response['message'] = 'Failed to delete division.';
                }
            } else {
                $response['message'] = 'Division not found or already deleted.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid Division ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
