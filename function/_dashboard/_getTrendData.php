<?php
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$session_campus = $_SESSION['user_campus'] ?? null;
$selected_campus = $_GET['campus'] ?? $session_campus; // Prioritize GET param, fallback to session
$selected_office = $_GET['office'] ?? null;
$period = $_GET['period'] ?? 'annual'; // Default to annual

if (!$selected_campus) {
    echo json_encode(['error' => 'User campus not set.']);
    exit;
}

try {
    $params = [':campus' => $selected_campus];
    $base_query = "
        FROM tbl_responses r
        WHERE r.response_id IN (
            SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :campus
        )
    ";

    if (!empty($selected_office)) {
        $base_query .= " AND r.response_id IN (
            SELECT response_id FROM tbl_responses WHERE question_id = -3 AND response = :office
        )";
        $params[':office'] = $selected_office;
    }

    $trend_labels = [];
    $trend_data = [];

    // --- Build query based on period ---
    if ($period === 'monthly') {
        // Group by month for the current year
        $sql = "SELECT MONTH(r.timestamp) as period_key, COUNT(DISTINCT r.response_id) as response_count
                " . $base_query . "
                AND YEAR(r.timestamp) = YEAR(CURDATE())
                GROUP BY period_key
                ORDER BY period_key ASC";
        $stmt_trend = $pdo->prepare($sql);
        $stmt_trend->execute($params);
        $results = $stmt_trend->fetchAll(PDO::FETCH_KEY_PAIR);

        for ($m = 1; $m <= 12; $m++) {
            $trend_labels[] = date('M', mktime(0, 0, 0, $m, 1));
            $trend_data[] = $results[$m] ?? 0;
        }
    } elseif ($period === 'quarterly') {
        // Group by quarter for the current year
        $sql = "SELECT QUARTER(r.timestamp) as period_key, COUNT(DISTINCT r.response_id) as response_count
                " . $base_query . "
                AND YEAR(r.timestamp) = YEAR(CURDATE())
                GROUP BY period_key
                ORDER BY period_key ASC";
        $stmt_trend = $pdo->prepare($sql);
        $stmt_trend->execute($params);
        $results = $stmt_trend->fetchAll(PDO::FETCH_KEY_PAIR);

        for ($q = 1; $q <= 4; $q++) {
            $trend_labels[] = 'Q' . $q;
            $trend_data[] = $results[$q] ?? 0;
        }
    } else { // Default to 'annual'
        // Group by year
        $sql = "SELECT YEAR(r.timestamp) as period_key, COUNT(DISTINCT r.response_id) as response_count
                " . $base_query . "
                GROUP BY period_key
                ORDER BY period_key ASC";
        $stmt_trend = $pdo->prepare($sql);
        $stmt_trend->execute($params);
        $results = $stmt_trend->fetchAll(PDO::FETCH_KEY_PAIR);

        if (!empty($results)) {
            $min_year = min(array_keys($results));
            $max_year = max(array_keys($results));
            for ($y = $min_year; $y <= $max_year; $y++) {
                $trend_labels[] = (string)$y;
                $trend_data[] = $results[$y] ?? 0;
            }
        }
    }

    echo json_encode([
        'labels' => $trend_labels,
        'data' => $trend_data,
    ]);
} catch (PDOException $e) {
    error_log("Error fetching trend data: " . $e->getMessage());
    echo json_encode(['error' => 'Database query failed.']);
}
