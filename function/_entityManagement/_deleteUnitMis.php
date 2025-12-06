<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['unit_id']) && is_numeric($_POST['unit_id'])) {
        $unitId = $_POST['unit_id'];

        try {
            // First, get the name and division for a more descriptive log message
            $getNameStmt = $pdo->prepare("SELECT unit_name, division_name FROM tbl_unit_mis WHERE id = ?");
            $getNameStmt->execute([$unitId]);
            $unit = $getNameStmt->fetch();

            if ($unit) {
                $unitName = $unit['unit_name'];
                $divisionName = $unit['division_name'];

                // Now, proceed with deletion
                $deleteStmt = $pdo->prepare("DELETE FROM tbl_unit_mis WHERE id = ?");
                if ($deleteStmt->execute([$unitId])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Removed unit: " . $unitName . " from division: " . $divisionName);
                    $response['message'] = 'Unit deleted successfully!';
                } else {
                    $response['message'] = 'Failed to delete unit.';
                }
            } else {
                $response['message'] = 'Unit not found or already deleted.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid Unit ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
