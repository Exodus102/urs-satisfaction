<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../function/_databaseConfig/_dbConfig.php';
// --- Resend Mailer Configuration ---
require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoloader
require_once __DIR__ . '/../_config/resend_config.php';

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);

    try {
        $stmt = $pdo->prepare("SELECT user_id, first_name FROM credentials WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $user_data['user_id'];
            $user_first_name = $user_data['first_name'];

            // Generate and store the password reset code
            $resetCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Delete any existing codes for this user to prevent conflicts
            // $deleteStmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
            // $deleteStmt->execute([$user_id]);

            // Insert the new code for password reset
            $insertStmt = $pdo->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
            $insertStmt->execute([$user_id, $resetCode, $expiresAt]);

            // --- Send the email using Resend ---
            $resend = Resend::client(RESEND_API_KEY);

            $resend->emails->send([
                'from' => MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
                'to' => [$email],
                'subject' => 'Password Reset Code',
                'html' => "Hello {$user_first_name},<br><br>Your password reset code is: <b>{$resetCode}</b><br><br>This code is valid for 10 minutes.",
                'text' => "Hello {$user_first_name}, Your password reset code is: {$resetCode}. This code is valid for 10 minutes."
            ]);

            // Set a temporary session flag to authorize code verification
            $_SESSION['password_reset_pending'] = true;
            $_SESSION['user_id_for_reset'] = $user_id;
            $_SESSION['reset_email'] = $email;

            // Redirect to the page where the user enters the code
            header("Location: ../../pages/login/forgot-password-authentication.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Email not found.";
            header("Location: ../../index.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = "An error occurred: " . $e->getMessage();
        header("Location: ../../pages/login/password.php");
        exit();
    }
} else {
    $_SESSION['login_error'] = "Invalid request.";
    header("Location: ../../index.php");
    exit();
}
