<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

// Determine the campus to display. Prioritize GET parameter.
$selected_campus = $_GET['campus'] ?? null;

$user_campus = $_SESSION['user_campus'] ?? null;
$pending_ncar_count = 0;
$respondents_count = 0;
$office_labels = [];
$office_data = [];
$pie_labels = [];
$pie_data = [];
$pie_chart_legend_data = [];
$active_survey_version = 'N/A';
$total_monthly_responses = 0;
$response_rate = 0;
$vs_last_month = 0;
$vs_last_month_display = '0';
$trend_labels = [];
$trend_data = [];
$css_respondents_current_year = 0;
$all_campuses = [];
$campus_offices = []; // To store offices for the dropdown

// Fetch active survey version for all users
try {
    $stmt_survey = $pdo->query("SELECT question_survey FROM tbl_questionaireform WHERE date_approved IS NOT NULL ORDER BY date_approved DESC LIMIT 1");
    $survey_name = $stmt_survey->fetchColumn();
    if ($survey_name) {
        $active_survey_version = $survey_name;
    }
} catch (PDOException $e) { /* Error is handled by 'N/A' default */
}

// Fetch all campuses for the dropdown
try {
    $stmt_all_campuses = $pdo->query("SELECT campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $all_campuses = $stmt_all_campuses->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching all campuses for dashboard: " . $e->getMessage());
}

// Fetch user type data for the pie chart
try {
    $pie_colors = ['#064089', '#6497B1', '#B3CDE0', '#324D3E', '#8EA48B', '#BECFBC'];
    $params = [];
    $sql = "SELECT type, COUNT(*) as user_count FROM credentials";

    if ($selected_campus) {
        $sql .= " WHERE campus = :campus";
        $params[':campus'] = $selected_campus;
    }

    $sql .= " GROUP BY type ORDER BY type ASC";
    $stmt_pie = $pdo->prepare($sql);
    $stmt_pie->execute($params);

    $user_types = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);

    $pie_labels = [];
    $pie_data = [];
    $pie_chart_legend_data = [];
    $color_index = 0;
    foreach ($user_types as $type) {
        $pie_labels[] = $type['type'];
        $pie_data[] = (int)$type['user_count'];
        $pie_chart_legend_data[] = [
            'label' => $type['type'],
            'color' => $pie_colors[$color_index % count($pie_colors)],
        ];
        $color_index++;
    }
} catch (PDOException $e) {
    error_log("Error fetching dashboard pie chart data: " . $e->getMessage());
}

