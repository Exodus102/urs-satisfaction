<?php

header('Content-Type: application/json');

// Use the existing database configuration - Corrected Path
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

require_once __DIR__ . '/../_auditTrail/_audit.php'; // Include the audit trail function

// --- Configuration ---
// IMPORTANT: Update this path to your mysqldump.exe if it's not in your system's PATH
// Common XAMPP path: 'C:\xampp\mysql\bin\mysqldump.exe'
$mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';

$rawBackupDir = __DIR__ . '/../../upload/backups/';

// --- Main Logic ---
try {
    // 1. Determine the next version number
    $stmt = $pdo->query("SELECT version FROM tbl_backup ORDER BY id DESC LIMIT 1");
    $latestVersion = $stmt->fetchColumn();

    if ($latestVersion) {
        // Parse the latest version, e.g., "1.4"
        $versionParts = explode('.', (string)$latestVersion);
        $major = (int) ($versionParts[0] ?? 1);
        $minor = (int) ($versionParts[1] ?? 0);
        $minor++; // Increment minor version

        if ($minor >= 10) {
            $major++;
            $minor = 0;
        }
        $version = $major . '.' . $minor;
    } else {
        // No backups exist yet, start with 1.0
        $version = '1.0';
    }

    // 2. Ensure the backup directory exists, is writable, and get its real path
    if (!is_dir($rawBackupDir)) {
        if (!mkdir($rawBackupDir, 0777, true) && !is_dir($rawBackupDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $rawBackupDir));
        }
    }
    $backupDir = realpath($rawBackupDir);
    if ($backupDir === false) {
        throw new RuntimeException('Could not resolve the real path for the backup directory.');
    }
    // Add a directory separator
    $backupDir .= DIRECTORY_SEPARATOR;

    // Generate a unique filename
    $backupFile = 'db_css_backup_v' . $version . '_' . date("Y-m-d_H-i-s") . '.sql';

    $backupFilePath = $backupDir . $backupFile;

    // Construct the mysqldump command
    // Using escapeshellarg to prevent command injection and 2>&1 to capture errors
    $command = sprintf(
        '%s --host=%s --user=%s --password=%s %s > %s 2>&1',
        $mysqldumpPath,
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($backupFilePath)
    );

    // Execute the command
    exec($command, $output, $return_var);

    // Check if the command was successful
    if ($return_var !== 0) {
        throw new Exception("mysqldump failed. Return code: $return_var. Output: " . implode("\n", $output));
    }

    // Get file size and format it
    $fileSize = filesize($backupFilePath);
    $sizeFormatted = round($fileSize / 1024 / 1024, 2) . ' MB'; // Format to MB

    // Insert backup record into the database
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_backup (available_backups, version, size, file_path) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$backupFile, $version, $sizeFormatted, $backupFilePath]);

    // --- LOG THE ACTION TO THE AUDIT TRAIL ---
    log_audit_trail($pdo, "Created a new system backup: " . $backupFile);

    echo json_encode(['success' => true, 'message' => 'Backup created successfully!']);
} catch (Exception $e) {
    // Clean up failed backup file if it exists
    if (isset($backupFilePath) && file_exists($backupFilePath)) {
        unlink($backupFilePath);
    }

    http_response_code(500);
    error_log('Backup Error: ' . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'An error occurred during backup: ' . $e->getMessage()]);
}

$pdo = null;
