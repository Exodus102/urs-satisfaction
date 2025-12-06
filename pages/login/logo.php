<?php
// This component fetches and displays the currently active system logo.
// It centralizes the logic so that all login-related pages can display the correct logo consistently.

// Determine the correct path to the database configuration file.
// This handles being included from different directory depths.
$db_config_path = __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';
if (!file_exists($db_config_path)) {
    $db_config_path = __DIR__ . '/../function/_databaseConfig/_dbConfig.php';
}
require_once $db_config_path;

$logo_path = 'resources/img/new-logo.png'; // Default fallback logo

try {
    $stmt = $pdo->prepare("SELECT logo_path FROM tbl_logo WHERE status = 1 LIMIT 1");
    $stmt->execute();
    $active_logo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($active_logo && !empty($active_logo['logo_path'])) {
        $logo_path = $active_logo['logo_path'];
    }
} catch (PDOException $e) {
    // If there's a DB error, we'll just use the default logo.
    // You could log the error here: error_log($e->getMessage());
}

// The path needs to be adjusted based on where it's included from.
$final_logo_path = (basename($_SERVER['PHP_SELF']) === 'index.php') ? $logo_path : '../../' . $logo_path;
?>
<img src="<?php echo htmlspecialchars($final_logo_path); ?>" alt="System Logo" class="w-20 h-20 object-contain">