<?php
// --- SETUP ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../fpdf186/fpdf.php';
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- PARAMETER VALIDATION ---
$year = $_GET['year'] ?? null;
$quarter = $_GET['quarter'] ?? null;
$month = $_GET['month'] ?? null;
$user_campus = $_SESSION['user_campus'] ?? null;

if (!$year || (!$quarter && !$month) || !$user_campus) {
    http_response_code(400);
    echo "<div class='p-4 text-red-500'>Error: Missing required parameters (year, quarter/month, or campus).</div>";
    exit;
}

try {
    // --- FPDF Custom Class ---
    class PDF extends FPDF
    {
        public $user_campus = '';
        public $quarter_display = '';
        public $year = '';

        // Add public getters for protected properties to avoid direct access errors.
        public function getTMargin()
        {
            return $this->tMargin;
        }

        public function getBMargin()
        {
            return $this->bMargin;
        }

        public function getPageBreakTrigger()
        {
            return $this->PageBreakTrigger;
        }

        public function getCurOrientation()
        {
            return $this->CurOrientation;
        }

        // Function to calculate the number of lines a MultiCell will take
        function NbLines($w, $txt)
        {
            // Computes the number of lines a MultiCell of width w will take
            if (!isset($this->CurrentFont)) {
                $this->Error('No font has been set');
            }
            $cw = &$this->CurrentFont['cw'];
            if ($w == 0) {
                $w = $this->w - $this->rMargin - $this->x;
            }
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s = str_replace("\r", '', $txt);
            $nb = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n") {
                $nb--;
            }
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
                if ($c == ' ') {
                    $sep = $i;
                }
                $l += $cw[$c];
                if ($l > $wmax) {
                    if ($sep == -1) {
                        if ($i == $j)
                            $i++;
                    } else
                        $i = $sep + 1;
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                } else
                    $i++;
            }
            return $nl;
        }

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
            // Position at 2.2 cm from the bottom to accommodate the line and text
            $this->SetY(-22);

            // Draw a line above the footer text, similar to the header
            $this->SetLineWidth(0.5); // Make the line bold
            $y = $this->GetY();
            $this->Line($this->lMargin, $y, $this->GetPageWidth() - $this->rMargin, $y);
            $this->SetLineWidth(0.2); // Reset line width to default
            $this->Ln(2); // Add a small space after the line


            // Set font for the first line of the footer
            $this->SetFont('Arial', 'B', 10);
            // Display dynamic campus info in uppercase
            $this->Cell(0, 5, 'URS ' . strtoupper($this->user_campus) . ' CAMPUS', 0, 1, 'R');

            // Display dynamic quarter and year info on the second line
            $this->Cell(0, 5, $this->quarter_display . ' ' . $this->year, 0, 1, 'R');

            // Set font for the third line of the footer
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 5, 'Customer Satisfaction Survey', 0, 1, 'R');
        }
    }

    // --- PDF GENERATION ---
    $pdf = new PDF('P', 'mm', 'A4'); // Portrait mode
    $pdf->SetMargins(23, 23, 23);
    $pdf->SetAutoPageBreak(true, 23);
    $pdf->AliasNbPages();

    // Set dynamic data for header/footer
    $pdf->user_campus = $user_campus;
    $pdf->year = $year;

    // Determine the quarter text based on the quarter number for the footer
    $period_text_for_footer = '';
    if ($quarter) {
        switch ($quarter) {
            case 1:
                $period_text_for_footer = "January to March";
                break;
            case 2:
                $period_text_for_footer = "April to June";
                break;
            case 3:
                $period_text_for_footer = "July to September";
                break;
            case 4:
                $period_text_for_footer = "October to December";
                break;
        }
    } elseif ($month) {
        $dateObj = DateTime::createFromFormat('!m', $month);
        $period_text_for_footer = $dateObj->format('F'); // Full month name, e.g., "January"
    }

    // This variable is used by the included files
    $quarter_text_for_footer = $period_text_for_footer;
    $pdf->quarter_display = $period_text_for_footer;

    $pdf->AddPage();

    include 'title.php';

    // Add a new page for the content that will follow the title page.
    $pdf->AddPage();

    include 'content.php';

    $pdf->AddPage();

    include 'range-table.php';

    $pdf->AddPage();

    include 'table-result.php';

    include 'table-office.php';

    // --- SAVE AND PREPARE FOR DISPLAY ---
    $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $user_campus);
    if ($quarter) {
        $filename = "tally-report_{$safe_campus_name}_{$year}_q{$quarter}.pdf";
    } else {
        $filename = "tally-report_{$safe_campus_name}_{$year}_m{$month}.pdf";
    }

    $savePath = __DIR__ . '/../../upload/pdf/' . $filename;

    // Ensure the destination directory exists
    $directory = dirname($savePath);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new Exception('Failed to create PDF storage directory.');
        }
    }

    // Save the PDF to the server
    $pdf->Output('F', $savePath);

    // The relative path from the project root for the URL
    $relativePath = 'upload/pdf/' . $filename;

    // --- Save file path to database ---
    try {
        $stmt = $pdo->prepare("INSERT INTO tbl_tally_report (file_path, timestamp) VALUES (?, NOW())");
        $stmt->execute([$relativePath]);
    } catch (PDOException $e) {
        // For debugging, display the error. In production, you might just log it.
        echo "<div class='p-4 mb-4 bg-red-100 text-red-800 border border-red-300 rounded-lg'>";
        echo "<strong>Database Error:</strong> Could not save the report path. Please check the following:<br>";
        echo "<ul class='list-disc list-inside ml-4 mt-2'>";
        echo "<li>Ensure the table `tbl_report_tally` exists.</li>";
        echo "<li>Ensure it has the columns `id` (auto-increment), `file_path`, and `timestamp`.</li>";
        echo "</ul>";
        echo "<p class='mt-2'><strong>Specific Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    // Add a unique timestamp as a query parameter to prevent browser caching issues.
    $pdf_url = '../../' . htmlspecialchars($relativePath) . '?v=' . time();
} catch (Exception $e) {
    http_response_code(500);
    // Log the detailed error for debugging
    error_log("PDF Generation Failed for tally report: " . $e->getMessage());
    // Display a user-friendly error
    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>An error occurred while generating the report. Please contact support.</div>";
    exit;
}

?>

<!-- HTML to embed and display the generated PDF -->
<div class="bg-white rounded-lg shadow-md h-[80vh]">
    <object data="<?php echo $pdf_url; ?>" type="application/pdf" width="100%" height="100%">
        <div class="p-4">
            <p class="text-red-500">Your browser does not support embedded PDFs.</p>
            <a href="<?php echo $pdf_url; ?>" target="_blank" class="text-blue-600 hover:underline">
                Click here to download or view the report.
            </a>
        </div>
    </object>
</div>