<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../_databaseConfig/_dbConfig.php';
require_once '../_auditTrail/_audit.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture']) && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $file = $_FILES['profile_picture'];

    // --- File Validation ---
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['profile_update_error'] = 'An error occurred during file upload. Please try again.';
        header('Location: ../../include/css-coordinators/css-coordinators-layout.php?page=profile');
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['profile_update_error'] = 'Invalid file type. Please upload a JPG, PNG, or GIF.';
        header('Location: ../../include/css-coordinators/css-coordinators-layout.php?page=profile');
        exit;
    }

    // --- File Processing ---
    $uploadDir = '../../upload/profile-picture/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'user_' . $userId . '_' . time() . '.' . $fileExtension;
    $destination = $uploadDir . $newFileName;
    $dbPath = 'upload/profile-picture/' . $newFileName;

    // --- Delete old picture if it exists ---
    if (!empty($_SESSION['user_dp']) && file_exists('../../' . $_SESSION['user_dp'])) {
        unlink('../../' . $_SESSION['user_dp']);
    }

    // --- Move the new file and update the database ---
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        try {
            $stmt = $pdo->prepare("UPDATE credentials SET dp = ? WHERE user_id = ?");
            if ($stmt->execute([$dbPath, $userId])) {
                // Update session to reflect the change immediately
                $_SESSION['user_dp'] = $dbPath;
                log_audit_trail($pdo, "Updated own profile picture.");
                $_SESSION['profile_update_success'] = 'Profile picture updated successfully!';
            } else {
                $_SESSION['profile_update_error'] = 'Failed to update database record.';
            }
        } catch (PDOException $e) {
            $_SESSION['profile_update_error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['profile_update_error'] = 'Failed to move uploaded file.';
    }
} else {
    $_SESSION['profile_update_error'] = 'Invalid request.';
}

// Redirect back to the profile page
header('Location: ../../include/css-coordinators/css-coordinators-layout.php?page=profile');
exit;
