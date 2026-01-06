<?php
header('Content-Type: application/json');
require_once '../_databaseConfig/_dbConfig.php';

require_once '../_auditTrail/_audit.php';

// --- Composer Autoloader and Environment Variables ---
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// --- PHPMailer Configuration ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../PHPMailer/vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../../PHPMailer/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../PHPMailer/vendor/phpmailer/phpmailer/src/SMTP.php';

$response = ['success' => false, 'message' => 'An error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check for all required fields
    if (
        isset($_POST['first_name'], $_POST['last_name'], $_POST['campus'], $_POST['unit'], $_POST['type'], $_POST['email'], $_POST['password'], $_POST['date_created']) &&
        !empty(trim($_POST['first_name'])) &&
        !empty(trim($_POST['last_name'])) &&
        !empty(trim($_POST['campus'])) &&
        !empty(trim($_POST['unit'])) &&
        !empty(trim($_POST['type'])) &&
        !empty(trim($_POST['email'])) &&
        !empty(trim($_POST['password'])) &&
        !empty(trim($_POST['date_created']))
    ) {
        $firstName = trim($_POST['first_name']);
        $middleName = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : ''; // Middle name is optional
        $lastName = trim($_POST['last_name']);
        $contactNumber = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : ''; // Contact number is optional
        $campus = trim($_POST['campus']);
        $unit = trim($_POST['unit']);
        $type = trim($_POST['type']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']); // Storing as plain text as per project's current implementation
        $dateCreated = trim($_POST['date_created']);
        $status = 'Active'; // Static status for new users
        $dp = ''; // Default empty display picture

        try {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM credentials WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetchColumn() > 0) {
                $response['message'] = 'An account with this email already exists.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO credentials (first_name, middle_name, last_name, contact_number, campus, unit, type, dp, email, password, date_created, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$firstName, $middleName, $lastName, $contactNumber, $campus, $unit, $type, $dp, $email, $password, $dateCreated, $status])) {

                    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
                    log_audit_trail($pdo, "Added new user: " . trim("$firstName $lastName") . " ($email)");

                    // Account created, now send email notification
                    $mail = new PHPMailer(true);
                    try {
                        //Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $_ENV['GMAIL_USERNAME'];
                        $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        //Recipients
                        $mail->setFrom('ursmain@urs.edu.ph', 'Customer Satisfaction Survey System');
                        $mail->addAddress($email, $firstName . ' ' . $lastName);

                        //Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Account for the Customer Satisfaction Survey System has been created';
                        $mail->Body    = "Hello {$firstName},<br><br>An account has been created for you in the Customer Satisfaction Survey System.<br><br>You can log in using the following credentials:<br><b>Email:</b> {$email}<br><b>Password:</b> {$password}<br><br>Thank you.";
                        $mail->AltBody = "Hello {$firstName}, An account has been created for you in the Customer Satisfaction Survey System. You can log in using the following credentials: Email: {$email}, Password: {$password}. Thank you.";

                        $mail->send();
                        $response['success'] = true;
                        $response['message'] = 'Account added successfully and a notification email has been sent!';
                    } catch (Exception $e) {
                        $response['success'] = true; // Account was still created
                        $response['message'] = "Account added successfully, but the notification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $response['message'] = 'Failed to add account.';
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
