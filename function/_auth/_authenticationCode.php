<?php
session_start();

require_once '../../function/_databaseConfig/_dbConfig.php';
require_once '../../function/_auditTrail/_audit.php'; // Include the audit trail function

// Check if the user is authorized to be on this page
if (!isset($_SESSION['user_authenticated_pending']) || !$_SESSION['user_authenticated_pending']) {
    $_SESSION['login_error'] = "Authentication session expired.";
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['code'])) {
    $input_code = trim($_POST['code']);
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("SELECT code, expires_at FROM two_factor_codes WHERE user_id = :user_id ORDER BY expires_at DESC LIMIT 1");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($verification) {
            $currentTime = new DateTime();
            $expiryTime = new DateTime($verification['expires_at']);

            if ($input_code === $verification['code'] && $currentTime < $expiryTime) {
                // Code is correct and valid.
                $_SESSION['user_authenticated'] = true;
                unset($_SESSION['user_authenticated_pending']);

                // --- LOG THE LOGIN ACTION TO THE AUDIT TRAIL ---
                log_audit_trail($pdo, 'User logged in');

                // Get the user's type from the session
                $user_type = $_SESSION['user_type'];

                // Clean up the used code from the database
                $clearStmt = $pdo->prepare("DELETE FROM two_factor_codes WHERE user_id = ?");
                $clearStmt->execute([$user_id]);

                // --- ADDED: Fetch and set user_unit_id for Unit Heads ---
                if ($user_type === 'Unit Head') {
                    try {
                        // Corrected query to join with tbl_unit and get the ID
                        $stmt_unit = $pdo->prepare("
                            SELECT u.id 
                            FROM credentials c
                            JOIN tbl_unit u ON c.unit = u.unit_name
                            WHERE c.user_id = ?
                        ");
                        $stmt_unit->execute([$_SESSION['user_id']]);
                        $unit_id = $stmt_unit->fetchColumn();
                        $_SESSION['user_unit_id'] = $unit_id;
                    } catch (PDOException $e) {
                        // Log error but don't block login
                        error_log("Failed to fetch unit_id for user_id: $user_id. Error: " . $e->getMessage());
                    }
                }

                // Redirect based on user type
                if ($user_type === 'Admin') {
                    header("Location: ../../include/university-mis/university-mis-layout.php");
                } elseif ($user_type === 'CSS Coordinator') {
                    header("Location: ../../include/css-coordinators/css-coordinators-layout.php");
                } elseif ($user_type === 'CSS Head') {
                    header("Location: ../../include/css-head/css-head-layout.php");
                } elseif ($user_type === 'DCC') {
                    header("Location: ../../include/dcc/dcc-layout.php");
                } elseif ($user_type === 'Campus Director') {
                    header("Location: ../../include/campus-directors/campus-directors-layout.php");
                } elseif ($user_type === 'Unit Head') {
                    header("Location: ../../include/unit-head/unit-head-layout.php");
                } else {
                    // Default redirect for all other user types
                    header("Location: ../../include/layout.php");
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid or expired verification code.";
                header("Location: ../../pages/login/two-factor-authentication.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "No verification code found. Please try logging in again.";
            header("Location: ../../pages/login/two-factor-authentication.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['login_error'] = "Database error: " . $e->getMessage();
        header("Location: ../../pages/login/two-factor-authentication.php");
        exit();
    }
} else {
    header("Location: ../../pages/login/two-factor-authentication.php");
    exit();
}
