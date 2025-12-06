<?php ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../../fpdf186/fpdf.php';
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$unit_id = $_GET['unit_id'] ?? null;
$year = $_GET['year'] ?? null;
$quarter = $_GET['quarter'] ?? null;
$user_campus = $_SESSION['user_campus'] ?? null;

if (!$unit_id || !$year || !$quarter || !$user_campus) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters (office, year, quarter, or campus).']);
    exit;
}

try {
    // --- FPDF Custom Class ---
    class PDF extends FPDF
    {
        public $user_campus = '';
        public $quarter_display = '';
        public $year = '';

        function Header()
        {
            // --- Dynamic Logo and Text Centering ---
            $logo1Path = '../../resources/img/urs-logo.png';
            $logo2Path = '../../resources/img/tuvr-urs-logo-mark.jpg';
            $logo1Width = 15;
            $logo2Width = 28;
            $logoGap = 10; // Increased gap

            // Set font to calculate the width of the main title, which is the widest part of the text block.
            $this->SetFont('Arial', 'B', 12);
            $titleWidth = $this->GetStringWidth('UNIVERSITY OF RIZAL SYSTEM');

            // Calculate the total width of the entire header block (text + gap + logo1 + gap + logo2)
            $totalBlockWidth = $logo1Width + $logoGap + $titleWidth + $logoGap + $logo2Width;
            $startX = ($this->GetPageWidth() - $totalBlockWidth) / 2;

            $this->Image($logo1Path, $startX + 13, 8, $logo1Width);
            if (file_exists($logo2Path)) {
                $this->Image($logo2Path, $startX + $logo1Width + $logoGap + $titleWidth + $logoGap, 10, $logo2Width, 12);
            }

            // --- Centered Header Text ---
            $this->SetY(10); // Move the cursor up to align text with the logo
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, 'UNIVERSITY OF RIZAL SYSTEM', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 2, 'Province of Rizal', 0, 1, 'C');
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 5, 'www.urs.edu.ph', 0, 1, 'C');
            $this->Ln(5);
            $this->Cell(0, 3, 'Email Address: ursmain@urs.edu.ph /urs.opmorong@gmail.com', 0, 1, 'C');
            $this->Cell(0, 5, 'Main Campus:  URS Tanay Tel. (02) 401-4900; 401-4910; 401-4911; telefax 653-1735', 0, 1, 'C');
            $this->SetLineWidth(0.5);
            $this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
            $this->Ln(5);
        }

        function Footer()
        {
            // Position at 1.5 cm from bottom
            $this->SetY(-15);
            // Set font for footer
            $this->SetFont('Arial', '', 10);

            // Calculate width for each of the 3 cells to span the page
            $pageWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
            $cellWidth = $pageWidth / 3;

            $this->Cell($cellWidth, 10, "URS-OP-IS-ISC-F-2017-0004", 0, 0, 'L');
            $this->Cell($cellWidth, 10, "Rev. 00", 0, 0, 'C');
            $this->Cell($cellWidth, 10, "Effective Date: August 15, 2017", 0, 1, 'R');
        }

        function NbLines($w, $txt)
        {
            if (!isset($this->CurrentFont)) $this->Error('No font has been set');
            $cw = &$this->CurrentFont['cw'];
            if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s = str_replace("\r", '', $txt);
            $nb = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
            $sep = -1;
            $i = 0;
            $j = 0;
            $l = 0;
            $nl = 1;
            while ($i < $nb) {
                $c = $s[$i];
                if ($c == "\n") {
                    $i++;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                    continue;
                }
                if ($c == ' ') $sep = $i;
                $l += $cw[$c];
                if ($l > $wmax) {
                    if ($sep == -1) {
                        if ($i == $j) $i++;
                    } else $i = $sep + 1;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                } else $i++;
            }
            return $nl;
        }

        // Getter for lMargin
        public function getLMargin()
        {
            return $this->lMargin;
        }

        // Getter for rMargin
        public function getRMargin()
        {
            return $this->rMargin;
        }
    }

    // --- PDF Generation ---
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 25);

    // Set dynamic data for header/footer
    $pdf->user_campus = $user_campus;
    $pdf->year = $year;
    $quarter_map = [1 => "1st Quarter", 2 => "2nd Quarter", 3 => "3rd Quarter", 4 => "4th Quarter"];
    $pdf->quarter_display = $quarter_map[$quarter] ?? '';

    $pdf->AddPage();

    // --- Report Title & Info ---
    $pdf->SetFont('Arial', 'B', 12.5);
    $pdf->Cell(0, 8, 'NON-CONFORMITY and CORRECTIVE ACTION REPORT (NCAR)', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, 'NCAR No.:', 0, 0, 'L');
    $pdf->Cell(0, 8, "Date: " . date('F j, Y'), 0, 1, 'R');

    // --- Fetch Office Name (still needed for filename) ---
    $stmt_office = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE id = ?");
    $stmt_office->execute([$unit_id]);
    $office_name = $stmt_office->fetchColumn();
    if (!$office_name) {
        throw new Exception("Office with ID $unit_id not found.");
    }

    // --- NCAR Form Table Structure ---

    $pageWidth = $pdf->GetPageWidth() - $pdf->getLMargin() - $pdf->getRMargin();

    // Row 1: Unit and Section Clause, side-by-side
    $y1 = $pdf->GetY();
    $x1 = $pdf->GetX();
    $cell_height = 8;
    $half_width = $pageWidth / 2;

    // Draw left cell border and write mixed-style text inside
    $pdf->Cell($half_width, $cell_height, '', 'LTB', 0, 'L');
    $pdf->SetXY($x1, $y1); // +2 for padding
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Write($cell_height, 'Unit: ');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Write($cell_height, $office_name);

    // Draw right cell
    $pdf->SetXY($x1 + $half_width, $y1);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell($half_width, $cell_height, 'Section Clause No. (for IQA only): ', 'TRB', 1, 'L');

    // Row 2: Details of Non-Conformity
    // Use Write() for mixed font styles (Bold and Italic)
    $y_pos = $pdf->GetY();
    $x_pos = $pdf->GetX();

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Write(8, '1. Details: ');
    $pdf->SetFont('Arial', 'I', 11); // Use Bold-Italic for emphasis
    $pdf->Write(8, 'Non-conformity raised as a result of:');

    // Draw the cell border around the text and move to the next line
    $pdf->SetXY($x_pos, $y_pos);
    $pdf->Cell($pageWidth, 6, '', 'LR', 1, 'L');

    // --- 2x4 Grid for Checklist Items ---
    $pdf->SetFont('Arial', '', 11);
    $items_left = [
        '[   ] Material, Product or Equipment',
        '[   ] Customer Complaints',
        '[   ] Internal Quality Audit',
        '[   ] Clientele Satisfaction Survey'
    ];
    $items_right = [
        '[   ] Unmet Quality Objectives',
        '[   ] Service Non-conformity',
        '[   ] Improvement',
        '[   ] Others'
    ];

    $cellHeight = 5; // A more compact height
    $colWidth = $pageWidth / 2;
    for ($i = 0; $i < 4; $i++) {
        $pdf->Cell($colWidth, $cellHeight, '                  ' . $items_left[$i], 'L', 0, 'L');
        $pdf->Cell($colWidth, $cellHeight, '     ' . $items_right[$i], 'R', 1, 'L');
    }
    // Draw the bottom border for the entire section
    $pdf->Cell($pageWidth, 0, '', 'T', 1);

    // Row 3: Description of Non-conformity
    $pdf->SetFont('Arial', 'B', 11);
    // Use Write() for mixed font styles
    $y_pos_desc = $pdf->GetY();
    $x_pos_desc = $pdf->GetX();
    $pdf->Write(8, '2. Description of: ');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Write(8, ' [   ] Non-Conformity                    [   ] Improvement');
    // Draw the cell border around the text
    $pdf->SetXY($x_pos_desc, $y_pos_desc);
    $pdf->Cell($pageWidth, 8, '', 'LR', 1, 'L');

    // --- Fetch and Display Negative Analysis Comments ---
    $sql_comments = "
        SELECT DISTINCT r.comment
        FROM tbl_responses r
        JOIN (
            SELECT response_id FROM tbl_responses
            WHERE question_id = -3 AND response = :office_name
        ) AS office_responses ON r.response_id = office_responses.response_id
        JOIN (
            SELECT response_id FROM tbl_responses
            WHERE question_id = -1 AND response = :user_campus
        ) AS campus_responses ON r.response_id = campus_responses.response_id
        WHERE r.analysis = 'negative'
          AND YEAR(r.timestamp) = :year
          AND QUARTER(r.timestamp) = :quarter
          AND r.comment IS NOT NULL
          AND TRIM(r.comment) <> ''
    ";
    $stmt_comments = $pdo->prepare($sql_comments);
    $stmt_comments->execute([
        ':office_name' => $office_name,
        ':user_campus' => $user_campus,
        ':year' => $year,
        ':quarter' => $quarter
    ]);
    $negative_comments = $stmt_comments->fetchAll(PDO::FETCH_COLUMN);

    $comments_text = "";
    foreach ($negative_comments as $index => $comment) {
        $comments_text .= "          " . chr(149) . " " . trim(html_entity_decode($comment)) . "\n";
    }
    // Use a MultiCell for comments, but without the bottom border yet.
    $pdf->MultiCell($pageWidth, 6, $comments_text ?: "\n No specific comments found for negative analysis.", 'LR', 'L');
    // Now add the "Detected by" line, which will have the bottom border.
    $pdf->Cell($pageWidth / 2, 8, 'Detected by:', 'L', 0, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Date:', 'R', 1, 'L');
    $pdf->Cell($pageWidth, 0, '', 'T', 1); // Draw the bottom line for the whole row

    // Row 4: Disposition
    $pdf->SetFont('Arial', 'B', 11);
    // Use Write() for mixed font styles
    $y_pos_disp = $pdf->GetY();
    $x_pos_disp = $pdf->GetX();
    $pdf->Write(8, '3. Disposition: ');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Write(8, '[Applicable for Material/Product or Equipment only]');
    // Draw the cell border around the text
    $pdf->SetXY($x_pos_disp, $y_pos_disp);
    $pdf->Cell($pageWidth, 8, '', 'LR', 1, 'L');
    // Create a 3-column grid for disposition options.
    $colWidth = $pageWidth / 3;
    $cellHeight = 5;

    // Row 1
    $pdf->Cell($colWidth, $cellHeight, '            [   ] Rework/Repair', 'L', 0, 'L');
    $pdf->Cell($colWidth, $cellHeight, '             [   ] Use as is', '', 0, 'L');
    $pdf->Cell($colWidth, $cellHeight, ' [   ] N/A', 'R', 1, 'L');
    // Row 2
    $pdf->Cell($colWidth, $cellHeight, '            [   ] Reject & return to supplier', 'L', 0, 'L');
    $pdf->Cell($colWidth, $cellHeight, '             [   ] Other:', '', 0, 'L');
    $pdf->Cell($colWidth, $cellHeight, '', 'R', 1, 'L'); // Add empty cell for right border
    $pdf->Cell($pageWidth / 2, 8, 'Proposed by:', 'LB', 0, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Date:', 'RB', 1, 'L');


    // Row 5: Corrective Action
    $pdf->SetFont('Arial', 'B', 11);
    $y_pos_ca = $pdf->GetY();
    $x_pos_ca = $pdf->GetX();
    $pdf->Write(8, '4. [   ] Correction (Immediate Action): ');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Write(8, '                   [   ] Not Applicable');
    $pdf->SetXY($x_pos_ca, $y_pos_ca);
    $pdf->Cell($pageWidth, 15, '', 'LR', 1, 'L');
    $pdf->Cell($pageWidth, 5, '', 'LR', 1, 'L'); // Empty space for corrective action
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($pageWidth / 2, 8, 'Responsible Person/s:', 'LB', 0, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Date:', 'RB', 1, 'L');

    // Row 6: Proposed by
    $pdf->SetFont('Arial', 'B', 11);
    $y_pos_pb = $pdf->GetY();
    $x_pos_pb = $pdf->GetX();
    $pdf->Write(8, '5. Root Cause Analysis: [   ] Non-conformity');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Write(8, '      [   ] Not Applicable');
    $pdf->SetXY($x_pos_pb, $y_pos_pb);
    $pdf->Cell($pageWidth, 20, '', 'LR', 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($pageWidth, 10, 'Investigated by:                                                      Date:', 'LR', 1, 'L'); // Empty space for signature/name
    $pdf->Cell($pageWidth / 2, 8, 'Conforme:', 'LB', 0, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Date:', 'RB', 1, 'L');

    // Row 7: Verified by
    $pdf->SetFont('Arial', 'B', 11);
    $y_pos_vb = $pdf->GetY();
    $x_pos_vb = $pdf->GetX();
    $pdf->Write(8, '6. [  ] Corrective Action:');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Write(8, '                                          [   ] Improvement:');
    $pdf->SetXY($x_pos_vb, $y_pos_vb);
    $pdf->Cell($pageWidth, 15, '', 'LR', 1, 'L');
    $pdf->Cell($pageWidth, 5, '', 'LR', 1, 'L'); // Empty space for signature/name
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($pageWidth / 2, 8, 'Responsible:', 'LB', 0, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Date:', 'RB', 1, 'L');

    // Row 8: Verification of Effectiveness
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell($pageWidth, 8, '7. Follow-up Implementation of Action:', 'LTR', 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($pageWidth, 6, '          [   ] Satisfactory                                                         [   ] Not satisfactory', 'LR', 1, 'L');
    $pdf->Cell($pageWidth, 6, '          Remarks:', 'LR', 1, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Name & Signature:', 'LB', 0, 'L');
    $pdf->Cell($pageWidth / 2, 8, 'Date:', 'RB', 1, 'L');

    // Row 9: NCAR Closure
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->MultiCell($pageWidth, 6, '8. Verification on the effectiveness of action: To be completed by the ISO Chairperson or Unit Head', 'LTR', 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($pageWidth / 2, 6, '          [   ] Satisfactory', 'L', 0, 'L');
    $pdf->Cell($pageWidth / 2, 6, '[   ] Not satisfactory (issue new NCAR)', 'R', 1, 'L');
    $pdf->Cell($pageWidth, 6, '          Remarks:', 'LR', 1, 'L');
    $pdf->Cell($pageWidth, 5, 'Verified by:____________________     _____________    _______________', 'LR', 1, 'L');
    $pdf->Cell($pageWidth, 5, '                             Print Name                       Signature                   Date', 'LBR', 0, 'L');

    // --- Save and Output ---
    $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $user_campus);
    $safe_office_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $office_name ?: 'UnknownOffice');
    $filename = "ncar-report_{$safe_campus_name}_{$safe_office_name}_{$year}_q{$quarter}.pdf";
    $savePath = __DIR__ . '/../../upload/pdf/' . $filename;

    $directory = dirname($savePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $pdf->Output('F', $savePath);

    $relativePath = 'upload/pdf/' . $filename;

    // --- Save to tbl_ncar database ---
    try {
        // Check if a record for this file path already exists
        $checkStmt = $pdo->prepare("SELECT id FROM tbl_ncar WHERE file_path = ?");
        $checkStmt->execute([$relativePath]);

        if ($checkStmt->fetchColumn() === false) {
            // If no record exists, insert a new one.
            $stmt = $pdo->prepare("INSERT INTO tbl_ncar (file_path, status) VALUES (?, ?)");
            $stmt->execute([$relativePath, 'Unresolved']);
        }
        // If a record already exists, do nothing. The PDF file has been overwritten,
        // and we don't want to create a duplicate database entry.

    } catch (PDOException $e) {
        // Log this DB error, but don't fail the whole operation since the PDF was created.
        error_log("Database error inserting NCAR report path: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'filePath' => $relativePath]);
} catch (Exception $e) {
    error_log("NCAR PDF Generation Failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