if ($user_campus) {
    // Fetch campus offices for dropdowns, regardless of other data fetching success
    try {
        $stmt_offices = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE campus_name = ? ORDER BY unit_name ASC");
        $stmt_offices->execute([$user_campus]);
        $campus_offices = $stmt_offices->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching campus offices for dashboard: " . $e->getMessage());
        // $campus_offices remains an empty array, so dropdowns will be empty but page won't crash.
    }

    try {
        // Sanitize the campus name to match the format used in the NCAR file paths
        $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $user_campus);
        $pattern = 'upload/pdf/ncar-report_' . $safe_campus_name . '_%';

        // Count NCARs for the user's campus that are not 'Resolved'
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_ncar WHERE file_path LIKE ? AND status != 'Resolved'");
        $stmt->execute([$pattern]);
        $pending_ncar_count = $stmt->fetchColumn();

        // Count unique respondents for the user's campus across all time
        $stmt_respondents = $pdo->prepare("
            SELECT COUNT(DISTINCT response_id) 
            FROM tbl_responses 
            WHERE question_id = -1 AND response = ?
        ");
        $stmt_respondents->execute([$user_campus]);
        $respondents_count = $stmt_respondents->fetchColumn();

        // Count unique respondents for the user's campus for the CURRENT YEAR
        $stmt_css_respondents = $pdo->prepare("
            SELECT COUNT(DISTINCT response_id) 
            FROM tbl_responses 
            WHERE question_id = -1 AND response = ? AND YEAR(timestamp) = YEAR(CURDATE())
        ");
        $stmt_css_respondents->execute([$user_campus]);
        $css_respondents_current_year = $stmt_css_respondents->fetchColumn();

        // Fetch total monthly responses for the entire campus (for the second box initial value)
        $stmt_monthly_total = $pdo->prepare("
            SELECT COUNT(DISTINCT r.response_id)
            FROM tbl_responses r JOIN tbl_unit u ON r.response = u.unit_name
            WHERE r.question_id = -3 AND u.campus_name = :campus
            AND r.timestamp >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND r.timestamp < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
        ");
        $stmt_monthly_total->execute([':campus' => $user_campus]);
        $total_monthly_responses = $stmt_monthly_total->fetchColumn();

        // Fetch last month's total responses for the entire campus
        $stmt_last_month_total = $pdo->prepare("
            SELECT COUNT(DISTINCT r.response_id)
            FROM tbl_responses r JOIN tbl_unit u ON r.response = u.unit_name
            WHERE r.question_id = -3 AND u.campus_name = :campus
            AND r.timestamp >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
            AND r.timestamp < DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ");
        $stmt_last_month_total->execute([':campus' => $user_campus]);
        $last_month_total_responses = $stmt_last_month_total->fetchColumn();
        $vs_last_month = $total_monthly_responses - $last_month_total_responses;
        $vs_last_month_display = ($vs_last_month > 0) ? '+' . $vs_last_month : $vs_last_month;

        // Fetch positive responses for the current month for the entire campus
        $stmt_positive = $pdo->prepare("
            SELECT COUNT(DISTINCT r.response_id)
            FROM tbl_responses r_positive
            JOIN tbl_responses r_campus ON r_positive.response_id = r_campus.response_id
            JOIN tbl_unit u ON r_campus.response = u.unit_name
            WHERE r_positive.analysis = 'positive'
            AND r_campus.question_id = -3 AND u.campus_name = :campus
            AND r_positive.timestamp >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND r_positive.timestamp < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
        ");
        $stmt_positive->execute([':campus' => $user_campus]);
        $positive_responses_total = $stmt_positive->fetchColumn();

        // Calculate initial response rate
        if ($total_monthly_responses > 0) {
            $response_rate = round(($positive_responses_total / $total_monthly_responses) * 100);
        }

        // Fetch monthly response data per office for the bar chart
        $stmt_chart = $pdo->prepare("
            SELECT 
                u.unit_name, 
                COUNT(DISTINCT r.response_id) as response_count
            FROM tbl_unit u
            LEFT JOIN tbl_responses r ON u.unit_name = r.response 
                AND r.question_id = -3 
                AND YEAR(r.timestamp) = YEAR(CURDATE())
                AND MONTH(r.timestamp) = MONTH(CURDATE())
            WHERE u.campus_name = :campus
            GROUP BY u.id, u.unit_name
            ORDER BY u.unit_name ASC
        ");
        $stmt_chart->execute([':campus' => $user_campus]);
        while ($row = $stmt_chart->fetch(PDO::FETCH_ASSOC)) {
            $office_labels[] = $row['unit_name'];
            $office_data[] = (int)$row['response_count'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching dashboard counts: " . $e->getMessage());
    }

    // --- Fetch Data for Trend Line Chart (Monthly responses for the current year) ---
    try {
        // Default to Annual view on page load
        $stmt_trend = $pdo->prepare("
            SELECT 
                YEAR(r.timestamp) as year,
                COUNT(DISTINCT r.response_id) as response_count
            FROM tbl_responses r
            WHERE r.response_id IN (
                SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :campus
            )
            GROUP BY YEAR(r.timestamp)
            ORDER BY year ASC
        ");
        $stmt_trend->execute([':campus' => $user_campus]);
        $yearly_counts = $stmt_trend->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($yearly_counts as $year => $count) {
            $trend_labels[] = (string)$year;
            $trend_data[] = $count;
        }
    } catch (PDOException $e) {
        error_log("Error fetching dashboard counts: " . $e->getMessage());
    }
}
?>
<div class="p-4">
    <!-- Full Page Loader -->
    <div id="full-page-loader" class="fixed inset-0 bg-gray-100 bg-opacity-75 flex items-center justify-center z-50">
        <svg class="animate-spin h-16 w-16 text-[#064089]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <!-- Main Dashboard Content -->
    <div class="w-full">
        <!-- Welcome Section -->
        <div class="">
            <h1 class="text-3xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['user_first_name'] ?? 'User'); ?>!</h1>
            <p class="">Gain real-time insights, track system status, and monitor key metrics to ensure total satisfaction.</p>
        </div>

        <!-- Key Metrics Cards and Charts -->
        <div class="flex flex-col lg:flex-row gap-6 shadow-around mt-6 lg:w-full">

            <!-- Left Column: Metrics and Bar Char -->
            <div class="flex flex-col w-full">
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-6">
                    <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Survey Version</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <span class="inline-block text-lg font-bold px-3 py-1 mt-2 rounded-lg"><?php echo htmlspecialchars($active_survey_version); ?></span>
                    </div>
                    <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Campus</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <form id="campus-filter-form" method="GET" class="mt-2">
                            <input type="hidden" name="page" value="dashboard-mis">
                            <select name="campus" id="campus-filter" class="w-full text-lg font-bold p-2 border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Campuses</option>
                                <?php foreach ($all_campuses as $campus_option) : ?>
                                    <option value="<?php echo htmlspecialchars($campus_option); ?>" <?php echo ($selected_campus == $campus_option) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($campus_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="w-full bg-[#CFD8E5] rounded-lg p-6 shadow-2xl flex flex-col">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold ">User Types</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <?php
                        // Set the timezone to Philippine time
                        date_default_timezone_set('Asia/Manila');
                        // Display the current date and time
                        ?>
                        <p class="text-xs mb-4">As of <?php echo date('F j, Y \a\t h:i A'); ?></p>
                        <div class="flex-grow flex items-center justify-center min-h-0">
                            <div class="relative w-64 h-64 lg:w-28 lg:h-28 xl:w-64 xl:h-64">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>
                        <!-- Legend for Pie Chart (you'd generate this dynamically with Chart.js)-->
                        <?php if (!empty($pie_chart_legend_data)) : ?>
                            <div class="mt-4 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm text-gray-700">
                                <?php foreach ($pie_chart_legend_data as $item) : ?>
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($item['color']); ?>"></span>
                                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Trend Line Graph -->
            <!--<div class="w-full mt-6">
            <div class="bg-[#CFD8E5] rounded-lg p-6 shadow-2xl w-full">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
                    <h2 class="text-3xl font-bold">Trend Analysis <?php echo date('Y'); ?></h2>
                    <div class="flex flex-col lg:flex-row items-center gap-2 mt-2 sm:mt-0">
                        Office Filter for Trend Chart
                        <select id="trend-office-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold">\
                            <option value="" hidden>Office</option>
                            <option value="">All Offices</option>
                            <?php foreach ($campus_offices as $office) : ?>
                                <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                            <?php endforeach; ?>
                        </select>
                        Period Filter for Trend Chart
                        <select id="trend-period-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold">
                            <option value="annual" selected>Annual</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <div id="trend-scroll-container" class="overflow-x-auto w-full no-scrollbar cursor-grab active:cursor-grabbing">
                    <div id="trend-chart-wrapper" class="relative h-80">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>-->
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let trendChartInstance = null; // To hold the chart object
            const pieLabels = <?php echo json_encode($pie_labels); ?>;
            const pieData = <?php echo json_encode($pie_data); ?>;
            const trendLabels = <?php echo json_encode($trend_labels); ?>;
            const trendData = <?php echo json_encode($trend_data); ?>;


            // Pie Chart for User Types
            const pieCtx = document.getElementById('pieChart');
            if (pieCtx) {
                new Chart(pieCtx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: pieLabels,
                        datasets: [{
                            label: 'User Types',
                            data: pieData,
                            backgroundColor: [
                                '#064089', // bg-blue-700
                                '#6497B1', // bg-blue-400
                                '#B3CDE0', // bg-green-500
                                '#324D3E', // bg-purple-500
                                '#8EA48B', // bg-yellow-500
                                '#BECFBC' // bg-red-500
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true, // Chart will fill the container
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false // The legend is manually created in HTML

                            }
                        }
                    }
                });
            }

            // Other chart and event listener logic would go here...

            // --- Full Page Loader Logic ---
            const campusFilter = document.getElementById('campus-filter');
            if (campusFilter) {
                campusFilter.addEventListener('change', () => {
                    document.getElementById('campus-filter-form').submit();
                });
            }

            window.onload = function() {
                const loader = document.getElementById('full-page-loader');
                if (loader) {
                    loader.style.display = 'none';
                }
            };
        });
    </script>