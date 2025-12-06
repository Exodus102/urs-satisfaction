<?php
// Enable detailed error reporting for debugging during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Always start the session at the top.

// --- Database Configuration ---
require_once '../../function/_databaseConfig/_dbConfig.php'; // Adjust path as necessary

// --- Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && !empty($_POST['username'])) {
        $input_value_from_form = trim($_POST['username']); // Get the value from the 'username' input

        // We will now treat this input_value_from_form as the email for database lookup
        // MODIFIED: Select 'first_name' and 'dp' along with 'email'
        $stmt = $pdo->prepare("SELECT email, first_name, dp FROM credentials WHERE email = :email LIMIT 1");

        // Bind the input value to the :email parameter
        $stmt->bindParam(':email', $input_value_from_form, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            // Store the fetched email into the session variable
            $_SESSION['login_username'] = $user_data['email'];
            // NEW: Store the fetched first name into the session variable
            $_SESSION['login_first_name'] = $user_data['first_name'];
            // NEW: Store the fetched display picture path
            $_SESSION['login_user_dp'] = $user_data['dp'];

            header("Location: ../../pages/login/password.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Email not found."; // More specific error message
            header("Location: ../../index.php"); // Redirect to your main login page
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Please enter your email."; // More specific error message
        header("Location: ../../index.php"); // Redirect to your main login page
        exit();
    }
} else {
    header("Location: ../../index.php"); // Redirect for direct access
    exit();
}
