<?php
// Enable detailed error reporting for debugging during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// --- Database Configuration ---
require_once '../../function/_databaseConfig/_dbConfig.php';
// --- Composer Autoloader and Environment Variables ---
require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();


// --- PHPMailer Configuration ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/vendor/phpmailer/phpmailer/src/Exception.php';
require '../../PHPMailer/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../../PHPMailer/vendor/phpmailer/phpmailer/src/SMTP.php';

// Check if the email is available in the session. If not, redirect.
if (!isset($_SESSION['login_username'])) {
  $_SESSION['login_error'] = "No email provided for login.";
  // 🔧 ensure session data is written before redirect
  session_write_close();
  header("Location: ../../index.php");
  exit();
}

$email_from_session = $_SESSION['login_username'];

// 🔹 Added for lockout system
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = 0;
if (!isset($_SESSION['lockout_time'])) $_SESSION['lockout_time'] = 0;

$max_attempts = 3;
$lockout_seconds = 60;

// 🔧 Debug log of current session state (check PHP error log)
error_log("[getPassword] session start for {$email_from_session} | attempts={$_SESSION['attempts']} | lockout_time={$_SESSION['lockout_time']}");

// Check if still locked
if (intval($_SESSION['lockout_time']) > time()) {
  $_SESSION['login_error'] = "Too many failed attempts. Please wait 1 minute.";
  // 🔧 write session and redirect
  session_write_close();
  error_log("[getPassword] Locked out (still within lockout). Redirecting back.");
  header("Location: ../../pages/login/password.php");
  exit();
}

// Reset if lockout expired
if (intval($_SESSION['lockout_time']) != 0 && intval($_SESSION['lockout_time']) <= time()) {
  $_SESSION['attempts'] = 0;
  $_SESSION['lockout_time'] = 0;
  unset($_SESSION['login_error']);
  // 🔧 log the reset
  error_log("[getPassword] Lockout expired. Reset attempts/lockout_time for {$email_from_session}.");
}

