<?php
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../_databaseConfig/_dbConfig.php';
require_once '../_auditTrail/_audit.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $firstName = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $password = trim($_POST['password']);

    if (empty($firstName) || empty($lastName)) {
        $response['message'] = 'First name and last name cannot be empty.';
        echo json_encode($response);
        exit;
    }

    try {
        $params = [
            $firstName,
            $middleName,
            $lastName,
            $contactNumber
        ];

        $sql = "UPDATE credentials SET first_name = ?, middle_name = ?, last_name = ?, contact_number = ?";

        if (!empty($password)) {
            $sql .= ", password = ?";
            $params[] = $password;
        }

        $sql .= " WHERE user_id = ?";
        $params[] = $userId;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['user_first_name'] = $firstName;
            $_SESSION['user_middle_name'] = $middleName;
            $_SESSION['user_last_name'] = $lastName;
            $_SESSION['user_contact_number'] = $contactNumber;
            if (!empty($password)) {
                $_SESSION['user_password'] = $password;
            }

            log_audit_trail($pdo, "Updated own profile information.");
            $response = ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            $response['message'] = 'Failed to update profile.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request or not logged in.';
}

echo json_encode($response);
