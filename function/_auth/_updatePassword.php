<?php
session_start();
require_once '../../function/_databaseConfig/_dbConfig.php';

if (!isset($_SESSION['authorized_to_reset']) || !$_SESSION['authorized_to_reset']) {
    $_SESSION['reset_error'] = "Authorization failed.";
    header("Location: ../../pages/login/password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id_for_reset'];

    if ($new_password !== $confirm_password) {
        $_SESSION['reset_error'] = "Passwords do not match.";
        header("Location: ../../pages/login/reset-password.php");
        exit();
    }

    // IMPORTANT: Use password_hash() to securely store the new password
    // $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // If not hashing (not recommended)
    $hashed_password = $new_password;

    $updateStmt = $pdo->prepare("UPDATE credentials SET password = ? WHERE user_id = ?");
    $updateStmt->execute([$hashed_password, $user_id]);

    // Clear session variables after a successful reset
    unset($_SESSION['authorized_to_reset']);
    unset($_SESSION['user_id_for_reset']);

    $_SESSION['login_success'] = "Your password has been reset successfully. You can now log in with your new password.";
    header("Location: ../../index.php");
    exit();
}
