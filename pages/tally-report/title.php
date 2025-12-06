<?php
// --- Calculate vertical center position ---
// These values are based on the content of this file.
$content_height = 74; // Total height of all Cells and Lns in this block
$page_height = $pdf->GetPageHeight();
$top_margin = $pdf->getTMargin();
$bottom_margin = $pdf->getBMargin();
$start_y = ($page_height - $content_height) / 2;
$pdf->SetY($start_y);

$pdf->SetFont('Arial', 'B', 26);
$pdf->Cell(0, 10, 'CUSTOMER SATISFACTION', 0, 1, 'C');
$pdf->Ln(2);
$pdf->Cell(0, 10, "SURVEY", 0, 1, 'C');
$pdf->Ln(15); // Add some space after the title

// --- Dynamic Quarter and Year Display ---
// These variables ($quarter, $month, $quarter_text_for_footer, $year) are expected to be defined
// in the script that includes this file (generate-report-tally.php).

$period_title = '';
if ($quarter) {
    // For quarterly reports, we show the quarter name and then the date range below it.
    $period_title = ($quarter == 1 ? "1st" : ($quarter == 2 ? "2nd" : ($quarter == 3 ? "3rd" : "4th"))) . " Quarter";
    $pdf->Cell(0, 10, $period_title, 0, 1, 'C');
    $pdf->Ln(2);
}

$pdf->Cell(0, 10, $quarter_text_for_footer . " " . $year, 0, 1, 'C');

$pdf->Ln(15);
$pdf->Cell(0, 10, "URS " . strtoupper($user_campus), 0, 1, 'C');
