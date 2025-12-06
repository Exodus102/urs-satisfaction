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

try {
    // --- FPDF Custom Class ---
    class PDF extends FPDF
    {
        public $period_display; // Add this property
        public $user_campus;    // Also add user_campus for consistency

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

        // Page header
        function Header()
        {
            // --- Dynamic Logo and Text Centering ---
            $logo1Path = '../../resources/img/urs-logo.png';
            $logo2Path = '../../resources/img/tuvr-urs-logo-mark.jpg';
            $logo1Width = 15;
            $logo2Width = 28; // Kept the width
            $logoGap = 10; // Increased gap

            // Set font to calculate the width of the main title, which is the widest part of the text block.
            $this->SetFont('Arial', 'B', 12);
            $titleWidth = $this->GetStringWidth('University of Rizal System');

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
            $this->Cell(0, 6, 'University of Rizal System', 0, 1, 'C');

            // Set font for the third line
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Province of Rizal', 0, 1, 'C');

            // Draw a line below the header
            $this->Ln(5); // Add a smaller space before the line
            $this->SetLineWidth(0.5); // Make the line bold
            $y = $this->GetY();
            $this->Line($this->lMargin, $y, $this->GetPageWidth() - $this->rMargin, $y);
            $this->SetLineWidth(0.2); // Reset line width to default for other elements
            $this->Ln(5); // Add a small space after the line
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
            $this->Cell(0, 5, $this->period_display, 0, 1, 'R');

            // Set font for the third line of the footer
            $this->SetFont('Arial', 'I', 10);
            $this->Cell(0, 5, 'Customer Satisfaction Survey', 0, 1, 'R');
        }
    }

    // --- 1. Get Filter Values & Determine View Mode ---
    $user_campus = $_SESSION['user_campus'] ?? null;

    // Get filter values from URL, providing sensible defaults
    $filter_campus_id = !empty($_GET['filter_campus']) ? $_GET['filter_campus'] : null;
    $filter_division_id = !empty($_GET['filter_division']) ? $_GET['filter_division'] : null;
    $filter_year = !empty($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
    $filter_quarter = !empty($_GET['filter_quarter']) ? $_GET['filter_quarter'] : null;
    $filter_month = !empty($_GET['filter_month']) ? $_GET['filter_month'] : null;
    $filter_unit_id = !empty($_GET['unit_id']) ? $_GET['unit_id'] : null;

    // --- Fetch selected office/division name for display ---
    $selected_office_name = "All Offices"; // Default text
    if ($filter_unit_id) {
        try {
            $stmt_office = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE id = ?");
            $stmt_office->execute([$filter_unit_id]);
            $fetched_name = $stmt_office->fetchColumn();
            if ($fetched_name) {
                $selected_office_name = $fetched_name;
            } else {
                $selected_office_name = "Unknown Office (ID: $filter_unit_id)";
            }
        } catch (PDOException $e) {
            error_log("Error fetching office name for PDF: " . $e->getMessage());
            $selected_office_name = "Error fetching office name";
        }
    } elseif ($filter_division_id) {
        $stmt_div = $pdo->prepare("SELECT division_name FROM tbl_division WHERE id = ?");
        $stmt_div->execute([$filter_division_id]);
        $division_name = $stmt_div->fetchColumn();
        $selected_office_name = "All Offices in " . ($division_name ?: 'Unknown Division');
    }

    // Determine the view mode and set up table headers
    $view_mode = 'year';
    $column_headers = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $period_display_text = $filter_year; // Default to just the year
    $certification_period_text = $filter_year; // Also default the certification text

    if ($filter_quarter) {
        $view_mode = 'quarter';
        $quarter_months_map = [
            1 => ['months' => [1, 2, 3], 'text' => 'January to March', 'cert_text' => '1st Quarter'],
            2 => ['months' => [4, 5, 6], 'text' => 'April to June', 'cert_text' => '2nd Quarter'],
            3 => ['months' => [7, 8, 9], 'text' => 'July to September', 'cert_text' => '3rd Quarter'],
            4 => ['months' => [10, 11, 12], 'text' => 'October to December', 'cert_text' => '4th Quarter']
        ];

        $months_in_quarter = $quarter_months_map[$filter_quarter]['months'];

        // This format is for the footer and main title (e.g., "October to December 2024")
        $period_display_text = $quarter_months_map[$filter_quarter]['text'] . ' ' . $filter_year;

        // This format is ONLY for the certification page (e.g., "4th Quarter of AY 2024")
        $certification_period_text = $quarter_months_map[$filter_quarter]['cert_text'] . ' of AY ' . $filter_year;

        $column_headers = [];
        foreach ($months_in_quarter as $month_num) {
            $column_headers[] = date('M', mktime(0, 0, 0, $month_num, 1));
        }
        $filter_month = null; // Quarter takes precedence

    } elseif ($filter_month) {
        $view_mode = 'month';
        $month_name = date('F', mktime(0, 0, 0, $filter_month, 1));
        $column_headers = [$month_name];
        $period_display_text = $month_name . ' ' . $filter_year;
        $certification_period_text = $period_display_text; // For month view, they are the same
    }

    // --- PDF Generation ---
    $pdf = new PDF('P', 'mm', 'A4'); // Portrait mode
    $pdf->SetMargins(15, 23, 15);
    $pdf->SetAutoPageBreak(true, 23);
    $pdf->AliasNbPages();

    // Set dynamic data for the footer
    $pdf->user_campus = $user_campus;
    $pdf->period_display = $period_display_text;

    $pdf->AddPage();

    // --- Determine Report Title ---
    $report_title = 'CSS ANNUAL RESULTS'; // Default for year view
    if ($view_mode === 'quarter') {
        $report_title = 'CSS QUARTERLY RESULTS';
    } elseif ($view_mode === 'month') {
        $report_title = 'CSS MONTHLY RESULTS';
    }

    // --- Title ---
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, $report_title, 0, 1, 'C');
    $pdf->Ln(5);

    // Display Office and Period information
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(20, 7, 'Office:', 0, 0, 'L');
    $pdf->SetFont('Arial', 'BU', 12);
    $pdf->Cell(0, 7, $selected_office_name, 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(20, 7, '', 0, 0, 'L');
    $pdf->Cell(20, 5, $period_display_text, 0, 1, 'L'); // Period text is not underlined for clarity
    $pdf->Ln(10);

    // --- Table Header ---
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(6, 64, 137); // #064089
    $pdf->SetTextColor(255, 255, 255);

    // Define column widths for Portrait. Usable width = 210 - 30 = 180mm
    $num_headers = count($column_headers);
    $usable_width = 180;
    $analysis_col_width = 25; // Define for use in month view

    if ($view_mode === 'year') {
        // For year view, no analysis column
        $office_col_width = 45;
        $other_col_width = ($num_headers > 0) ? ($usable_width - $office_col_width) / $num_headers : 0;
    } elseif ($view_mode === 'month') {
        // For month view, include analysis column
        $remaining_width = $usable_width - $analysis_col_width;
        $office_col_width = $remaining_width * 0.7; // Give office name plenty of space
        $other_col_width = $remaining_width * 0.3; // The single month column
    } else {
        // For quarter view, no analysis column
        $office_col_width = $usable_width * 0.6;
        $other_col_width = ($num_headers > 0) ? ($usable_width * 0.4) / $num_headers : 0;
    }

    $pdf->Cell($office_col_width, 10, 'Office', 1, 0, 'C', true);
    $header_count = count($column_headers);
    foreach ($column_headers as $i => $header) {
        $is_last_data_header = ($i === $header_count - 1);
        $line_break = ($is_last_data_header && $view_mode !== 'month') ? 1 : 0;
        $pdf->Cell($other_col_width, 10, $header, 1, $line_break, 'C', true);
    }
    if ($view_mode === 'month') {
        $pdf->Cell($analysis_col_width, 10, 'Analysis', 1, 1, 'C', true);
    }

    // --- Table Body ---
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);

    // --- Data Fetching Logic (from tally-results.php) ---
    $tally_data = [];
    $target_campus_name = $user_campus;
    if ($filter_campus_id) {
        $stmt_campus = $pdo->prepare("SELECT campus_name FROM tbl_campus WHERE id = ?");
        $stmt_campus->execute([$filter_campus_id]);
        $target_campus_name = $stmt_campus->fetchColumn();
    }

    if ($target_campus_name) {
        $sql_select = "SELECT u.id as unit_id, u.unit_name";
        $sql_from_base = "FROM tbl_unit u";
        $join_conditions = "ON u.unit_name = r.response AND r.question_id = -3";
        $sql_where = "WHERE u.campus_name = ?";
        $sql_group_by = "GROUP BY u.id, u.unit_name";
        $sql_order_by = "ORDER BY u.unit_name ASC";

        $where_params = [$target_campus_name];
        $join_params = [];

        $join_conditions .= " AND YEAR(r.timestamp) = ?";
        $join_params[] = $filter_year;

        if ($view_mode === 'month') {
            $sql_select .= ", COUNT(DISTINCT r.response_id) as count";
            $join_conditions .= " AND MONTH(r.timestamp) = ?";
            $join_params[] = $filter_month;
        } elseif ($view_mode === 'quarter') {
            foreach ($column_headers as $month_abbr) {
                $month_num = date('n', strtotime("1 $month_abbr 2000"));
                $month_alias = strtolower($month_abbr);
                $sql_select .= ", COUNT(DISTINCT CASE WHEN MONTH(r.timestamp) = $month_num THEN r.response_id END) AS {$month_alias}_count";
            }
            $join_conditions .= " AND QUARTER(r.timestamp) = ?";
            $join_params[] = $filter_quarter;
        } else { // 'year' view
            for ($m = 1; $m <= 12; $m++) {
                $month_name = strtolower(date('M', mktime(0, 0, 0, $m, 1)));
                $sql_select .= ", COUNT(DISTINCT CASE WHEN MONTH(r.timestamp) = $m THEN r.response_id END) AS {$month_name}_count";
            }
        }

        if ($filter_division_id) {
            $stmt_div = $pdo->prepare("SELECT division_name FROM tbl_division WHERE id = ?");
            $stmt_div->execute([$filter_division_id]);
            $division_name = $stmt_div->fetchColumn();
            if ($division_name) {
                $sql_where .= " AND u.division_name = ?";
                $where_params[] = $division_name;
            }
        }

        if ($filter_unit_id) {
            $sql_where .= " AND u.id = ?";
            $where_params[] = $filter_unit_id;
        }

        $sql_from = "$sql_from_base LEFT JOIN tbl_responses r $join_conditions";
        $final_sql = "$sql_select $sql_from $sql_where $sql_group_by $sql_order_by";
        $params = array_merge($join_params, $where_params);

        $stmt = $pdo->prepare($final_sql);
        $stmt->execute($params);
        $tally_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Render Table Rows ---
    if (empty($tally_data)) {
        $pdf->Cell(0, 10, 'No data found for the selected period.', 1, 1, 'C');
    } else {
        $certification_details = null; // To store data for the certification page if needed

        foreach ($tally_data as $row) {
            $total_responses = 0;
            if ($view_mode === 'year' || $view_mode === 'quarter') {
                foreach ($column_headers as $header) {
                    $total_responses += (int)$row[strtolower($header) . '_count'];
                }
            } else {
                $total_responses = (int)$row['count'];
            }

            if ($total_responses == 0) $analysis = 'Bad';
            elseif ($total_responses < 10) $analysis = 'Neutral';
            else $analysis = 'Good';

            // If analysis is 'Bad', store the details for later generation
            if ($analysis === 'Bad') {
                $certification_details = $row;
            }

            // --- Dynamic Row Height Calculation ---
            $line_count = $pdf->NbLines($office_col_width, $row['unit_name']);
            $row_height = 6 * $line_count; // Base height of 6mm per line

            // Store current position
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Use MultiCell for the office name to handle wrapping
            $pdf->MultiCell($office_col_width, 6, $row['unit_name'], 1, 'L');

            // Reposition cursor for the next cells
            $pdf->SetXY($x + $office_col_width, $y);

            // Draw the rest of the cells with the calculated row height
            if ($view_mode === 'year' || $view_mode === 'quarter') {
                $data_cell_count = count($column_headers);
                foreach ($column_headers as $i => $header) {
                    $pdf->Cell($other_col_width, $row_height, $row[strtolower($header) . '_count'], 1, ($i === $data_cell_count - 1 ? 1 : 0), 'C');
                }
            } else {
                // Month view
                $pdf->Cell($other_col_width, $row_height, $row['count'], 1, 0, 'C');
                $pdf->Cell($analysis_col_width, $row_height, $analysis, 1, 1, 'C');
            }
        }
    }

    // --- Add Footer Rows to the Table ---
    // This will now always be added to the end of the table on the first page.
    // Blank row
    $pdf->Cell($usable_width, 5, '', 'LTR', 1, 'L');

    // "Printed on" row
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell($usable_width, 5, 'Printed this on: ' . date('d/m/Y h:i A'), 'LRB', 1, 'R');

    // --- Generate Certification Page if Needed ---
    // This is done AFTER the main table and its footer rows are complete.
    if ($certification_details) {
        $pdf->AddPage('P'); // Add a new portrait page
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->Cell(0, 10, 'CERTIFICATION', 0, 1, 'C');

        // --- Print text with mixed styles ---
        // Part 1: Normal text
        $pdf->Ln(10);
        $pdf->SetX(25); // Indent the paragraph
        $pdf->SetFont('Arial', '', 12);
        $pdf->Write(8, "This is to certify that no data was retrieved in ");

        // Part 2: Styled text for "Customer Satisfaction Survey"
        $pdf->SetFont('Arial', 'BI', 12);
        $pdf->Write(8, "Customer Satisfaction Survey");

        // Part 3: Normal text, followed by the styled unit name
        $pdf->SetFont('Arial', '', 12);
        $pdf->Write(8, " for the ");
        $pdf->SetFont('Arial', 'BI', 12);
        $pdf->Write(8, $certification_details['unit_name']);

        // Part 4: The rest of the sentence in normal text
        $pdf->SetFont('Arial', '', 12);
        $pdf->Write(8, " for the " . $certification_period_text . ", thus, no result processed.");
        $pdf->Ln(15); // Add a bit more space for a new paragraph

        // Final paragraph
        $pdf->SetX(25); // Indent the paragraph
        $current_date = date('F j, Y');
        $pdf->MultiCell(0, 8, "This certification was issued on " . $current_date . " for the purpose of ISO Certification.", 0, 'L');
    }

    // --- Output PDF ---
    $pdf->Output('I', 'tally_results.pdf');
} catch (Exception $e) {
    http_response_code(500);
    // Log the detailed error for debugging
    error_log("PDF Generation Failed for tally results: " . $e->getMessage());
    // Display a user-friendly error
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<strong>Error:</strong> An error occurred while generating the report. Please check the server logs or contact support.";
    echo "<br><br><strong>Details:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit;
}
