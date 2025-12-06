<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents('php://input'), true);
$comment_id = $data['comment_id'] ?? null;

if (!$comment_id) {
    echo json_encode(['success' => false, 'message' => 'Error: Report identifier is missing.']);
    exit;
}


if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Campus Director') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

try {
    // To construct the file path, we need to fetch the details associated with the comment_id
    $stmt_details = $pdo->prepare("
        SELECT u.unit_name, u.campus_name, YEAR(r.timestamp) as year, QUARTER(r.timestamp) as quarter
        FROM tbl_responses r
        JOIN tbl_responses r_office ON r.response_id = r_office.response_id AND r_office.question_id = -3
        JOIN tbl_unit u ON r_office.response = u.unit_name
        WHERE r.id = ?
    ");
    $stmt_details->execute([$comment_id]);
    $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        throw new Exception("Could not find report details for the given ID.");
    }

    // Reconstruct the unique file path
    $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $details['campus_name']);
    $safe_unit_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $details['unit_name']);
    $year = $details['year'];
    $quarter = $details['quarter'];
    $file_path = "upload/pdf/ncar-report_{$safe_campus_name}_{$safe_unit_name}_{$year}_q{$quarter}_{$comment_id}.pdf";

    // Update the status in tbl_ncar for the specific file path
    $stmt = $pdo->prepare("UPDATE tbl_ncar SET status = 'Resolved' WHERE file_path = ?");
    $stmt->execute([$file_path]);

    echo json_encode(['success' => true, 'message' => 'NCAR has been marked as Resolved.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
