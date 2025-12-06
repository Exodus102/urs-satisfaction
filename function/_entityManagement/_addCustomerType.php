<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['customer_type']) && !empty(trim($_POST['customer_type']))) {
        $customerType = trim($_POST['customer_type']);

        try {
            // Check if customer type already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_customer_type WHERE customer_type = ?");
            $checkStmt->execute([$customerType]);
            if ($checkStmt->fetchColumn() > 0) {
                $response['message'] = 'Customer type with this name already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO tbl_customer_type (customer_type) VALUES (?)");
                if ($stmt->execute([$customerType])) {
                    $response['success'] = true;
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Added new customer type: " . $customerType);
                    $response['message'] = 'Customer type added successfully!';
                } else {
                    $response['message'] = 'Failed to add customer type.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Customer type name cannot be empty.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
