<?php

// This file is included from generate-report-tally.php,
// so it has access to $pdf, $year, $quarter, and $quarter_text_for_footer.

$intro_text = '';
$scope_text = "        The 10 CSS boxes distributed to various buildings where the units/offices are located were maintained.  As was the practice, they are kept locked, the keys of which are handled by the chairman of the Customer Satisfaction Survey Committee.  Two sets of keys are still kept by the Chairman for safekeeping.";
$intrument_text = "        The survey utilized a customer satisfaction survey form placed in the CSS boxes.  Blank forms were placed beside the padlocked container so as to give customers access.  Once the forms were filled-up by the customers, they can drop the forms inside the box which will then be collected later on by the committee for analysis and preparation of reports.  The filled-up forms are submitted together with the final report.";

// Determine the full quarter name for the text.
$quarter_full_name = '';
if ($quarter) {
    switch ($quarter) {
        case 1:
            $quarter_full_name = "First Quarter";
            break;
        case 2:
            $quarter_full_name = "Second Quarter";
            break;
        case 3:
            $quarter_full_name = "Third Quarter";
            break;
        case 4:
            $quarter_full_name = "Fourth Quarter";
            break;
    }
    $intro_text = "        As one of the quality objectives of each unit, a customer satisfaction survey is necessary to ensure that the performance and delivery of service is of high level of quality and is maintained by the same. This report covers the {$quarter_full_name} of the calendar year {$year} ({$quarter_text_for_footer}).";
} else {
    // Generic text for monthly or other views
    $intro_text = "        As one of the quality objectives of each unit, a customer satisfaction survey is necessary to ensure that the performance and delivery of service is of high level of quality and is maintained by the same. This report covers the period of {$quarter_text_for_footer}, {$year}.";
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Introduction', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Multicell(0, 10, $intro_text);
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Scope: Customer Satisfaction Survey and Upkeep of Satisfaction Boxes', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Multicell(0, 10, $scope_text);
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Instruments', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Multicell(0, 10, $intrument_text);
