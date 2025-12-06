<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

$user_unit_id = $_SESSION['user_unit_id'] ?? null;
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

    // --- Get User's Unit Name ---
    $user_unit_name = null;
    if ($user_unit_id) {
        try {
            $stmt_unit_name = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE id = ?");
            $stmt_unit_name->execute([$user_unit_id]);
            $user_unit_name = $stmt_unit_name->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching user's unit name for dashboard: " . $e->getMessage());
        }
    }

    try {
        // --- Corrected Pending NCAR Count for Unit Head ---
        $current_year = date('Y');
        $current_quarter = ceil(date('n') / 3);

        if ($user_unit_id) {
            $ncar_count_sql = "
                SELECT COUNT(*) FROM (
                    SELECT 
                        COALESCE((SELECT status 
                                  FROM tbl_ncar 
                                  WHERE file_path LIKE CONCAT('%', REPLACE(REPLACE(REPLACE(u.unit_name, ' ', '-'), '/', '-'), '\\\\', '-'), '_', :year, '_q', :quarter, '_', MIN(r_comment.id), '.pdf') 
                                  ORDER BY id DESC LIMIT 1), 
                        'Unresolved') AS ncar_status
                    FROM
                        tbl_responses r_comment
                    JOIN
                        tbl_responses r_office ON r_comment.response_id = r_office.response_id AND r_office.question_id = -3
                    JOIN
                        tbl_unit u ON r_office.response = u.unit_name
                    WHERE
                        r_comment.analysis = 'negative'
                        AND r_comment.question_id > 0
                        AND u.id = :user_unit_id
                        AND YEAR(r_comment.timestamp) = :year
                        AND QUARTER(r_comment.timestamp) = :quarter
                    GROUP BY
                        r_comment.response_id, u.id, u.unit_name
                ) AS ncars WHERE ncars.ncar_status != 'Resolved'
            ";

            $stmt = $pdo->prepare($ncar_count_sql);
            $stmt->execute([':user_unit_id' => $user_unit_id, ':year' => $current_year, ':quarter' => $current_quarter]);
            $pending_ncar_count = $stmt->fetchColumn();
        }

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

        // --- MODIFIED: Fetch total monthly responses for the user's specific unit ---
        if ($user_unit_name) {
            $stmt_monthly_total = $pdo->prepare("
                SELECT COUNT(DISTINCT response_id)
                FROM tbl_responses
                WHERE question_id = -3 AND response = :unit_name
                AND timestamp >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND timestamp < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
            ");
            $stmt_monthly_total->execute([':unit_name' => $user_unit_name]);
            $total_monthly_responses = $stmt_monthly_total->fetchColumn();
        }

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

        // Fetch user type data for the pie chart
        $pie_colors = ['#064089', '#6497B1', '#B3CDE0', '#324D3E', '#8EA48B', '#BECFBC'];
        $stmt_pie = $pdo->prepare("
            SELECT type, COUNT(*) as user_count 
            FROM credentials 
            WHERE campus = :campus 
            GROUP BY type
            ORDER BY type ASC
        ");
        $stmt_pie->execute([':campus' => $user_campus]);
        $user_types = $stmt_pie->fetchAll(PDO::FETCH_ASSOC);

        $color_index = 0;
        foreach ($user_types as $type) {
            $pie_labels[] = $type['type'];
            $pie_data[] = (int)$type['user_count'];
            $pie_chart_legend_data[] = [
                'label' => $type['type'],
                'color' => $pie_colors[$color_index % count($pie_colors)]
            ];
            $color_index++;
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
                            <h2 class="text-lg">Monthly Response</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p id="monthly-response-count" class="text-4xl font-bold mt-2"><?php echo htmlspecialchars(number_format($total_monthly_responses)); ?></p>
                    </div>
                    <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Pending NCAR</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold mt-2"><?php echo htmlspecialchars(number_format($pending_ncar_count)); ?></p>
                    </div>

                    <!-- <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Pending NCAR</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold mt-2"><?php echo htmlspecialchars($pending_ncar_count); ?></p>
                    </div> -->
                </div>

            </div>

            <!-- Right Column: User Types Pie Chart -->
            <!--<div class="lg:w-2/5 bg-[#CFD8E5] rounded-lg p-6 shadow-2xl flex flex-col">
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
                Legend for Pie Chart (you'd generate this dynamically with Chart.js)
                <?php if (!empty($pie_chart_legend_data)) : ?>
                    <div class="mt-4 text-sm text-gray-600 flex justify-center gap-6">
                        <?php
                        // Split the legend data into two columns for better layout
                        $midpoint = ceil(count($pie_chart_legend_data) / 2);
                        $columns = array_chunk($pie_chart_legend_data, $midpoint);
                        ?>
                        <?php foreach ($columns as $column) : ?>
                            <div>
                                <?php foreach ($column as $item) : ?>
                                    <div class="flex items-center mb-1">
                                        <span class="w-3 h-3 rounded-full mr-2" style="background-color: <?php echo htmlspecialchars($item['color']); ?>"></span>
                                        <?php echo htmlspecialchars($item['label']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div> -->
        </div>
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

        // --- Office Dropdown Logic for Monthly Response Box ---
        const officeSelect = document.getElementById('office-select');
        const monthlyResponseCountEl = document.getElementById('monthly-response-count');

        if (officeSelect && monthlyResponseCountEl) {
            officeSelect.addEventListener('change', async () => {
                const selectedOffice = officeSelect.value;
                const url = `../../function/_dashboard/_getMonthlyOfficeResponse.php?office=${encodeURIComponent(selectedOffice)}`;

                // Show a loading state
                monthlyResponseCountEl.textContent = '...';

                try {
                    const response = await fetch(url);
                    const data = await response.json();

                    if (data.error) {
                        throw new Error(data.error);
                    }
                    // Update the monthly response count
                    monthlyResponseCountEl.textContent = data.current_month_count.toLocaleString();

                } catch (error) {
                    console.error('Failed to fetch monthly response count:', error);
                    monthlyResponseCountEl.textContent = 'Error';
                }
            });
        }

        // --- Full Page Loader Logic ---
        window.onload = function() {
            const loader = document.getElementById('full-page-loader');
            if (loader) {
                loader.style.display = 'none';
            }
        };
    });
</script>