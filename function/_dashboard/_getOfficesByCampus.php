<?php
// File: function/_dashboard/_getOfficesByCampus.php
header('Content-Type: application/json');
require_once __DIR__ . '/../_databaseConfig/_dbConfig.php';

$campus = $_GET['campus'] ?? null;
$offices = [];

if ($campus) {
    try {
        $stmt = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE campus_name = ? ORDER BY unit_name ASC");
        $stmt->execute([$campus]);
        $offices = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Log error, but return empty array to prevent frontend errors
        error_log("Error fetching offices by campus: " . $e->getMessage());
    }
}

echo json_encode($offices);
