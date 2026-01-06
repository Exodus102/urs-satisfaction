<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php'; // Include the audit trail function
$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation
    if (
        isset($_POST['user_id'], $_POST['first_name'], $_POST['last_name'], $_POST['campus'], $_POST['unit'], $_POST['type'], $_POST['email'], $_POST['status']) &&
        is_numeric($_POST['user_id']) &&
        !empty(trim($_POST['first_name'])) &&
        !empty(trim($_POST['last_name'])) &&
        !empty(trim($_POST['campus'])) &&
        !empty(trim($_POST['unit'])) &&
        !empty(trim($_POST['type'])) &&
        !empty(trim($_POST['email'])) &&
        !empty(trim($_POST['status']))
    ) {
        $userId = $_POST['user_id'];
        $firstName = trim($_POST['first_name']);
        $middleName = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
        $lastName = trim($_POST['last_name']);
        $contactNumber = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
        $campus = trim($_POST['campus']);
        $unit = trim($_POST['unit']);
        $type = trim($_POST['type']);
        $email = trim($_POST['email']);
        $status = trim($_POST['status']);
        $password = trim($_POST['password']);

        try {
            // Check if email already exists for another user
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM credentials WHERE email = ? AND user_id != ?");
            $checkStmt->execute([$email, $userId]);
            if ($checkStmt->fetchColumn() > 0) {
                $response['message'] = 'This email is already in use by another account.';
            } else {
                // Build the query
                $sql = "UPDATE credentials SET first_name = ?, middle_name = ?, last_name = ?, contact_number = ?, campus = ?, unit = ?, type = ?, email = ?, status = ?";
                $params = [$firstName, $middleName, $lastName, $contactNumber, $campus, $unit, $type, $email, $status];

                // Only update password if a new one is provided
                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params[] = $password;
                }

                $sql .= " WHERE user_id = ?";
                $params[] = $userId;

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Updated user account: " . trim("$firstName $lastName") . " ($email)");

                    $response['success'] = true;
                    $response['message'] = 'Account updated successfully!';
                } else {
                    $response['message'] = 'Failed to update account.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Please fill out all required fields.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
