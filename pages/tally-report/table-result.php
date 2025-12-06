<?php

// This file is included from a script that has access to:
// $pdf, $pdo, $year, $quarter, $quarter_text_for_footer, and $user_campus.

$pdf->SetFont('Arial', 'B', 12);
// Dynamically display the period (e.g., "January to March 2024")
$pdf->Cell(0, 5, $quarter_text_for_footer . " " . $year, 0, 1, 'C'); // Reduced height
// Dynamically display the campus name (e.g., "BINANGONAN CAMPUS")
$pdf->Cell(0, 5, strtoupper($user_campus) . " CAMPUS", 0, 1, 'C');
$pdf->Ln(2); // Reduced space
$pdf->Cell(0, 5, "Customer Satisfaction Survey", 0, 1, 'L');
$pdf->Ln(2); // Reduced space

// --- Determine Quarter Title for the table header ---
$quarter_title = '';
if ($quarter) {
    switch ($quarter) {
        case 1:
            $quarter_title = "1st Quarter";
            break;
        case 2:
            $quarter_title = "2nd Quarter";
            break;
        case 3:
            $quarter_title = "3rd Quarter";
            break;
        case 4:
            $quarter_title = "4th Quarter";
            break;
    }
} elseif ($month) {
    // For monthly view, the title is just the month name.
    $dateObj = DateTime::createFromFormat('!m', $month);
    $quarter_title = $dateObj->format('F'); // e.g., "January"
}

// --- Table Setup ---
$pdf->SetFont('Arial', 'B', 10); // Use a smaller font for the complex header

// Define column widths. Usable width is A4 width (210mm) minus margins (23mm * 2 = 46mm) = 164mm.
// Main department column
$col_dept_width = 104;
// 6 sub-columns for the quarter data
$sub_col_width = 10;
$total_quarter_width = $sub_col_width * 6; // 60mm total for the right side

// --- Draw Table Header ---
// To create a two-level header, we need to manage the cursor position manually.

// Store starting position to handle multi-line header correctly
$start_x = $pdf->GetX();
$start_y = $pdf->GetY();

// Define a smaller row height for the header to make it more compact.
$header_row_height = 6;

// 1. Draw the 'Department' cell. Its height is double the row height to span two rows.
$pdf->Cell($col_dept_width, $header_row_height * 2, 'Department', 1, 0, 'C');

// 2. Draw the main 'Quarter' title cell, which spans 6 sub-columns horizontally.
$pdf->Cell($total_quarter_width, $header_row_height, $quarter_title, 1, 1, 'C'); // New line after this

// 3. Move cursor to the start of the sub-header row.
// The X position is after the 'Department' cell. The Y position is on the second line of the header.
$pdf->SetXY($start_x + $col_dept_width, $start_y + $header_row_height);

// 4. Define and draw the sub-header cells below the quarter title.
$sub_headers = ['QoS', 'VI', 'SU', 'VI', 'AVE', 'VI'];
for ($i = 0; $i < count($sub_headers); $i++) {
    // The last cell will have a '1' to move to the next line for the table body
    $pdf->Cell($sub_col_width, $header_row_height, $sub_headers[$i], 1, ($i == count($sub_headers) - 1), 'C');
}
// --- Helper function for Verbal Interpretation ---
if (!function_exists('getVerbalInterpretation')) {
    function getVerbalInterpretation($mean)
    {
        if ($mean >= 4.50) return 'E';
        if ($mean >= 3.50) return 'VS';
        if ($mean >= 2.50) return 'S';
        if ($mean >= 1.50) return 'US';
        if ($mean >= 1.00) return 'P/NI';
        return ''; // Return empty if mean is 0 or out of range
    }
}

