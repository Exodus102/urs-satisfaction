<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

require_once __DIR__ . '/../_auditTrail/_audit.php'; // Include the audit trail function

$data = json_decode(file_get_contents('php://input'), true);
$backupId = $data['backup_id'] ?? null;

if (!$backupId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No backup ID provided.']);
    exit;
}

try {
    // 1. Get backup details (file path and name) from the database before deleting the record
    $stmt = $pdo->prepare("SELECT file_path, available_backups FROM tbl_backup WHERE id = ?");
    $stmt->execute([$backupId]);
    $backup = $stmt->fetch();

    if ($backup) {
        $backupFilePath = $backup['file_path'];
        $backupFileName = $backup['available_backups'];

        // 2. Delete the record from the database
        $deleteStmt = $pdo->prepare("DELETE FROM tbl_backup WHERE id = ?");
        $deleteStmt->execute([$backupId]);

        // 3. If the record was deleted and the file exists, delete the file
        if ($deleteStmt->rowCount() > 0 && $backupFilePath && file_exists($backupFilePath)) {
            unlink($backupFilePath);
        }

        // --- LOG THE ACTION TO THE AUDIT TRAIL ---
        log_audit_trail($pdo, "Deleted backup: " . $backupFileName);

        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Backup not found.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Delete Backup Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the backup: ' . $e->getMessage()]);
}

$pdo = null;
