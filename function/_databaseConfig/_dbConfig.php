<?php
// --- Database Configuration ---
// IMPORTANT: Replace these with your actual database credentials.
// For production, consider using environment variables or a more secure config file.
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_css');
define('DB_USER', 'root');
define('DB_PASS', ''); // Your database password

// --- Establish Database Connection ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // You might add error_log here for connection success in a real environment
} catch (PDOException $e) {
    // Log the error (e.g., to a file, not directly to the browser in production)
    error_log("Database connection failed: " . $e->getMessage());
    // For a production environment, you might want to redirect to a generic error page
    // or set a session message here, but for now, we'll let _getEmail.php handle the redirect.
    die("Database connection failed: " . $e->getMessage()); // Stop script execution
}
