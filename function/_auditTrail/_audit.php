<?php

/**
 * Logs an action to the audit trail table.
 *
 * @param PDO $pdo The database connection object.
 * @param string $action The action performed by the user (e.g., 'User logged in').
 * @return void
 */
function log_audit_trail(PDO $pdo, string $action): void
{
    // Ensure session is started to access user details
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Get user details from the session
    $unit_name = $_SESSION['user_unit'] ?? 'Unknown Unit';
    $first_name = $_SESSION['user_first_name'] ?? 'System';
    $last_name = $_SESSION['user_last_name'] ?? 'User';
    $user_name = trim("$first_name $last_name");

    try {
        $stmt = $pdo->prepare("INSERT INTO tbl_audit_trail (unit_name, user_name, action) VALUES (?, ?, ?)");
        $stmt->execute([$unit_name, $user_name, $action]);
    } catch (PDOException $e) {
        // For production, it's better to log this error to a file than to display it.
        error_log("Audit trail logging failed: " . $e->getMessage());
    }
}