// --- Fetch Data and Render Table Body ---
try {
    // 1. Fetch ALL divisions from the database to ensure every division is listed.
    $stmtDivisions = $pdo->query("SELECT division_name FROM tbl_division ORDER BY division_name ASC");
    $all_divisions = $stmtDivisions->fetchAll(PDO::FETCH_COLUMN);

    // 2. Fetch all units for the user's campus and group them by division in PHP.
    $stmtUnits = $pdo->prepare("
        SELECT division_name, unit_name 
        FROM tbl_unit 
        WHERE campus_name = ? 
        ORDER BY unit_name ASC
    ");
    $stmtUnits->execute([$user_campus]);
    $units_raw = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);

    $units_by_division = [];
    foreach ($units_raw as $unit) {
        // Group units under their respective division name.
        $units_by_division[$unit['division_name']][] = $unit;
    }

    // 2. Fetch all survey data for the campus in the given period in a single query.
    $sql_data = "
        SELECT
            office_response.response AS unit_name,
            AVG(CASE WHEN r.question_rendering = 'QoS' THEN CAST(r.response AS DECIMAL(10,2)) ELSE NULL END) as qos_mean,
            AVG(CASE WHEN r.question_rendering = 'Su' THEN CAST(r.response AS DECIMAL(10,2)) ELSE NULL END) as su_mean,
            AVG(CAST(r.response AS DECIMAL(10,2))) as ave_mean
        FROM
            tbl_responses r
        JOIN
            (SELECT response_id, response FROM tbl_responses WHERE question_id = -3) AS office_response ON r.response_id = office_response.response_id
        WHERE
            r.response_id IN (
                SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :campus_name
            )
            AND r.question_rendering IN ('QoS', 'Su')
            AND r.response REGEXP '^[0-9\.]+$'
            AND YEAR(r.timestamp) = :year
    ";

    // Add period condition based on whether it's a quarter or month
    if ($quarter) {
        $sql_data .= " AND QUARTER(r.timestamp) = :quarter";
    } elseif ($month) {
        $sql_data .= " AND MONTH(r.timestamp) = :month";
    }

    // Append the GROUP BY clause at the very end
    $sql_data .= " GROUP BY office_response.response";

    $stmtData = $pdo->prepare($sql_data);
    $params = [':campus_name' => $user_campus, ':year' => $year];
    if ($quarter) $params[':quarter'] = $quarter;
    if ($month) $params[':month'] = $month;
    $stmtData->execute($params);
    $results = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // 3. Create a lookup map for easy access to unit data.
    $data_by_unit = [];
    foreach ($results as $row) {
        $data_by_unit[$row['unit_name']] = $row;
    }

    // 4. Loop through divisions and their units to build the table.
    $pdf->SetFont('Arial', '', 10);
    $row_height = 6;

    // Loop through ALL divisions to ensure each one is displayed.
    foreach ($all_divisions as $division_name) {
        // --- Calculate Division Averages ---
        $div_qos_total = 0;
        $div_su_total = 0;
        $div_ave_total = 0;
        $unit_count = 0;

        if (isset($units_by_division[$division_name])) {
            $unit_count = count($units_by_division[$division_name]);
            foreach ($units_by_division[$division_name] as $unit) {
                $unit_name = $unit['unit_name'];
                $unit_data = $data_by_unit[$unit_name] ?? ['qos_mean' => 0, 'su_mean' => 0, 'ave_mean' => 0];
                $div_qos_total += (float) $unit_data['qos_mean'];
                $div_su_total += (float) $unit_data['su_mean'];
                $div_ave_total += (float) $unit_data['ave_mean'];
            }
        }

        $div_qos_mean = ($unit_count > 0) ? $div_qos_total / $unit_count : 0;
        $div_su_mean = ($unit_count > 0) ? $div_su_total / $unit_count : 0;
        $div_ave_mean = ($unit_count > 0) ? $div_ave_total / $unit_count : 0;

        // --- Calculate dynamic row height for Division ---
        $pdf->SetFont('Arial', 'B', 10);
        $line_count_div = $pdf->NbLines($col_dept_width, $division_name);
        $current_row_height = 6 * $line_count_div;

        // --- Check for page break before drawing ---
        if ($pdf->GetY() + $current_row_height > $pdf->GetPageHeight() - $pdf->getBMargin()) {
            $pdf->AddPage();
            // Redraw header on new page
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($col_dept_width, $header_row_height * 2, 'Department', 1, 0, 'C');
            $pdf->SetXY($pdf->GetX() - $col_dept_width, $pdf->GetY()); // Reset X
            $pdf->Cell($col_dept_width, $header_row_height * 2, 'Department', 1, 0, 'C');
            $pdf->Cell($total_quarter_width, $header_row_height, $quarter_title, 1, 1, 'C');
            $pdf->SetX($start_x + $col_dept_width);
            for ($i = 0; $i < count($sub_headers); $i++) {
                $pdf->Cell($sub_col_width, $header_row_height, $sub_headers[$i], 1, ($i == count($sub_headers) - 1), 'C');
            }
        }

        // --- Draw Division Summary Row ---
        $pdf->SetFont('Arial', 'B', 10);
        $x_pos = $pdf->GetX();
        $y_pos = $pdf->GetY();
        $pdf->MultiCell($col_dept_width, 6, $division_name, 1, 'L');
        $pdf->SetXY($x_pos + $col_dept_width, $y_pos); // Reposition cursor

        $pdf->Cell($sub_col_width, $current_row_height, '', 1, 0, 'C');
        $pdf->Cell($sub_col_width, $current_row_height, '', 1, 0, 'C');
        $pdf->Cell($sub_col_width, $current_row_height, '', 1, 0, 'C');
        $pdf->Cell($sub_col_width, $current_row_height, '', 1, 0, 'C');
        $pdf->Cell($sub_col_width, $current_row_height, '', 1, 0, 'C');
        $pdf->Cell($sub_col_width, $current_row_height, '', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);

        // --- Draw Individual Unit Rows for the Division ---
        if (isset($units_by_division[$division_name])) {
            foreach ($units_by_division[$division_name] as $unit) {
                $unit_name = $unit['unit_name'];
                // Get the data for this unit from our lookup map. Default to 0 if no data exists.
                $unit_data = $data_by_unit[$unit_name] ?? ['qos_mean' => 0, 'su_mean' => 0, 'ave_mean' => 0];

                $qos_mean = (float) $unit_data['qos_mean'];
                $su_mean = (float) $unit_data['su_mean'];
                $ave_mean = (float) $unit_data['ave_mean'];

                // Get verbal interpretations
                $qos_vi = getVerbalInterpretation($qos_mean);
                $su_vi = getVerbalInterpretation($su_mean);
                $ave_vi = getVerbalInterpretation($ave_mean);

                // --- Calculate dynamic row height for Unit ---
                $indented_unit_name = '    ' . $unit_name;
                $line_count_unit = $pdf->NbLines($col_dept_width, $indented_unit_name);
                $current_row_height = 6 * $line_count_unit;

                // --- Check for page break before drawing ---
                if ($pdf->GetY() + $current_row_height > $pdf->GetPageHeight() - $pdf->getBMargin()) {
                    $pdf->AddPage();
                    // Redraw header on new page
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->Cell($col_dept_width, $header_row_height * 2, 'Department', 1, 0, 'C');
                    $pdf->SetXY($pdf->GetX() - $col_dept_width, $pdf->GetY()); // Reset X
                    $pdf->Cell($col_dept_width, $header_row_height * 2, 'Department', 1, 0, 'C');
                    $pdf->Cell($total_quarter_width, $header_row_height, $quarter_title, 1, 1, 'C');
                    $pdf->SetX($start_x + $col_dept_width);
                    for ($i = 0; $i < count($sub_headers); $i++) {
                        $pdf->Cell($sub_col_width, $header_row_height, $sub_headers[$i], 1, ($i == count($sub_headers) - 1), 'C');
                    }
                    $pdf->SetFont('Arial', '', 10);
                }

                // Draw the row for the unit
                $x_pos = $pdf->GetX();
                $y_pos = $pdf->GetY();
                $pdf->MultiCell($col_dept_width, 6, $indented_unit_name, 1, 'L');
                $pdf->SetXY($x_pos + $col_dept_width, $y_pos); // Reposition cursor

                $pdf->Cell($sub_col_width, $current_row_height, number_format($qos_mean, 2), 1, 0, 'C');
                $pdf->Cell($sub_col_width, $current_row_height, $qos_vi, 1, 0, 'C');
                $pdf->Cell($sub_col_width, $current_row_height, number_format($su_mean, 2), 1, 0, 'C');
                $pdf->Cell($sub_col_width, $current_row_height, $su_vi, 1, 0, 'C');
                $pdf->Cell($sub_col_width, $current_row_height, number_format($ave_mean, 2), 1, 0, 'C');
                $pdf->Cell($sub_col_width, $current_row_height, $ave_vi, 1, 1, 'C'); // New line at the end
            }
        }
    }

    // --- Fetch Coordinators for Signature ---
    $coordinators = [];
    // Assuming user table is `tbl_users` based on other parts of the application.
    $stmtCoord = $pdo->prepare("
        SELECT first_name, middle_name, last_name 
        FROM credentials 
        WHERE type = 'CSS Coordinator' AND campus = ? AND status = 'Active' 
        ORDER BY last_name ASC
    ");
    $stmtCoord->execute([$user_campus]);
    $coordinators = $stmtCoord->fetchAll(PDO::FETCH_ASSOC);

    // --- Fetch Campus Director for Signature ---
    $campus_director_name = ""; // Default to empty
    $stmtDirector = $pdo->prepare("
        SELECT first_name, middle_name, last_name 
        FROM credentials 
        WHERE type = 'Campus Director' AND campus = ? AND status = 'Active'
        LIMIT 1
    ");
    $stmtDirector->execute([$user_campus]);
    $director = $stmtDirector->fetch(PDO::FETCH_ASSOC);

    if ($director) {
        $firstName = $director['first_name'];
        $lastName = $director['last_name'];
        $middleInitial = !empty($director['middle_name']) ? strtoupper(substr(trim($director['middle_name']), 0, 1)) . '.' : '';
        $campus_director_name = trim(implode(' ', array_filter([$firstName, $middleInitial, $lastName])));
    }
} catch (PDOException $e) {
    // If there's a database error, display it in the PDF for debugging.
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(255, 0, 0); // Red text
    $pdf->MultiCell(0, 10, 'Database Error: ' . $e->getMessage());
}

// --- Signature Section ---
$pdf->Ln(20); // Add more space before the signature section
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, "Prepared by:", 0, 1, 'L');
$pdf->Ln(10); // Space for signatures

if (!empty($coordinators)) {
    foreach ($coordinators as $coordinator) {
        $firstName = $coordinator['first_name'];
        $lastName = $coordinator['last_name'];
        // Get the first letter of the middle name and append a period. Trim to handle extra spaces.
        $middleInitial = !empty($coordinator['middle_name']) ? strtoupper(substr(trim($coordinator['middle_name']), 0, 1)) . '.' : '';

        // Construct the full name, ensuring no double spaces if middle initial is empty
        $fullName = trim(implode(' ', array_filter([$firstName, $middleInitial, $lastName])));

        $pdf->SetFont('Arial', 'B', 10); // Bold for the name
        $pdf->Cell(0, 5, strtoupper($fullName), 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10); // Normal for the title
        $pdf->Cell(0, 5, "Coordinator, CSS " . $user_campus . " Campus", 0, 1, 'L');
        $pdf->Ln(10); // Space between coordinators
    }
} else {
    // Fallback if no coordinator is found for the campus
    $pdf->Ln(15); // Space for signature line
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, "Coordinator, CSS " . $user_campus . " Campus", 0, 1, 'L');
}

$pdf->Ln(10); // Add more space before the signature section
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 5, "NOTED:", 0, 1, 'L');
$pdf->Ln(10);

// Signature line for the Campus Director
$pdf->SetFont('Arial', 'B', 10); // Bold for the name
if (!empty($campus_director_name)) {
    $pdf->Cell(0, 5, strtoupper($campus_director_name), 0, 1, 'L');
} else {
    $pdf->Cell(0, 5, "______________________________", 0, 1, 'L');
}
$pdf->SetFont('Arial', '', 10); // Normal for the title
$pdf->Cell(0, 5, "Campus Director, " . $user_campus . " Campus", 0, 1, 'L');
$pdf->Ln(10);
