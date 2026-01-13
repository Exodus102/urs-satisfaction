<?php

header('Content-Type: application/json');

// Use the existing database configuration - Corrected Path
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

require_once __DIR__ . '/../_auditTrail/_audit.php'; // Include the audit trail function

$data = json_decode(file_get_contents('php://input'), true);
$backupId = $data['backup_id'] ?? null;

if (!$backupId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No backup selected for restore.']);
    exit;
}

// --- Main Logic ---
try {
    // Increase execution time for large restores
    set_time_limit(0);
    // 1. Get the file path from the database
    $stmt = $pdo->prepare("SELECT file_path FROM tbl_backup WHERE id = ?");
    $stmt->execute([$backupId]);
    $backupFilePath = $stmt->fetchColumn();

    if (!$backupFilePath || !file_exists($backupFilePath)) {
        throw new Exception("Backup file not found for ID: $backupId. It may have been moved or deleted.");
    }

    // 2. PHP-based SQL Restore (No exec() required)
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0'); // Disable FK checks to prevent errors during restore

    $handle = fopen($backupFilePath, "r");
    if ($handle) {
        $query = '';
        while (($line = fgets($handle)) !== false) {
            $trimLine = trim($line);
            // Skip empty lines and comments
            if ($trimLine === '' || strpos($trimLine, '--') === 0 || strpos($trimLine, '#') === 0) {
                continue;
            }

            $query .= $line;
            // If the line ends with a semicolon, execute the query
            if (substr(rtrim($line), -1) === ';') {
                $pdo->exec($query);
                $query = '';
            }
        }
        fclose($handle);
    } else {
        throw new Exception("Could not open backup file for reading.");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); // Re-enable FK checks

    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
    log_audit_trail($pdo, "Restored database from backup ID: " . $backupId);

    echo json_encode(['success' => true, 'message' => 'Database restored successfully!']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Restore Error: ' . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'An error occurred during restore: ' . $e->getMessage()]);
}

$pdo = null;
