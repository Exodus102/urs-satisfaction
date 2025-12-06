<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

// --- Force fetch of Survey Version at the top to avoid conflicts ---
$active_survey_version = 'N/A'; // Default value
try {
    // This is the exact logic from the main dashboard.
    $stmt_survey = $pdo->query("SELECT question_survey FROM tbl_questionaireform WHERE date_approved IS NOT NULL ORDER BY date_approved DESC LIMIT 1");
    $survey_name = $stmt_survey->fetchColumn();
    if ($survey_name) {
        $active_survey_version = $survey_name;
    }
} catch (PDOException $e) {
    error_log("Campus Director Dashboard - Survey Version DB Error: " . $e->getMessage());
}

$user_campus = $_SESSION['user_campus'] ?? null;
$pending_ncar_count = 0;
$respondents_count = 0;
$office_labels = [];
$office_data = [];
$pie_labels = [];
$pie_data = [];
$pie_chart_legend_data = [];
$total_monthly_responses = 0;
$response_rate = 0;
$vs_last_month = 0;
$vs_last_month_display = '0';
$trend_labels = [];
$trend_data = [];
$campus_offices = []; // To store offices for the dropdown

if ($user_campus) {
    // Fetch campus offices for dropdowns, regardless of other data fetching success.
    try {
        $stmt_offices = $pdo->prepare("SELECT unit_name FROM tbl_unit WHERE campus_name = ? ORDER BY unit_name ASC");
        $stmt_offices->execute([$user_campus]);
        $campus_offices = $stmt_offices->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error fetching campus offices for dashboard: " . $e->getMessage());
        // $campus_offices remains an empty array, so dropdowns will be empty but page won't crash.
    }


    try {
        // --- Corrected Pending NCAR Count Logic ---
        // This query now mirrors the logic from the ncar-campus-director.php page to get an accurate count.
        $current_year = date('Y');
        $current_quarter = ceil(date('n') / 3);

        // This query now joins with the tbl_ncar table to exclude resolved items,
        // providing an accurate count of only pending (Unresolved) NCARs.
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
                    AND u.campus_name = :user_campus
                    AND YEAR(r_comment.timestamp) = :year
                    AND QUARTER(r_comment.timestamp) = :quarter
                GROUP BY
                    r_comment.response_id, u.id, u.unit_name
            ) AS ncars WHERE ncars.ncar_status != 'Resolved'
        ";

        $stmt = $pdo->prepare($ncar_count_sql);
        $stmt->execute([
            ':user_campus' => $user_campus,
            ':year' => $current_year,
            ':quarter' => $current_quarter
        ]);
        $pending_ncar_count = $stmt->fetchColumn();

        // Count unique respondents for the user's campus for the CURRENT YEAR
        $stmt_respondents = $pdo->prepare("
            SELECT COUNT(DISTINCT response_id) 
            FROM tbl_responses 
            WHERE question_id = -1 AND response = ? 
            AND YEAR(timestamp) = YEAR(CURDATE())
        ");
        $stmt_respondents->execute([$user_campus]);
        $respondents_count = $stmt_respondents->fetchColumn();

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
            LEFT JOIN tbl_responses r ON u.unit_name = r.response AND r.question_id = -3 
                AND r.timestamp >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND r.timestamp < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
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
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            const savedTheme = localStorage.getItem("theme");
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }

            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('dark');
            }
        })();
    </script>
    <!-- Main Dashboard Content -->
    <div class="w-full">
        <!-- Welcome Section -->
        <div class="dark:text-white">
            <h1 class="text-3xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['user_first_name'] ?? 'User'); ?>!</h1>
            <p class="">Gain real-time insights, track system status, and monitor key metrics to ensure total satisfaction.</p>
        </div>

        <!-- Key Metrics Cards and Charts -->
        <div class="flex flex-col lg:flex-row gap-6 shadow-around mt-6 lg:w-full">

            <!-- Left Column: Metrics and Bar Char -->
            <div class="flex flex-col w-full">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between dark:bg-gray-700 dark:text-white">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Survey Version</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <span class="inline-block text-lg font-bold px-3 py-1 mt-2 rounded-lg"><?php echo htmlspecialchars($active_survey_version); ?></span>
                    </div>
                    <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between dark:bg-gray-700 dark:text-white">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">CSS Respondents (<?php echo date('Y'); ?>)</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold mt-2"><?php echo htmlspecialchars(number_format($respondents_count)); ?></p>
                    </div>
                    <div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between dark:bg-gray-700 dark:text-white">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Pending NCAR</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold mt-2"><?php echo htmlspecialchars($pending_ncar_count); ?></p>
                    </div>
                </div>

                <div class="bg-[#CFD8E5] rounded-lg p-6 shadow-2xl w-full h-full dark:bg-gray-700 dark:text-white">
                    <h2 class="text-3xl mb-4 font-bold">Monthly Responses</h2>
                    <!-- 4 boxes -->
                    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 gap-4">
                        <!--<div class="bg-[#F1F7F9]/80 rounded-lg p-4 shadow-md text-center dark:bg-gray-900 dark:text-white">
                            <h3 class="text-md font-semibold">Total Responses</h3>
                            <p class="text-4xl font-bold"><?php echo htmlspecialchars(number_format($respondents_count)); ?></p>
                        </div> -->
                        <div class="bg-[#F1F7F9]/80 rounded-lg p-4 shadow-md text-center dark:bg-gray-900 dark:text-white">
                            <h3 class="text-md font-semibold">Monthly Response</h3>
                            <p id="monthly-response-count" class="text-4xl font-bold"><?php echo htmlspecialchars(number_format($total_monthly_responses)); ?></p>
                        </div>
                        <!--<div class="bg-[#F1F7F9]/80 rounded-lg p-4 shadow-md text-center dark:bg-gray-900 dark:text-white">
                            <h3 class="text-md font-semibold">Response Rate</h3>
                            <p id="response-rate" class="text-4xl font-bold text-black dark:text-white">
                                <?php echo htmlspecialchars($response_rate); ?>%
                            </p>
                        </div>-->
                        <!--<div class="bg-[#F1F7F9]/80 rounded-lg p-4 shadow-md text-center dark:bg-gray-900 dark:text-white">
                            <h3 class="text-md font-semibold">vs Last Month</h3>
                            <p id="vs-last-month" class="text-4xl font-bold text-black dark:text-white">
                                <?php echo htmlspecialchars($vs_last_month_display); ?>
                            </p>
                        </div>-->
                    </div>

                    <!-- Dropdown -->
                    <div class="mt-6">
                        <label for="office-select" class="block text-sm font-medium text-gray-700 dark:text-white">Select an Office for Details</label>
                        <select id="office-select" name="office-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold dark:bg-gray-900 dark:text-white">
                            <option value="" hidden>Office</option>
                            <?php foreach ($campus_offices as $office) : ?>
                                <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Right Column: User Types Pie Chart -->
            <!--<div class="lg:w-2/5 bg-[#CFD8E5] rounded-lg p-6 shadow-2xl flex flex-col dark:bg-gray-700 dark:text-white">
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
                                    <div class="flex items-center mb-1 dark:text-white">
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

        <!-- Trend Line Graph -->
        <!--<div class="w-full mt-6">
            <div class="bg-[#CFD8E5] rounded-lg p-6 shadow-2xl w-full dark:bg-gray-700 dark:text-white">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
                    <h2 class="text-3xl font-bold">Trend Analysis</h2>
                    <div class="flex flex-col lg:flex-row items-center gap-2 mt-2 sm:mt-0">
                        Office Filter for Trend Chart
                        <select id="trend-office-select" class="dark:bg-gray-900 dark:text-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold">\
                            <option value="" hidden>Office</option>
                            <option value="">All Offices</option>
                            <?php foreach ($campus_offices as $office) : ?>
                                <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                            <?php endforeach; ?>
                        </select>
                        Period Filter for Trend Chart
                        <select id="trend-period-select" class="dark:bg-gray-900 dark:text-white mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold">
                            <option value="annual" selected>Annual</option>
                            <option value="quarterly">Quarterly (This Year)</option>
                            <option value="monthly">Monthly (This Year)</option>
                        </select>
                    </div>
                </div>
                <div id="trend-scroll-container" class="overflow-x-auto w-full no-scrollbar cursor-grab active:cursor-grabbing">
                    <div id="trend-chart-wrapper" class="relative h-80">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div> -->
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

        const savedTheme = localStorage.getItem("theme");
        const isDarkMode = savedTheme == 'dark';
        const chartTextColor = isDarkMode ? 'white' : 'black';

        const toolTipColor = isDarkMode ? '#212121' : '#064089';
        const trendColor = isDarkMode ? '#111827' : '#064089';

        // --- Trend Line Chart ---
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            const ctx = trendCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 320); // h-80 = 320px
            gradient.addColorStop(0, 'rgba(6, 64, 137, 0.6)');
            gradient.addColorStop(1, 'rgba(179, 205, 224, 0.1)');

            trendChartInstance = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Monthly Responses',
                        data: trendData,
                        fill: true,
                        backgroundColor: gradient,
                        borderColor: trendColor,
                        borderWidth: 2.5,
                        tension: 0.4, // Makes the line smoother
                        pointBackgroundColor: trendColor,
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 0, // Hide points by default
                        pointHoverRadius: 7, // Show on hover
                        pointHoverBackgroundColor: '#FFFFFF',
                        pointHoverBorderColor: '#064089'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'sans-serif',
                                    weight: '500'
                                },
                                color: chartTextColor
                            }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: 500,
                            grid: {
                                color: '#E0E0E0',
                                borderDash: [5, 5], // Dashed lines
                                drawBorder: false,
                            },
                            ticks: {
                                precision: 0, // Ensure ticks are integers
                                padding: 10,
                                font: {
                                    family: 'sans-serif',
                                    weight: '500'
                                },
                                color: chartTextColor
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: toolTipColor,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false, // Hide the little color box
                            callbacks: {
                                label: (context) => `  Responses: ${context.raw}`
                            }
                        }
                    }
                }
            });
        }

        // --- Drag-to-scroll for Trend Chart ---
        const trendSlider = document.getElementById('trend-scroll-container');
        if (trendSlider) {
            let isDown = false;
            let startX;
            let scrollLeft;

            trendSlider.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - trendSlider.offsetLeft;
                scrollLeft = trendSlider.scrollLeft;
            });

            trendSlider.addEventListener('mouseleave', () => {
                isDown = false;
            });

            trendSlider.addEventListener('mouseup', () => {
                isDown = false;
            });

            trendSlider.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - trendSlider.offsetLeft;
                const walk = (x - startX) * 2; // Speed multiplier
                trendSlider.scrollLeft = scrollLeft - walk;
            });
        }

        // --- Trend Chart Filter Logic ---
        const trendOfficeSelect = document.getElementById('trend-office-select');
        const trendPeriodSelect = document.getElementById('trend-period-select');

        const updateTrendChart = async () => {
            if (!trendChartInstance) return;

            const selectedOffice = trendOfficeSelect.value;
            const selectedPeriod = trendPeriodSelect.value;
            const url = `../../function/_dashboard/_getTrendData.php?office=${encodeURIComponent(selectedOffice)}&period=${selectedPeriod}`;
            const chartWrapper = document.getElementById('trend-chart-wrapper');

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data.error) throw new Error(data.error);

                // Adjust chart width for scrolling if needed
                function updateChartWidth() {
                    const labelCount = data.labels.length;
                    const screenWidth = window.innerWidth;

                    if (selectedPeriod === 'monthly') {
                        if (screenWidth >= 1024) {
                            // Large screens (lg and above)
                            chartWrapper.style.width = '100%';
                        } else {
                            // Medium and smaller screens
                            const barWidth = 80; // pixels per month
                            chartWrapper.style.width = `${labelCount * barWidth}px`;
                        }
                    } else {
                        // For annual or quarterly
                        chartWrapper.style.width = '100%';
                    }
                }

                // Smooth animation setup
                chartWrapper.style.transition = 'width 0.4s ease-in-out';

                // Run once on load
                updateChartWidth();

                // Re-run on resize
                window.addEventListener('resize', updateChartWidth);

                // Update chart data and redraw
                trendChartInstance.data.labels = data.labels;
                trendChartInstance.data.datasets[0].data = data.data;
                trendChartInstance.update();

            } catch (error) {
                console.error('Failed to update trend chart:', error);
            }
        };

        if (trendOfficeSelect && trendPeriodSelect) {
            trendOfficeSelect.addEventListener('change', updateTrendChart);
            trendPeriodSelect.addEventListener('change', updateTrendChart);
        }

        // --- Office Dropdown Logic for Monthly Response Box ---
        const officeSelectEl = document.getElementById('office-select');
        const monthlyResponseCountEl = document.getElementById('monthly-response-count');

        if (officeSelectEl && monthlyResponseCountEl) {
            officeSelectEl.addEventListener('change', async () => {
                const selectedOffice = officeSelectEl.value;
                // If "Office" placeholder is selected, reload to show campus-wide total
                if (!selectedOffice) {
                    window.location.reload();
                    return;
                }
                const url = `../../function/_dashboard/_getMonthlyOfficeResponse.php?office=${encodeURIComponent(selectedOffice)}`;

                monthlyResponseCountEl.textContent = '...'; // Loading indicator

                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    monthlyResponseCountEl.textContent = data.current_month_count.toLocaleString();
                } catch (error) {
                    console.error('Failed to fetch monthly response count:', error);
                    monthlyResponseCountEl.textContent = 'Error';
                }
            });
        }
    });
</script>