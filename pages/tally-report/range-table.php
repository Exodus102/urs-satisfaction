<?php
$intro_text = "        Mean was used to determine the extent of the customer satisfaction of the respondents.  To describe the extent of satisfaction, the following range was used:";
$findings_text = "        The following pages present the results of the survey for the stated period presented by specific office/unit.";


$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Statistical Treatment', 0, 1, 'L');
$pdf->Ln(2);
$pdf->SetFont('Arial', '', 12);
$pdf->Multicell(0, 10, $intro_text);
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 12);

// --- Create a 3-column header for the range table ---

// Define column widths.
$col1_width = 40;  // For "Range"
$col2_width = 65;  // For "Verbal Interpretation"
$col3_width = 30;  // For "Legend"
$total_table_width = $col1_width + $col2_width + $col3_width;

// Calculate starting X position to center the table
$page_width = $pdf->GetPageWidth();
$start_x = ($page_width - $total_table_width) / 2;

// Table Header Row
$pdf->SetX($start_x);
$pdf->Cell($col1_width, 10, 'Range', 0, 0, 'L');
$pdf->Cell($col2_width, 10, 'Verbal Interpretation', 0, 0, 'L');
$pdf->Cell($col3_width, 10, 'Legend', 0, 1, 'L'); // The '1' at the end creates a new line

$pdf->SetFont('Arial', '', 12); // Set font to normal for data rows

$range_data = [
    ['range' => '4.50 - 5.00', 'interpretation' => 'Excellent', 'legend' => 'E'],
    ['range' => '3.50 - 4.49', 'interpretation' => 'Very Satisfactory', 'legend' => 'VS'],
    ['range' => '2.50 - 3.49', 'interpretation' => 'Satisfactory', 'legend' => 'S'],
    ['range' => '1.50 - 2.49', 'interpretation' => 'Unsatisfactory', 'legend' => 'US'],
    ['range' => '1.00 - 1.49', 'interpretation' => 'Poor/Needs Improvement', 'legend' => 'P/NI'],
];

foreach ($range_data as $row) {
    // We use the same column widths as the header for alignment.
    // The last '1' in the final Cell call moves the cursor to the next line for the next row.
    $pdf->SetX($start_x);
    $pdf->Cell($col1_width, 5, $row['range'], 0, 0, 'L');
    $pdf->Cell($col2_width, 5, $row['interpretation'], 0, 0, 'L');
    $pdf->Cell($col3_width, 5, $row['legend'], 0, 1, 'L');
}

$pdf->Ln(10); // Add some space after the table
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Findings', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Multicell(0, 6, $findings_text);