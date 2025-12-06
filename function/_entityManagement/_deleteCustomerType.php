<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['customer_type_id']) && is_numeric($_POST['customer_type_id'])) {
        $customerTypeId = $_POST['customer_type_id'];

        try {
            // First, get the name for a more descriptive log message
            $getNameStmt = $pdo->prepare("SELECT customer_type FROM tbl_customer_type WHERE id = ?");
            $getNameStmt->execute([$customerTypeId]);
            $customer = $getNameStmt->fetch();

            if ($customer) {
                $customerTypeName = $customer['customer_type'];

                // Now, proceed with deletion
                $deleteStmt = $pdo->prepare("DELETE FROM tbl_customer_type WHERE id = ?");
                if ($deleteStmt->execute([$customerTypeId])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Removed customer type: " . $customerTypeName);
                    $response['message'] = 'Customer type deleted successfully!';
                } else {
                    $response['message'] = 'Failed to delete customer type.';
                }
            } else {
                $response['message'] = 'Customer type not found or already deleted.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid Customer Type ID.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
