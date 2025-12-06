<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';
require_once __DIR__ . '/../_auditTrail/_audit.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);
$filePath = $data['file_path'] ?? null;

if (!$filePath) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File path is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Prepare and execute the update statement
    $stmt = $pdo->prepare("UPDATE tbl_ncar SET status = 'Resolved' WHERE file_path = ?");
    $stmt->execute([$filePath]);

    if ($stmt->rowCount() > 0) {
        // Log the action to the audit trail
        $ncarFileName = basename($filePath, '.pdf'); // Remove .pdf extension
        // Filename format: ncar-report_URS-Campus_Office-Name_2024_q1
        $parts = explode('_', $ncarFileName);

        $logMessage = "Resolved NCAR";
        if (count($parts) >= 3) {
            $campusName = str_replace('-', ' ', $parts[1]);
            $officeName = str_replace('-', ' ', $parts[2]);
            $logMessage = "Resolved NCAR of $campusName Campus for the $officeName";
        } else {
            $logMessage = "Resolved NCAR: " . basename($filePath); // Fallback to filename
        }
        log_audit_trail($pdo, $logMessage);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'NCAR status updated to Resolved.']);
    } else {
        // If no rows were affected, it might mean the record doesn't exist.
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'NCAR record not found for the given file path.']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log('NCAR Resolve Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while resolving the NCAR.']);
}
