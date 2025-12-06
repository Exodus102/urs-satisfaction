<?php

// This file is included from generate-report-tally.php and has access to:
// $pdf, $pdo, $year, $quarter, $month, and $user_campus.

try {
    // --- 1. Fetch all offices for the current campus ---
    $stmt_offices = $pdo->prepare("SELECT id, unit_name FROM tbl_unit WHERE campus_name = ? ORDER BY unit_name ASC");
    $stmt_offices->execute([$user_campus]);
    $all_offices = $stmt_offices->fetchAll(PDO::FETCH_ASSOC); // Contains 'id' and 'unit_name'

    // --- 2. Fetch all active questions once to avoid re-querying in the loop ---
    $stmt_questions = $pdo->prepare("
        SELECT question_id, question, question_type, question_rendering, header 
        FROM tbl_questionaire 
        WHERE status = 1 
        AND (question_rendering IN ('QoS', 'Su') OR header = 1)
        ORDER BY 
            CASE 
                WHEN question_rendering = 'QoS' THEN 1
                WHEN question_rendering = 'Su' THEN 2
                ELSE 3
            END, 
            question_id ASC
    ");
    $stmt_questions->execute();
    $all_questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

    // --- OPTIMIZATION: Fetch all means and comments for all offices in the campus at once ---
    $base_sql = "
        FROM tbl_responses r
        JOIN (SELECT response_id, response FROM tbl_responses WHERE question_id = -3) AS office_response 
            ON r.response_id = office_response.response_id
        WHERE
            r.response_id IN (
                SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :campus_name_param
            )
            AND YEAR(r.timestamp) = :year
    ";

    $period_condition = '';
    $params = [
        ':campus_name_param' => $user_campus,
        ':year' => $year,
    ];

    if ($quarter) {
        $period_condition = " AND QUARTER(r.timestamp) = :quarter";
        $params[':quarter'] = $quarter;
    } elseif ($month) {
        $period_condition = " AND MONTH(r.timestamp) = :month";
        $params[':month'] = $month;
    }

    // Query for all mean values, grouped by office and question
    $sql_all_means = "
        SELECT 
            office_response.response AS office_name, 
            r.question_id, 
            AVG(CAST(r.response AS DECIMAL(10,2))) AS mean_value
        " . $base_sql . "
        AND r.question_rendering IN ('QoS', 'Su')
        AND r.response REGEXP '^[0-9\.]+$'
        " . $period_condition . "
        GROUP BY office_response.response, r.question_id
    ";
    $stmt_all_means = $pdo->prepare($sql_all_means);
    $stmt_all_means->execute($params);
    $all_means_raw = $stmt_all_means->fetchAll(PDO::FETCH_ASSOC);

    // Query for all comments, grouped by office
    $sql_all_comments = "
        SELECT 
            office_response.response AS office_name, 
            r.comment
        " . $base_sql . "
        AND r.comment IS NOT NULL AND r.comment != ''
        " . $period_condition . "
        GROUP BY r.response_id, office_response.response, r.comment
    ";
    $stmt_all_comments = $pdo->prepare($sql_all_comments);
    $stmt_all_comments->execute($params);
    $all_comments_raw = $stmt_all_comments->fetchAll(PDO::FETCH_ASSOC);

    // --- Pre-process the raw data into lookup maps for easy access ---
    $means_by_office = [];
    foreach ($all_means_raw as $row) $means_by_office[$row['office_name']][$row['question_id']] = $row['mean_value'];
    $comments_by_office = [];
    foreach ($all_comments_raw as $row) $comments_by_office[$row['office_name']][] = $row['comment'];

    // --- 3. Define Helper functions if they don't exist ---
    if (!function_exists('getVerbalInterpretation')) {
        function getVerbalInterpretation($mean)
        {
            if ($mean >= 4.50) return 'E';
            if ($mean >= 3.50) return 'VS';
            if ($mean >= 2.50) return 'S';
            if ($mean >= 1.50) return 'US';
            if ($mean >= 1.00) return 'P/NI';
            return '';
        }
    }

    if (!function_exists('drawSummaryRow')) {
        function drawSummaryRow($pdf, $label, $average, $col1_width, $col2_width, $col3_width, $col4_width)
        {
            $vi = getVerbalInterpretation($average);
            $pdf->SetFont('Arial', 'B', 11);
            if ($pdf->GetY() + 6 > $pdf->getPageBreakTrigger()) {
                $pdf->AddPage($pdf->getCurOrientation());
            }
            $pdf->Cell($col1_width, 6, '', 1, 0, 'C');
            $pdf->Cell($col2_width, 6, $label, 1, 0, 'C');
            $pdf->Cell($col3_width, 6, number_format($average, 2), 1, 0, 'C');
            $pdf->Cell($col4_width, 6, $vi, 1, 1, 'C');
            $pdf->SetFont('Arial', '', 11);
        }
    }

    if (!function_exists('getVerbalInterpretationFullText')) {
        function getVerbalInterpretationFullText($mean)
        {
            if ($mean >= 4.50) {
                return 'Excellent';
            } elseif ($mean >= 3.50) {
                return 'Very Satisfactory';
            } elseif ($mean >= 2.50) {
                return 'Satisfactory';
            } elseif ($mean >= 1.50) {
                return 'Unsatisfactory';
            } elseif ($mean >= 1.00) {
                return 'Poor/Needs Improvement';
            }
            return 'N/A'; // Not Applicable or out of range
        }
    }

    // --- Define the period display string ---
    $period_display = "$quarter_text_for_footer $year";

    // --- 4. Loop through each office and generate its table ---
    foreach ($all_offices as $office) {
        $pdf->AddPage();
        $office_name = $office['unit_name'];

        // --- Display Office Header ---
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, 'Office:', 0, 0);
        $pdf->SetFont('Arial', 'BU', 12);
        $pdf->Cell(0, 7, $office_name, 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(20, 7, '', 0, 0); // Add an empty cell for indentation
        $pdf->Cell(0, 7, $period_display, 0, 1);


        // --- Get pre-fetched data for this office ---
        $means = $means_by_office[$office_name] ?? []; // This is now a map of [question_id => mean_value]
        $comments = $comments_by_office[$office_name] ?? [];


        // --- Draw Table Header ---
        $pdf->SetFont('Arial', 'B', 11);
        $col1_width = 10;
        $col3_width = 20;
        $col4_width = 20;
        $col2_width = 164 - $col1_width - $col3_width - $col4_width; // 114mm
        $pdf->Cell($col1_width, 6, '', 1, 0, 'C');
        $pdf->Cell($col2_width, 6, 'Question', 1, 0, 'C');
        $pdf->Cell($col3_width, 6, 'Mean', 1, 0, 'C');
        $pdf->Cell($col4_width, 6, 'VI', 1, 1, 'C');

        // --- Draw Table Body ---
        $pdf->SetFont('Arial', '', 11);
        $qos_means = [];
        $su_means = [];
        $questions_needing_recommendations = []; // Store questions that fall in the 'Satisfactory' range
        $questions_for_recommendation = [];
        $current_rendering_group = null;

        foreach ($all_questions as $question) {
            $question_rendering = $question['question_rendering'];

            if ($current_rendering_group === 'QoS' && $question_rendering !== 'QoS') {
                $qos_average = !empty($qos_means) ? array_sum($qos_means) / count($qos_means) : 0;
                drawSummaryRow($pdf, 'Average for QoS', $qos_average, $col1_width, $col2_width, $col3_width, $col4_width);
            }
            $current_rendering_group = $question_rendering;

            $question_type = $question['question_type'];
            $question_text = $question['question'];
            $question_id = $question['question_id'];
            $mean_value = $means[$question_id] ?? 0;

            $is_computable = !in_array($question_type, ['Text', 'Description']);

            if ($is_computable && $mean_value > 0) {
                if ($question_rendering === 'QoS') $qos_means[] = $mean_value;
                if ($question_rendering === 'Su') $su_means[] = $mean_value;

                // If a question's mean is 'Satisfactory' (2.50 - 3.49), add it to our list for recommendations.
                if ($mean_value >= 2.50 && $mean_value < 3.50) {
                    $questions_needing_recommendations[] = $question['question'];
                }
            }

            $display_mean = $is_computable ? number_format($mean_value, 2) : '';
            $verbal_interpretation = $is_computable ? getVerbalInterpretation($mean_value) : '';

            $line_count = $pdf->NbLines($col2_width, $question_text);
            $row_height = 6 * $line_count;

            if ($pdf->GetY() + $row_height > $pdf->getPageBreakTrigger()) {
                $pdf->AddPage($pdf->getCurOrientation());
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell($col1_width, 6, '', 1, 0, 'C');
                $pdf->Cell($col2_width, 6, 'Question', 1, 0, 'C');
                $pdf->Cell($col3_width, 6, 'Mean', 1, 0, 'C');
                $pdf->Cell($col4_width, 6, 'VI', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 11);
            }

            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($col1_width, $row_height, '', 1, 0, 'C');
            $pdf->MultiCell($col2_width, 6, $question_text, 1, 'L');
            $pdf->SetXY($x + $col1_width + $col2_width, $y);
            $pdf->Cell($col3_width, $row_height, $display_mean, 1, 0, 'C');
            $pdf->Cell($col4_width, $row_height, $verbal_interpretation, 1, 1, 'C');
        }

        // --- Draw Final Summary Rows for the office ---
        if ($current_rendering_group === 'Su') {
            $su_average = !empty($su_means) ? array_sum($su_means) / count($su_means) : 0;
            drawSummaryRow($pdf, 'Average for Su', $su_average, $col1_width, $col2_width, $col3_width, $col4_width);
        }

        $all_means = array_merge($qos_means, $su_means);
        $grand_mean = !empty($all_means) ? array_sum($all_means) / count($all_means) : 0;
        drawSummaryRow($pdf, 'Grand Mean', $grand_mean, $col1_width, $col2_width, $col3_width, $col4_width);

        // --- Display Recommendations Section based on Grand Mean ---
        $verbal_interpretation_full_text = getVerbalInterpretationFullText($grand_mean);
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, 'Recommendations:', 0, 1, 'L');
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 11);

        $recommendation_text = 'No specific recommendation tier for this score.';
        if ($grand_mean >= 4.50) {
            $recommendation_text = "Grand Mean of " . number_format($grand_mean, 2) . " with a verbal interpretation of " . $verbal_interpretation_full_text . ", Optimizing (Performance is very high; sustain + refine)";
        } elseif ($grand_mean >= 3.50) {
            $recommendation_text = "Grand Mean of " . number_format($grand_mean, 2) . " with a verbal interpretation of " . $verbal_interpretation_full_text . ", Continuous Improvement (Good, but still needs ongoing improvements)";
        } elseif ($grand_mean >= 2.50) {
            $recommendation_text = "Grand Mean of " . number_format($grand_mean, 2) . " with a verbal interpretation of " . $verbal_interpretation_full_text . ", Targeted Enhancement (Average; needs focused improvements)";
        } elseif ($grand_mean >= 1.50) {
            $recommendation_text = "Grand Mean of " . number_format($grand_mean, 2) . " with a verbal interpretation of " . $verbal_interpretation_full_text . ", Corrective Action (Below standard; clear problems to fix)";
        } elseif ($grand_mean >= 1.00) {
            $recommendation_text = "Grand Mean of " . number_format($grand_mean, 2) . " with a verbal interpretation of " . $verbal_interpretation_full_text . ", Immediate Intervention (Critical; urgent and comprehensive action needed)";
        } elseif ($grand_mean == 0) {
            $recommendation_text = 'No responses recorded for this period.';
        }

        $pdf->MultiCell(0, 5, $recommendation_text, 0, 'L');

        $pdf->Ln(10); // Add more space
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, 'Comments:', 0, 1, 'L');
        $pdf->Ln(2);

        // --- Display Comments ---
        $pdf->SetFont('Arial', '', 11);
        if (!empty($comments)) {
            $comment_number = 1;
            foreach ($comments as $comment) {
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
    }
} catch (PDOException $e) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(255, 0, 0); // Red text
    $pdf->MultiCell(0, 10, 'Database Error in table-office.php: ' . $e->getMessage());
    $pdf->SetTextColor(0, 0, 0); // Reset text color
    error_log("Error in table-office.php: " . $e->getMessage());
}
