<?php
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_campus = $_SESSION['user_campus'] ?? null;
$selected_office = $_GET['office'] ?? null;

if (!$user_campus) {
    echo json_encode(['error' => 'User campus not set.']);
    exit;
}

try {
    $params = [':campus' => $user_campus];

    if (empty($selected_office)) {
        // For "All Offices", we need to join with tbl_unit to ensure we only count offices within the campus
        $base_query = "
            FROM tbl_responses r
            WHERE r.response_id IN (
                SELECT r_inner.response_id
                FROM tbl_responses r_inner
                JOIN tbl_unit u ON r_inner.response = u.unit_name AND r_inner.question_id = -3
                WHERE u.campus_name = :campus
            )
        ";
    } else {
        // For a specific office, filter by campus AND office
        $base_query = "
            FROM tbl_responses r
            WHERE question_id = -3 AND response = :office
            AND response_id IN (SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :campus)
        ";
        $params[':office'] = $selected_office;
    }

    // --- Current Month's Data ---
    $current_month_query = "SELECT COUNT(DISTINCT r.response_id) " . $base_query . " AND YEAR(r.timestamp) = YEAR(CURDATE()) AND MONTH(r.timestamp) = MONTH(CURDATE())";
    $stmt_current = $pdo->prepare($current_month_query);
    $stmt_current->execute($params);
    $current_month_count = (int) $stmt_current->fetchColumn();

    // --- Last Month's Data ---
    $last_month_query = "SELECT COUNT(DISTINCT r.response_id) " . $base_query . " AND YEAR(r.timestamp) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(r.timestamp) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
    $stmt_last = $pdo->prepare($last_month_query);
    $stmt_last->execute($params);
    $last_month_count = (int) $stmt_last->fetchColumn();

    // --- Positive Responses for Current Month ---
    // We need to find response_ids that match the office/campus and then check their analysis
    $positive_query = "
        SELECT COUNT(DISTINCT r_positive.response_id)
        FROM tbl_responses r_positive
        WHERE r_positive.analysis = 'positive'
        AND YEAR(r_positive.timestamp) = YEAR(CURDATE()) AND MONTH(r_positive.timestamp) = MONTH(CURDATE())
        AND r_positive.response_id IN (SELECT r.response_id " . $base_query . ")
    ";
    $stmt_positive = $pdo->prepare($positive_query);
    $stmt_positive->execute($params);
    $positive_count = (int) $stmt_positive->fetchColumn();

    // --- Calculations ---
    $vs_last_month = $current_month_count - $last_month_count;
    $response_rate = 0;
    if ($current_month_count > 0) {
        $response_rate = round(($positive_count / $current_month_count) * 100);
    }

    // --- Format vs Last Month ---
    $vs_last_month_display = $vs_last_month;
    if ($vs_last_month > 0) {
        $vs_last_month_display = '+' . $vs_last_month;
    }

    echo json_encode([
        'current_month_count' => $current_month_count,
        'response_rate' => $response_rate,
        'vs_last_month' => $vs_last_month_display,
    ]);
} catch (PDOException $e) {
    error_log("Error fetching monthly office response count: " . $e->getMessage());
    echo json_encode(['error' => 'Database query failed.']);
}
