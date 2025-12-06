<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['customer_type_id'], $_POST['customer_type']) && !empty(trim($_POST['customer_type'])) && is_numeric($_POST['customer_type_id'])) {
        $customerTypeId = $_POST['customer_type_id'];
        $customerType = trim($_POST['customer_type']);

        try {
            // First, get the old customer type name for logging
            $oldNameStmt = $pdo->prepare("SELECT customer_type FROM tbl_customer_type WHERE id = ?");
            $oldNameStmt->execute([$customerTypeId]);
            $oldCustomerType = $oldNameStmt->fetch();

            if (!$oldCustomerType) {
                $response['message'] = 'Customer type not found.';
            } else {
                $oldCustomerTypeName = $oldCustomerType['customer_type'];

                // Check if the new name already exists for a DIFFERENT customer type
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_customer_type WHERE customer_type = ? AND id != ?");
                $checkStmt->execute([$customerType, $customerTypeId]);
                if ($checkStmt->fetchColumn() > 0) {
                    $response['message'] = 'Another customer type with this name already exists.';
                } else {
                    // Update the customer type name
                    $stmt = $pdo->prepare("UPDATE tbl_customer_type SET customer_type = ? WHERE id = ?");
                    if ($stmt->execute([$customerType, $customerTypeId])) {
                        $response['success'] = true;
                        // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                        log_audit_trail($pdo, "Updated customer type from '$oldCustomerTypeName' to '$customerType'");
                        $response['message'] = 'Customer type updated successfully!';
                    } else {
                        $response['message'] = 'Failed to update customer type.';
                    }
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid input. Customer type name and ID are required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
