<?php

header('Content-Type: application/json');

// Use the existing database configuration - Corrected Path
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

$data = json_decode(file_get_contents('php://input'), true);
$backupId = $data['backup_id'] ?? null;

if (!$backupId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No backup selected for restore.']);
    exit;
}

// --- Configuration ---
// IMPORTANT: Update this path to your mysql.exe if it's not in your system's PATH
// Common XAMPP path: 'C:\xampp\mysql\bin\mysql.exe'
$mysqlPath = 'C:\xampp\mysql\bin\mysql.exe';

// --- Main Logic ---
try {
    // 1. Get the file path from the database
    $stmt = $pdo->prepare("SELECT file_path FROM tbl_backup WHERE id = ?");
    $stmt->execute([$backupId]);
    $backupFilePath = $stmt->fetchColumn();

    if (!$backupFilePath || !file_exists($backupFilePath)) {
        throw new Exception("Backup file not found for ID: $backupId. It may have been moved or deleted.");
    }

    // 2. Construct the mysql import command
    $command = sprintf(
        '%s --host=%s --user=%s --password=%s %s < %s 2>&1',
        $mysqlPath,
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME),
        escapeshellarg($backupFilePath)
    );

    // 3. Execute the command
    exec($command, $output, $return_var);

    // 4. Check for success
    if ($return_var !== 0) {
        throw new Exception("mysql import failed. Return code: $return_var. Output: " . implode("\n", $output));
    }

    echo json_encode(['success' => true, 'message' => 'Database restored successfully!']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Restore Error: ' . $e->getMessage()); // Log error for debugging
    echo json_encode(['success' => false, 'message' => 'An error occurred during restore: ' . $e->getMessage()]);
}

$pdo = null;
