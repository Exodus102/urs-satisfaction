<?php
// --- Enhanced Error Reporting for Debugging ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set header to JSON, as this script will now respond with a status message.
header('Content-Type: application/json');

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
    echo json_encode(['success' => false, 'message' => 'Missing required parameters. Please ensure you are logged in and all report criteria are selected.']);
    exit;
}

// --- Main PDF Generation Logic with Error Handling ---
try {
    // --- FPDF Custom Class ---
    class PDF extends FPDF
    {
        public $user_campus = '';
        public $quarter_display = '';
        public $year = '';

        // Add public getters for protected properties to avoid direct access errors.
        public function getPageBreakTrigger()
        {
            return $this->PageBreakTrigger;
        }

        public function getCurOrientation()
        {
            return $this->CurOrientation;
        }

        // Page header
        function Header()
        {
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
            // Set font for the first line
            $this->SetFont('Arial', '', 10);
            // Add the cell, 0 width means it spans the page, 1 means new line after, 'C' is for center.
            $this->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');

            // Set font for the main title
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, 'UNIVERSITY OF RIZAL SYSTEM', 0, 1, 'C');

            // Set font for the third line
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Province of Rizal', 0, 1, 'C');

            // Draw a line below the header
            $this->Ln(5); // Add a smaller space before the line
            $this->SetLineWidth(0.5); // Make the line bold
            $y = $this->GetY();
            $this->Line($this->lMargin, $y, $this->GetPageWidth() - $this->rMargin, $y);
            $this->SetLineWidth(0.2); // Reset line width to default for other elements

            // Line break
            $this->Ln(15);
        }

        // Page footer
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

        // Function to calculate the number of lines a MultiCell will take
        // This is a common and necessary helper function for FPDF when dealing with dynamic row heights.
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
                        if ($i == $j) {
                            $i++;
                        }
                    } else {
                        $i = $sep + 1;
                    }
                    $sep = -1;
                    $j = $i;
                    $l = 0;
                    $nl++;
                } else {
                    $i++;
                }
            }
            return $nl;
        }
    }

    // --- End of PDF Class ---

    // --- Helper function for Verbal Interpretation ---
    function getVerbalInterpretation($mean)
    {
        if ($mean >= 4.50) {
            return 'E';
        } elseif ($mean >= 3.50) {
            return 'VS';
        } elseif ($mean >= 2.50) {
            return 'S';
        } elseif ($mean >= 1.50) {
            return 'US';
        } elseif ($mean >= 1.00) {
            return 'P/NI';
        }
        return ''; // Return empty if mean is 0 or out of range
    }

    // --- Helper function to draw summary rows (Average, Grand Mean) ---
    function drawSummaryRow($pdf, $label, $average, $col1_width, $col2_width, $col3_width, $col4_width)
    {
        $vi = getVerbalInterpretation($average);
        $pdf->SetFont('Arial', 'B', 11);

        // Check for page break
        if ($pdf->GetY() + 6 > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage($pdf->getCurOrientation());
        }

        $pdf->Cell($col1_width, 6, '', 1, 0, 'C'); // Use full border
        $pdf->Cell($col2_width, 6, $label, 1, 0, 'C'); // Center the label and use full border
        $pdf->Cell($col3_width, 6, number_format($average, 2), 1, 0, 'C');
        $pdf->Cell($col4_width, 6, $vi, 1, 1, 'C');
        $pdf->SetFont('Arial', '', 11); // Reset font
    }

    // --- PDF Generation ---

    // Instanciation of inherited class
    $pdf = new PDF();
    $pdf->SetMargins(23, 23, 23); // Set 23mm margins (left, top, right)
    $pdf->SetAutoPageBreak(true, 23); // Set 23mm bottom margin for page breaks
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Times', '', 12);

    // --- Set dynamic data for the footer ---
    $pdf->user_campus = $user_campus;
    $pdf->year = $year;

    // Determine the quarter text based on the quarter number for the footer
    $quarter_text_for_footer = '';
    switch ($quarter) {
        case 1:
            $quarter_text_for_footer = "January to March";
            break;
        case 2:
            $quarter_text_for_footer = "April to June";
            break;
        case 3:
            $quarter_text_for_footer = "July to September";
            break;
        case 4:
            $quarter_text_for_footer = "October to December";
            break;
    }
    $pdf->quarter_display = $quarter_text_for_footer;

    // Fetch office name
    $office_data = ['unit_name' => 'N/A', 'campus_name' => $user_campus];
    try {
        $stmt = $pdo->prepare("SELECT unit_name, campus_name FROM tbl_unit WHERE id = ?");
        $stmt->execute([$unit_id]);
        $fetched_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetched_data) {
            $office_data = $fetched_data;
        }
    } catch (PDOException $e) {
        // Log the database error but don't stop the whole script
        error_log("Database error fetching office name: " . $e->getMessage());
    }

    // Determine the quarter text based on the quarter number
    $quarter_text = '';
    switch ($quarter) {
        case 1:
            $quarter_text = "January to March";
            break;
        case 2:
            $quarter_text = "April to June";
            break;
        case 3:
            $quarter_text = "July to September";
            break;
        case 4:
            $quarter_text = "October to December";
            break;
    }
    $period_display = "$quarter_text $year";

    // Display Office and Period information
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(20, 7, 'Office:', 0, 0);
    $pdf->SetFont('Arial', 'BU', 12);
    $pdf->Cell(0, 7, $office_data['unit_name'], 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(20, 7, '', 0, 0); // Add an empty cell for indentation
    $pdf->Cell(0, 7, $period_display, 0, 1);
    $pdf->Ln(10);

    // --- Fetch and Calculate Mean for all relevant questions ---
    $means = [];
    try {
        // This optimized query calculates the mean for each question directly in the database.
        // This corrected query uses subqueries to first find the relevant response_ids
        // and then calculates the average from those submissions. This version uses JOINs for clarity and performance.
        $sql_means = "
            SELECT
                r.question_id,
                AVG(CAST(r.response AS DECIMAL(10,2))) AS mean_value
            FROM
                tbl_responses r
            JOIN
                (SELECT response_id FROM tbl_responses WHERE question_id = -3 AND response = :office_name_param) AS office_responses ON r.response_id = office_responses.response_id
            JOIN
                (SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :office_campus_param) AS campus_responses ON r.response_id = campus_responses.response_id
            WHERE
                r.question_rendering IN ('QoS', 'Su')
                AND r.response REGEXP '^[0-9\\\\.]+$'
                AND YEAR(r.timestamp) = :year AND QUARTER(r.timestamp) = :quarter
            GROUP BY
                r.question_id
        ";
        $stmt_means = $pdo->prepare($sql_means);
        $params = [
            ':office_name_param' => $office_data['unit_name'],
            ':year' => $year,
            ':quarter' => $quarter,
            ':office_campus_param' => $office_data['campus_name']
        ];
        $stmt_means->execute($params);

        // Fetch the results directly into the $means array.
        // PDO::FETCH_KEY_PAIR uses the first column as the key and the second as the value.
        $means = $stmt_means->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Database error fetching means for report: " . $e->getMessage());
        // In case of an error, the $means array will be empty, and the report will show 0.00, which is acceptable.
    }

    // --- Fetch Questions for the report body ---
    $questions = [];
    try {
        // 1. Find the active survey name
        $stmt_active_survey = $pdo->query("SELECT DISTINCT question_survey FROM tbl_questionaire WHERE status = 1 LIMIT 1");
        $active_survey_name = $stmt_active_survey->fetchColumn();

        if ($active_survey_name) {
            // 2. Fetch questions from the active survey with specific ordering
            $sql = "SELECT question_id, question, question_type, question_rendering, header FROM tbl_questionaire 
                    WHERE question_survey = ? 
                    AND status = 1 
                    AND (question_rendering IN ('QoS', 'Su') OR header = 1)
                    ORDER BY 
                        CASE 
                            WHEN question_rendering = 'QoS' THEN 1
                            WHEN question_rendering = 'Su' THEN 2
                            WHEN header = 1 THEN 3
                            ELSE 4
                        END, 
                        question_id ASC";
            $stmt_questions = $pdo->prepare($sql);
            $stmt_questions->execute([$active_survey_name]);
            $questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Database error fetching questions for report: " . $e->getMessage());
    }

    // --- Fetch Comments for the report ---
    $comments = [];
    try {
        $sql_comments = "
            SELECT DISTINCT r.comment
            FROM tbl_responses r
            JOIN
                (SELECT response_id FROM tbl_responses WHERE question_id = -3 AND response = :office_name_param) AS office_responses ON r.response_id = office_responses.response_id
            JOIN
                (SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :office_campus_param) AS campus_responses ON r.response_id = campus_responses.response_id
            WHERE
                YEAR(r.timestamp) = :year AND QUARTER(r.timestamp) = :quarter
                AND r.comment IS NOT NULL AND TRIM(r.comment) != ''
        ";
        $stmt_comments = $pdo->prepare($sql_comments);
        $stmt_comments->execute([
            ':office_name_param' => $office_data['unit_name'],
            ':office_campus_param' => $office_data['campus_name'],
            ':year' => $year,
            ':quarter' => $quarter
        ]);
        $comments = $stmt_comments->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Database error fetching comments for report: " . $e->getMessage());
    }

    // --- Create a four-column table with specific widths and headers ---
    $pdf->SetFont('Arial', 'B', 11); // Set font for table header

    // Define column widths. Usable width = 164mm.
    $col1_width = 10;  // For "Item No."
    $col3_width = 20;  // For "Mean"
    $col4_width = 20;  // For "VI"
    $col2_width = 164 - $col1_width - $col3_width - $col4_width; // For "Question" (114mm)

    // Table Header
    $pdf->Cell($col1_width, 6, '', 1, 0, 'C');
    $pdf->Cell($col2_width, 6, '', 1, 0, 'C');
    $pdf->Cell($col3_width, 6, 'Mean', 1, 0, 'C');
    $pdf->Cell($col4_width, 6, 'VI', 1, 1, 'C'); // The '1' at the end creates a new line

    // Table Body with Questions
    $pdf->SetFont('Arial', '', 11); // Reset font for the body

    // --- Variables for calculating averages ---
    $qos_means = [];
    $su_means = [];
    $current_rendering_group = null;

    if (!empty($questions)) {
        foreach ($questions as $question) {
            $question_rendering = $question['question_rendering'];

            // --- Check if the rendering group has changed to print the average ---
            if ($current_rendering_group === 'QoS' && $question_rendering !== 'QoS') {
                $qos_average = !empty($qos_means) ? array_sum($qos_means) / count($qos_means) : 0;
                drawSummaryRow($pdf, 'Average for QoS', $qos_average, $col1_width, $col2_width, $col3_width, $col4_width);
            }
            // Also check for 'Su' group change, in case there are other groups later
            if ($current_rendering_group === 'Su' && $question_rendering !== 'Su') {
                $su_average = !empty($su_means) ? array_sum($su_means) / count($su_means) : 0;
                drawSummaryRow($pdf, 'Average for Su', $su_average, $col1_width, $col2_width, $col3_width, $col4_width);
            }
            $current_rendering_group = $question_rendering;

            $question_type = $question['question_type'];
            $question_text = $question['question'];
            $question_id = $question['question_id'];
            // Get the calculated mean for this question, default to 0 if not found
            $mean_value = $means[$question_id] ?? 0;

            // Determine if the question is computable (not Text or Description)
            $is_computable = !in_array($question_type, ['Text', 'Description']);

            // Only store the mean for average calculation if it's a computable type
            if ($is_computable && $mean_value > 0) {
                if ($question_rendering === 'QoS') {
                    $qos_means[] = $mean_value;
                }
                if ($question_rendering === 'Su') {
                    $su_means[] = $mean_value;
                }
            }

            // Set display values based on whether the question is computable
            $display_mean = $is_computable ? number_format($mean_value, 2) : '';
            $verbal_interpretation = $is_computable ? getVerbalInterpretation($mean_value) : '';

            // Calculate the number of lines the question will take to auto-adjust row height
            $line_count = $pdf->NbLines($col2_width, $question_text);
            $row_height = 6 * $line_count; // Adjust height based on line count

            // Check if the new row will overflow the page.
            if ($pdf->GetY() + $row_height > $pdf->getPageBreakTrigger()) {
                $pdf->AddPage($pdf->getCurOrientation()); // Add a new page

                // --- Re-draw the table header on the new page ---
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell($col1_width, 6, '', 1, 0, 'C');
                $pdf->Cell($col2_width, 6, '', 1, 0, 'C');
                $pdf->Cell($col3_width, 6, 'Mean', 1, 0, 'C');
                $pdf->Cell($col4_width, 6, 'VI', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 11); // Reset font for the body
            }

            // Draw cells for the row
            // Store current position
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Use MultiCell for the question to handle wrapping
            $pdf->Cell($col1_width, $row_height, '', 1, 0, 'C');
            $pdf->MultiCell($col2_width, 6, $question_text, 1, 'L');

            // After a MultiCell, the cursor moves to the next line. We must reposition it
            // to the correct X and Y to continue the row. The X position is the start
            // of the row plus the width of the first two columns.
            $pdf->SetXY($x + $col1_width + $col2_width, $y);
            $pdf->Cell($col3_width, $row_height, $display_mean, 1, 0, 'C'); // Mean
            $pdf->Cell($col4_width, $row_height, $verbal_interpretation, 1, 1, 'C'); // VI
        }
    }

    // --- Draw final summary rows after the loop ---

    // This handles the case where the last processed group was 'QoS' or 'Su'.
    if ($current_rendering_group === 'QoS') {
        $qos_average = !empty($qos_means) ? array_sum($qos_means) / count($qos_means) : 0;
        drawSummaryRow($pdf, 'Average for QoS', $qos_average, $col1_width, $col2_width, $col3_width, $col4_width);
    } elseif ($current_rendering_group === 'Su') {
        $su_average = !empty($su_means) ? array_sum($su_means) / count($su_means) : 0;
        drawSummaryRow($pdf, 'Average for Su', $su_average, $col1_width, $col2_width, $col3_width, $col4_width);
    }

    // Draw Grand Mean
    $all_means = array_merge($qos_means, $su_means);
    $grand_mean = !empty($all_means) ? array_sum($all_means) / count($all_means) : 0;
    drawSummaryRow($pdf, 'Grand Mean', $grand_mean, $col1_width, $col2_width, $col3_width, $col4_width);

    // --- Display Comments Section ---
    $pdf->Ln(10); // Add more space
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 5, 'Comments:', 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('Arial', '', 11);
    if (!empty($comments)) {
        $comment_number = 1;
        foreach ($comments as $comment) {
            // Decode entities for PDF output and remove extra whitespace
            $clean_comment = trim(html_entity_decode($comment, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            // Calculate height and check for page break
            $line_count = $pdf->NbLines(0, $comment_number . '. ' . $clean_comment);
            $cell_height = 5 * $line_count;

            if ($pdf->GetY() + $cell_height > $pdf->getPageBreakTrigger()) {
                $pdf->AddPage($pdf->getCurOrientation());
            }

            $pdf->MultiCell(0, 5, $comment_number . '. ' . $clean_comment, 0, 'L');
            $pdf->Ln(2); // Space between comments
            $comment_number++;
        }
    } else {
        $pdf->Cell(0, 5, 'None', 0, 1, 'L');
    }

    // --- Save and Output PDF ---

    // 1. Get the PDF content as a string.
    // The 'S' parameter returns the document as a string without outputting it.
    $pdfContent = $pdf->Output('S');

    // 2. Define the file path for saving.
    // Sanitize campus and unit names for the filename by replacing spaces and invalid characters with hyphens.
    $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $user_campus);
    $safe_unit_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $office_data['unit_name']);

    // Construct the new, more descriptive filename.
    $filename = "report_{$safe_campus_name}_{$safe_unit_name}_{$year}_q{$quarter}.pdf";
    $savePath = __DIR__ . '/../../upload/pdf/' . $filename;

    // 3. Ensure the destination directory exists.
    $directory = dirname($savePath);
    if (!is_dir($directory)) {
        // Create the directory recursively with safe permissions (0755).
        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new Exception('Failed to create PDF storage directory. Please check server permissions for the "upload" folder.');
        }
    }

    // 4. Save the PDF content to the file on the server.
    if (file_put_contents($savePath, $pdfContent) === false) {
        throw new Exception('Failed to save the PDF file. Please check server permissions for the "upload/pdf" folder.');
    }

    // 5. Insert the file path into the database.
    // We'll store the relative path from the project root for better portability.
    $relativePath = 'upload/pdf/' . $filename;
    try {
        $stmt = $pdo->prepare("INSERT INTO tbl_report (file_path) VALUES (?)");
        $stmt->execute([$relativePath]);
    } catch (PDOException $e) {
        // Log this DB error, but don't fail the whole operation since the PDF was created.
        error_log("Database error inserting report path: " . $e->getMessage());
    }

    // 6. If we reach here, it was successful. Send a success response.
    $response_data = [
        'success' => true,
        'message' => 'PDF report created successfully!',
        'filePath' => $relativePath // Return the path to the generated file
    ];

    echo json_encode($response_data);
} catch (Exception $e) {
    // If any error occurs, catch it and send a JSON error response.
    $errorMessage = "An error occurred while generating the PDF: " . $e->getMessage();

    // Log the detailed error to the server's error log for the developer
    error_log("PDF Generation Failed: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

    // Send a user-friendly error message back to the JavaScript
    http_response_code(500);
    $error_response = ['success' => false, 'message' => $errorMessage];
    echo json_encode($error_response);
}
