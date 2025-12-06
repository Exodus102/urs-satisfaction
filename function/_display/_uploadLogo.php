<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

require_once __DIR__ . '/../_auditTrail/_audit.php'; // Include the audit trail function

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_file'])) {
    $file = $_FILES['logo_file'];

    // --- File Validation ---
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error. Code: ' . $file['error'];
        echo json_encode($response);
        exit;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        echo json_encode($response);
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        $response['message'] = 'File is too large. Maximum size is 2MB.';
        echo json_encode($response);
        exit;
    }

    // --- File Processing ---
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'logo_' . time() . '.' . $file_extension;

    // The target directory relative to the project root.
    $upload_dir = __DIR__ . '/../../resources/img/';
    $upload_path = $upload_dir . $new_filename;

    // The path to store in the database, relative to the project root.
    $db_path = 'resources/img/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        try {
            $pdo->beginTransaction();

            // 1. Set all existing logos to inactive (status = 0)
            $stmt_deactivate = $pdo->prepare("UPDATE tbl_logo SET status = 0");
            $stmt_deactivate->execute();

            // 2. Insert the new logo with active status (status = 1)
            $stmt_insert = $pdo->prepare("INSERT INTO tbl_logo (logo_path, status) VALUES (?, 1)");
            $stmt_insert->execute([$db_path]);

            $pdo->commit();
            $response['success'] = true;
            // --- LOG THE ACTION TO THE AUDIT TRAIL ---
            log_audit_trail($pdo, "Uploaded a new system logo: " . $new_filename);

            $response['message'] = 'Logo uploaded successfully!';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Failed to move uploaded file. Check folder permissions.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