// --- Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Check if the password input is set and not empty
  if (isset($_POST['pass']) && !empty($_POST['pass'])) {
    $input_password = trim($_POST['pass']);

    // Prepare a statement to fetch the password and user details for the given email
    $stmt = $pdo->prepare("SELECT user_id, password, first_name, last_name, middle_name, email, type, status, dp, contact_number, campus, unit FROM credentials WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email_from_session, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
      $user_credentials = $stmt->fetch(PDO::FETCH_ASSOC);
      $user_status = $user_credentials['status'];
      $user_id = $user_credentials['user_id'];
      $stored_password = $user_credentials['password']; // plain-text password
      $user_first_name = $user_credentials['first_name'];
      $user_last_name = $user_credentials['last_name'];
      $user_email = $user_credentials['email'];
      $user_type = $user_credentials['type'];
      $user_dp = $user_credentials['dp'];
      $user_campus = $user_credentials['campus'];
      $user_contact_number = $user_credentials['contact_number'];
      $user_unit = $user_credentials['unit'];
      $user_middle_name = $user_credentials['middle_name'];

      // --- Password Verification (direct string comparison) ---
      if ($input_password === $stored_password) {
        // ✅ Correct password: reset attempts
        $_SESSION['attempts'] = 0;
        $_SESSION['lockout_time'] = 0;
        unset($_SESSION['login_error']);

        // Check if the user's account is active
        if ($user_status === 'Inactive') {
          header("Location: ../../pages/login/inactive.php");
          exit();
        }

        // 🔧 log success
        error_log("[getPassword] Successful login for {$email_from_session}. Resetting attempts.");

        // Generate and store the 2FA code.
        $verificationCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Clear any old codes
        $clearStmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
        $clearStmt->execute([$user_id]);

        // Insert new code
        $insertStmt = $pdo->prepare("INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
        $insertStmt->execute([$user_id, $verificationCode, $expiresAt]);

        // --- Send email using PHPMailer ---
        try {
          $mail = new PHPMailer(true);

          //Server settings
          $mail->isSMTP();
          $mail->Host       = 'smtp.gmail.com';
          $mail->SMTPAuth   = true;
          $mail->Username   = $_ENV['GMAIL_USERNAME']; // Your Gmail address from .env
          $mail->Password   = $_ENV['GMAIL_APP_PASSWORD']; // Your Gmail App Password from .env
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
          $mail->Port       = 587;
          $htmlBody = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <style>
    body {
      background-color: #f4f6f8;
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      margin: 0;
      padding: 0;
      color: #333333;
    }
    .email-container {
      max-width: 500px;
      margin: 40px auto;
      background-color: #ffffff;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .header {
      background: linear-gradient(135deg, #4F75FF, #6439FF);
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 20px;
      font-weight: bold;
    }
    .content {
      padding: 30px 25px;
      text-align: center;
    }
    .content p {
      font-size: 16px;
      line-height: 1.6;
    }
    .code-box {
      background-color: #f0f3ff;
      border: 1px solid #d1d9ff;
      color: #4F75FF;
      font-size: 24px;
      letter-spacing: 4px;
      font-weight: bold;
      margin: 20px 0;
      padding: 12px 0;
      border-radius: 8px;
      display: inline-block;
      width: 80%;
    }
    .footer {
      text-align: center;
      font-size: 12px;
      color: #888888;
      padding: 20px 10px;
      border-top: 1px solid #eeeeee;
    }
  </style>
</head>
<body>
  <div class='email-container'>
    <div class='header'>
      2-Step Verification
    </div>
    <div class='content'>
      <p>Hello <b>{$user_first_name}</b>,</p>
      <p>Use the verification code below to complete your sign-in process.</p>
      <div class='code-box'>{$verificationCode}</div>
      <p>This code is valid for <b>10 minutes</b>. Please don’t share it with anyone.</p>
    </div>
    <div class='footer'>
      © " . date('Y') . " Customer Satisfaction Survey System. All rights reserved.
    </div>
  </div>
</body>
</html>
"; // The HTML body from your original code

          //Recipients
          $mail->setFrom('ursmain@urs.edu.ph', 'Customer Satisfaction Survey System');
          $mail->addAddress($user_email, $user_first_name . ' ' . $user_last_name);

          //Content
          $mail->isHTML(true);
          $mail->Subject = 'Your 2-Step Verification Code';
          $mail->Body    = $htmlBody;
          $mail->AltBody = "Hello {$user_first_name}, Your verification code is: {$verificationCode}. This code is valid for 10 minutes.";
          $mail->send();

          // Session for verification
          $_SESSION['user_authenticated_pending'] = true;
          $_SESSION['user_id'] = $user_id;
          $_SESSION['user_email'] = $user_email;
          $_SESSION['user_first_name'] = $user_first_name;
          $_SESSION['user_last_name'] = $user_last_name;
          $_SESSION['user_dp'] = $user_dp;
          $_SESSION['user_type'] = $user_type;
          $_SESSION['user_campus'] = $user_campus;
          $_SESSION['user_contact_number'] = $user_contact_number;
          $_SESSION['user_unit'] = $user_unit;
          $_SESSION['user_password'] = $stored_password;
          $_SESSION['user_middle_name'] = $user_middle_name;

          // 🔧 ensure session is written before redirect
          session_write_close();
          header("Location: ../../pages/login/two-factor-authentication.php");
          exit();
        } catch (\Exception $e) {
          $_SESSION['login_error'] = "Could not send verification email. Mailer Error: {$mail->ErrorInfo}";
          // 🔧 write session & log
          session_write_close();
          error_log("[getPassword] PHPMailer error for {$email_from_session}: " . $mail->ErrorInfo);
          header("Location: ../../pages/login/password.php");
          exit();
        }
      } else {
        // ❌ Wrong password
        $_SESSION['attempts']++;

        if ($_SESSION['attempts'] >= $max_attempts) {
          $_SESSION['lockout_time'] = time() + $lockout_seconds;
          $_SESSION['login_error'] = "Too many failed attempts. Please wait 1 minute.";
          // 🔧 log lockout
          error_log("[getPassword] Locking out {$email_from_session}. attempts={$_SESSION['attempts']} lockout_time={$_SESSION['lockout_time']}");
        } else {
          $_SESSION['login_error'] = "Incorrect password.";
          error_log("[getPassword] Incorrect password for {$email_from_session}. attempts={$_SESSION['attempts']}");
        }

        // 🔧 ensure session writes and redirect
        session_write_close();
        header("Location: ../../pages/login/password.php");
        exit();
      }
    } else {
      $_SESSION['login_error'] = "User not found or credentials mismatch.";
      // 🔧 write and log
      session_write_close();
      error_log("[getPassword] User not found for email: {$email_from_session}");
      header("Location: ../../pages/login/password.php");
      exit();
    }
  } else {
    $_SESSION['login_error'] = "Please enter your password.";
    // 🔧 write and redirect
    session_write_close();
    error_log("[getPassword] Empty password submitted for {$email_from_session}");
    header("Location: ../../pages/login/password.php");
    exit();
  }
} else {
  session_write_close();
  header("Location: ../../index.php");
  exit();
}
