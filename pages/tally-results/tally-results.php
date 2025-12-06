<?php
// The db connection is included in filters.php, but it's good practice to have it here too.
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 1. Get Filter Values & Determine View Mode ---
$user_campus = $_SESSION['user_campus'] ?? null;

// Get filter values from URL, providing sensible defaults
$filter_campus_id = !empty($_GET['filter_campus']) ? $_GET['filter_campus'] : null;
$filter_division_id = !empty($_GET['filter_division']) ? $_GET['filter_division'] : null;
$filter_year = !empty($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$filter_quarter = !empty($_GET['filter_quarter']) ? $_GET['filter_quarter'] : null;
$filter_month = !empty($_GET['filter_month']) ? $_GET['filter_month'] : null;

// Check if the user has actively used the filters by checking for the presence of filter keys in the URL.
$user_has_filtered = isset($_GET['filter_division']) || isset($_GET['filter_year']) || isset($_GET['filter_quarter']) || isset($_GET['filter_month']);

// If it's the initial page load (no filters applied), default to the current month's view.
if (!$user_has_filtered) {
    $filter_month = date('n');
}

// Determine the view mode and set up table headers
$view_mode = 'year';
$column_headers = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

if ($filter_quarter) {
    $view_mode = 'quarter';
    // Map quarters to their respective months
    $quarter_months_map = [
        1 => [1, 2, 3], // Q1: Jan, Feb, Mar
        2 => [4, 5, 6], // Q2: Apr, May, Jun
        3 => [7, 8, 9], // Q3: Jul, Aug, Sep
        4 => [10, 11, 12] // Q4: Oct, Nov, Dec
    ];
    $months_in_quarter = $quarter_months_map[$filter_quarter];

    $column_headers = [];
    foreach ($months_in_quarter as $month_num) {
        $column_headers[] = date('M', mktime(0, 0, 0, $month_num, 1)); // e.g., 'Jul', 'Aug', 'Sep'
    }
    $filter_month = null; // Quarter takes precedence over month
} elseif ($filter_month) {
    $view_mode = 'month';
    $column_headers = [date('F', mktime(0, 0, 0, $filter_month, 1))];
}

// --- 2. Build Dynamic SQL Query ---
$tally_data = [];
$target_campus_name = $user_campus;
if ($filter_campus_id) {
    $stmt_campus = $pdo->prepare("SELECT campus_name FROM tbl_campus WHERE id = ?");
    $stmt_campus->execute([$filter_campus_id]);
    $target_campus_name = $stmt_campus->fetchColumn();
}

if ($target_campus_name) {
    // Base of the query
    $sql_select = "SELECT u.id as unit_id, u.unit_name";
    $sql_from_base = "FROM tbl_unit u";
    $join_conditions = "ON u.unit_name = r.response AND r.question_id = -3";
    $sql_where = "WHERE u.campus_name = ?";
    $sql_group_by = "GROUP BY u.id, u.unit_name";
    $sql_order_by = "ORDER BY u.unit_name ASC";

    $where_params = [$target_campus_name];
    $join_params = [];

    // Add time constraints to the JOIN condition for accuracy
    $join_conditions .= " AND YEAR(r.timestamp) = ?";
    $join_params[] = $filter_year;

    if ($view_mode === 'month') {
        $sql_select .= ", COUNT(DISTINCT r.response_id) as count";
        $join_conditions .= " AND MONTH(r.timestamp) = ?";
        $join_params[] = $filter_month;
    } elseif ($view_mode === 'quarter') {
        // Create a COUNT for each month in the selected quarter
        foreach ($column_headers as $month_abbr) {
            $month_num = date('n', strtotime("1 $month_abbr 2000")); // Get month number from abbreviation
            $month_alias = strtolower($month_abbr);
            $sql_select .= ", COUNT(DISTINCT CASE WHEN MONTH(r.timestamp) = $month_num THEN r.response_id END) AS {$month_alias}_count";
        }
        // Still filter by quarter in the WHERE clause for efficiency
        $join_conditions .= " AND QUARTER(r.timestamp) = ?";
        $join_params[] = $filter_quarter;
    } else { // 'year' view
        for ($m = 1; $m <= 12; $m++) {
            $month_name = strtolower(date('M', mktime(0, 0, 0, $m, 1)));
            $sql_select .= ", COUNT(DISTINCT CASE WHEN MONTH(r.timestamp) = $m THEN r.response_id END) AS {$month_name}_count";
        }
    }

    // Add division filter if selected
    if ($filter_division_id) {
        $stmt_div = $pdo->prepare("SELECT division_name FROM tbl_division WHERE id = ?");
        $stmt_div->execute([$filter_division_id]);
        $division_name = $stmt_div->fetchColumn();
        if ($division_name) {
            $sql_where .= " AND u.division_name = ?";
            $where_params[] = $division_name;
        }
    }

    $sql_from = "$sql_from_base LEFT JOIN tbl_responses r $join_conditions";
    $final_sql = "$sql_select $sql_from $sql_where $sql_group_by $sql_order_by";
    $params = array_merge($join_params, $where_params);

    try {
        $stmt = $pdo->prepare($final_sql);
        $stmt->execute($params);
        $tally_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Tally results query failed: " . $e->getMessage());
        $tally_data = [];
    }
}
?>
<div class="p-4 dark:text-white">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <h1 class="text-4xl font-bold">Unit Certification</h1>
    <P>You are viewing the tally results of available offices for this period.</P>
    <!-- Filters Bar -->
    <?php include 'filters.php'; // This now includes JS for interactions 
    ?>

    <!-- Table -->
    <div class="overflow-x-auto mt-4">
        <table class="w-full border-collapse">
            <thead class="bg-[#064089] text-white font-normal text-left dark:bg-gray-900 dark:text-white">
                <tr>
                    <th class="border border-[#1E1E1ECC] px-4 py-3 w-1/3">Office</th>
                    <?php foreach ($column_headers as $header) : ?>
                        <th class="border border-[#1E1E1ECC] px-4 py-3 text-center"><?php echo $header; ?></th>
                    <?php endforeach; ?>
                    <!--<?php if ($view_mode === 'month') : ?>
                        <th class="border border-[#1E1E1ECC] px-4 py-3 text-center">Analysis</th>
                    <?php endif; ?> -->
                    <th class="border border-[#1E1E1ECC] px-4 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php if (empty($tally_data)) : ?>
                    <tr class="bg-white">
                        <?php
                        $colspan = count($column_headers) + 2; // Office + Action
                        if ($view_mode === 'month') $colspan++; // Add one for Analysis
                        ?>
                        <td colspan="<?php echo $colspan; ?>" class="text-center p-4 text-gray-500 border border-[#1E1E1ECC]">No offices found for your campus or no responses have been recorded for the selected period.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($tally_data as $row) : ?>
                        <?php
                        $total_responses = 0;
                        if ($view_mode === 'year' || $view_mode === 'quarter') {
                            foreach ($column_headers as $header) {
                                $total_responses += (int)$row[strtolower($header) . '_count'];
                            }
                        } else {
                            $total_responses = (int)$row['count'];
                        }

                        if ($total_responses == 0) {
                            $analysis = 'Bad';
                            $analysis_class = 'bg-red-100 text-red-800';
                        } elseif ($total_responses < 10) {
                            $analysis = 'Neutral';
                            $analysis_class = 'bg-yellow-100 text-yellow-800';
                        } else {
                            $analysis = 'Good';
                            $analysis_class = 'bg-green-100 text-green-800';
                        }
                        ?>
                        <tr class="bg-white hover:bg-gray-300 dark:bg-gray-700 dark:text-white">
                            <td class="border border-[#1E1E1ECC] p-3"><?php echo htmlspecialchars($row['unit_name']); ?></td>
                            <?php
                            if ($view_mode === 'year' || $view_mode === 'quarter') {
                                foreach ($column_headers as $header) {
                                    echo '<td class="border border-[#1E1E1ECC] p-3 text-center">' . htmlspecialchars($row[strtolower($header) . '_count']) . '</td>';
                                }
                            } else {
                                echo '<td class="border border-[#1E1E1ECC] p-3 text-center">' . htmlspecialchars($row['count']) . '</td>';
                            }
                            ?>
                            <!--<?php if ($view_mode === 'month') : ?>
                                <td class="border border-[#1E1E1ECC] p-3 text-center">
                                    <span class="px-3 py-1 font-semibold leading-tight rounded-full text-xs <?php echo $analysis_class; ?>">
                                        <?php echo $analysis; ?>
                                    </span>
                                </td>
                            <?php endif; ?> -->
                            <td class="border border-[#1E1E1ECC] p-3 text-center">
                                <div class="flex justify-center">
                                    <?php
                                    $query_params = [
                                        'filter_campus' => $filter_campus_id,
                                        'filter_division' => $filter_division_id,
                                        'filter_year' => $filter_year,
                                        'filter_quarter' => $filter_quarter,
                                        'filter_month' => $filter_month,
                                        'unit_id' => $row['unit_id'],
                                    ];
                                    $download_url = '../../pages/tally-results/generate-tally-results.php?' . http_build_query(array_filter($query_params));
                                    ?>
                                    <a href="<?php echo htmlspecialchars($download_url); ?>" target="_blank" class="bg-[#D9E2EC] text-[#064089] px-4 py-1 rounded-full text-xs font-semibold transition flex justify-center items-center hover:bg-[#c2ccd6]">
                                        <img src="../../resources/svg/download-outline.svg" alt="Download Icon"> Download</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>