<?php
// Turn off error displaying to keep JSON clean, but log errors
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Use absolute paths for reliability
require_once __DIR__ . '/../../fpdf186/fpdf.php';
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Basic security check
if (!isset($_SESSION['user_campus'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$response_id = $_GET['response_id'] ?? null;

if (!$response_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Response ID is required.']);
    exit;
}

try {
    // --- Fetch all data for the response ID ---
    $stmt_responses = $pdo->prepare("SELECT * FROM tbl_responses WHERE response_id = ? ORDER BY question_id ASC");
    $stmt_responses->execute([$response_id]);
    $responses_raw = $stmt_responses->fetchAll(PDO::FETCH_ASSOC);

    $stmt_details = $pdo->prepare("SELECT * FROM tbl_detail WHERE response_id = ?");
    $stmt_details->execute([$response_id]);
    $details = $stmt_details->fetch(PDO::FETCH_ASSOC);

    if (empty($responses_raw) || !$details) {
        echo json_encode(['success' => false, 'message' => 'No complete data found for this response.']);
        exit;
    }

    // --- Process raw data into a structured format ---
    $response_data = [
        'id' => $response_id,
        'timestamp' => '',
        'comment' => '',
        'analysis' => '',
        'campus' => '',
        'division_name' => '',
        'unit_name' => '',
        'customer_type' => '',
        'responses' => [],
    ];

    foreach ($responses_raw as $row) {
        $response_data['timestamp'] = $row['timestamp']; // Keep updating, it's the same for all
        $response_data['comment'] = $row['comment'];
        $response_data['analysis'] = $row['analysis'];

        switch ($row['question_id']) {
            case -1:
                $response_data['campus'] = $row['response'];
                break;
            case -2:
                $response_data['division_name'] = $row['response'];
                break;
            case -3:
                $response_data['unit_name'] = $row['response'];
                break;
            case -4:
                $response_data['customer_type'] = $row['response'];
                break;
            default:
                if ($row['question_id'] > 0) {
                    $response_data['responses'][$row['question_id']] = $row['response'];
                }
                break;
        }
    }

    // --- FPDF Custom Class ---
    class PDF extends FPDF
    {
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
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }

        function ChapterTitle($label, $value)
        {
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(40, 6, $label, 0, 0, 'L');
            $this->SetFont('Arial', '', 11);
            // Ensure value is a string and decode for FPDF
            $str = (string)$value;
            $decoded = function_exists('mb_convert_encoding') ? mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8') : utf8_decode($str);
            $this->MultiCell(0, 6, $decoded, 0, 'L');
            $this->Ln(1);
        }

        public function getRMargin()
        {
            return $this->rMargin;
        }

        public function getPageBreakTrigger()
        {
            return $this->PageBreakTrigger;
        }

        public function getCurOrientation()
        {
            return $this->CurOrientation;
        }

        // Computes the number of lines a MultiCell of width w will take
        function NbLines($w, $txt)
        {
            $cw = &$this->CurrentFont['cw'];
            if ($w == 0)
                $w = $this->w - $this->rMargin - $this->x;
            $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
            $s = str_replace("\r", '', $txt);
            $nb = strlen($s);
            if ($nb > 0 && $s[$nb - 1] == "\n")
                $nb--;
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
                if ($c == ' ')
                    $sep = $i;
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

        function decodeText($text)
        {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
            }
            return utf8_decode($text);
        }

        function WriteHTMLTable($html)
        {
            $dom = new DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $table = $dom->getElementsByTagName('table')->item(0);
            if (!$table) return;

            $availableWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
            $colWidths = [$availableWidth * 0.3, $availableWidth * 0.7];

            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $row) {
                $headerCells = $row->getElementsByTagName('th');
                $isHeader = $headerCells->length > 0;
                $cells = $isHeader ? $headerCells : $row->getElementsByTagName('td');
                if ($cells->length < 2) continue;

                $nb = 0;
                for ($i = 0; $i < $cells->length; $i++) {
                    $nb = max($nb, $this->NbLines($colWidths[$i], $this->decodeText($cells->item($i)->nodeValue)));
                }
                $h = 5 * $nb;

                if ($this->GetY() + $h > $this->PageBreakTrigger) $this->AddPage($this->CurOrientation, $this->CurPageSize);

                $y_before = $this->GetY();
                for ($i = 0; $i < $cells->length; $i++) {
                    $this->SetFont('Arial', $isHeader ? 'B' : '', 9);
                    $this->MultiCell($colWidths[$i], $h, $this->decodeText($cells->item($i)->nodeValue), 1, 'L');
                    $this->SetXY($this->GetX() + $colWidths[$i], $y_before);
                }
                $this->Ln($h);
            }
        }
    }

    // --- PDF Generation ---
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Sentiment Analysis Report', 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Response Details', 0, 1, 'L');
    $pdf->SetLineWidth(0.5);
    $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetPageWidth() - $pdf->getRMargin(), $pdf->GetY());
    $pdf->Ln(4);

    // $pdf->ChapterTitle('Response ID:', $response_data['id']);
    $pdf->ChapterTitle(
        'Timestamp:',
        $response_data['timestamp']
            ? (new DateTime($response_data['timestamp']))->format('F j, Y')
            : 'N/A'
    );
    $pdf->ChapterTitle('Campus:', $response_data['campus']);
    $pdf->ChapterTitle('Office:', $response_data['unit_name']);
    $pdf->ChapterTitle('Customer:', $response_data['customer_type']);
    $pdf->ChapterTitle('Comment:', $response_data['comment']);
    $pdf->ChapterTitle('Analysis:', $response_data['analysis']);

    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Sentiment Analysis', 0, 1, 'L');
    $pdf->SetLineWidth(0.5);
    $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetPageWidth() - $pdf->getRMargin(), $pdf->GetY());
    $pdf->Ln(4);

    $sentiment_details = $details['sentiment_details'];
    if (strpos(trim($sentiment_details), '<table') === 0) {
        $pdf->WriteHTMLTable($sentiment_details);
    } else {
        $sentiment_json = json_decode($sentiment_details, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $col1Width = 45;

            foreach ($sentiment_json as $key => $value) {
                // Manual page break check, leave some margin at bottom
                if ($pdf->GetY() > $pdf->getPageBreakTrigger() - 40) {
                    $pdf->AddPage($pdf->getCurOrientation());
                }

                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell($col1Width, 6, $pdf->decodeText($key . ':'), 0, 0, 'L');

                if (is_array($value) || is_object($value)) {
                    $value_str = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $pdf->SetFont('Courier', '', 9);
                    $line_height = 5;
                } else {
                    $value_str = (string)$value;
                    $pdf->SetFont('Arial', '', 10);
                    $line_height = 6;
                }

                $pdf->MultiCell(0, $line_height, $pdf->decodeText($value_str), 0, 'L');
                $pdf->Ln(4);
            }
        } else {
            // Fallback for non-JSON or malformed JSON
            $pdf->SetFont('Courier', '', 9);
            $str = (string)$sentiment_details;
            $decoded_str = $pdf->decodeText($str);
            $pdf->MultiCell(0, 5, $decoded_str);
        }
    }

    // --- Save PDF ---
    $upload_dir = __DIR__ . '/../../upload/pdf/sentiment-reports/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $upload_dir));
        }
    }
    $filename = "sentiment_report_{$response_id}_" . time() . ".pdf";
    $filepath = $upload_dir . $filename;
    $pdf->Output('F', $filepath);

    // --- Return JSON Response ---
    $relative_path = 'upload/pdf/sentiment-reports/' . $filename;
    echo json_encode(['success' => true, 'filePath' => $relative_path]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Error generating sentiment report: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred while generating the report. Details: ' . $e->getMessage()]);
}
