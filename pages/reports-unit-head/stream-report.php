<?php
// --- Enhanced Error Reporting for Debugging ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Use absolute paths for reliability
require_once __DIR__ . '/../../fpdf186/fpdf.php';
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get parameters from the URL
$unit_id = $_GET['unit_id'] ?? null;
$quarter = $_GET['quarter'] ?? null;
$year = $_GET['year'] ?? null;
$user_campus = $_SESSION['user_campus'] ?? null;

if (!$unit_id || !$quarter || !$year || !$user_campus) {
    header("HTTP/1.1 400 Bad Request");
    die('Missing required parameters.');
}

try {
    // --- FPDF Custom Class ---
    class PDF extends FPDF
    {
        // Page header
        function Header()
        {
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(80);
            $this->Cell(30, 10, 'CSS Report', 1, 0, 'C');
            $this->Ln(20);
        }

        // Page footer
        function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }
    }

    // --- PDF Generation ---
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Times', '', 12);

    // Fetch office name
    $office_name = 'N/A';
    try {
        $stmt = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE id = ?");
        $stmt->execute([$unit_id]);
        $office_name = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error fetching office name: " . $e->getMessage());
    }

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Report for: ' . $office_name, 0, 1);
    $pdf->Cell(0, 10, "Campus: $user_campus", 0, 1);
    $pdf->Cell(0, 10, "Period: $year - Quarter $quarter", 0, 1);
    $pdf->Ln(10);

    $pdf->SetFont('Times', '', 12);
    $pdf->MultiCell(0, 10, 'This is a sample report generated with FPDF. You can add your report data, tables, and charts here. eh par saan tyo??');

    // Output the PDF to the browser for inline viewing. 'I' sends headers and content.
    $pdf->Output('I', 'report.pdf');
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-Type: text/plain');

    $errorMessage = "An error occurred while generating the PDF: \n\n";
    $errorMessage .= "Message: " . $e->getMessage() . "\n";
    $errorMessage .= "File: " . $e->getFile() . "\n";
    $errorMessage .= "Line: " . $e->getLine() . "\n\n";
    $errorMessage .= "Please check the server's error logs.";

    error_log($errorMessage);
    die($errorMessage);
}
