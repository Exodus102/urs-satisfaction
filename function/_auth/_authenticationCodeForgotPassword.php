<?php
session_start();
require_once '../../function/_databaseConfig/_dbConfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['code'])) {
    $input_code = trim($_POST['code']);
    $user_id = $_SESSION['user_id_for_reset']; // Get the user ID from the session

    $stmt = $pdo->prepare("SELECT code, expires_at FROM two_factor_codes WHERE user_id = :user_id ORDER BY expires_at DESC LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($verification && $input_code === $verification['code'] && new DateTime() < new DateTime($verification['expires_at'])) {
        // Code is correct and valid. Authorize the user to reset their password.
        $_SESSION['authorized_to_reset'] = true;
        unset($_SESSION['password_reset_pending']);

        // Clean up the used code from the database
        // $clearStmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
        // $clearStmt->execute([$user_id]);

        header("Location: ../../pages/login/reset-password.php");
        exit();
    } else {
        $_SESSION['reset_error'] = "Invalid or expired verification code.";
        header("Location: ../../pages/login/forgot-password-authentication.php");
        exit();
    }
} else {
    header("Location: ../../pages/login/forgot-password-authentication.php");
    exit();
}
