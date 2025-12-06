<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

$user_campus = $_SESSION['user_campus'] ?? null;
$active_survey_version = 'N/A';
$trend_labels = [];
$trend_data = [];
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

// Fetch all campuses for the new trend analysis filter
$approved_monthly_reports = [];
$approved_quarterly_reports = [];
$available_years = [];
$all_campuses = [];
try {
    $stmt_all_campuses = $pdo->query("SELECT campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $all_campuses = $stmt_all_campuses->fetchAll(PDO::FETCH_COLUMN);

    // --- New Logic for Report Status Table ---
    $selected_year = $_GET['year'] ?? date('Y');

    // Fetch distinct years from approved reports for the filter dropdown
    $stmt_years = $pdo->query("SELECT DISTINCT YEAR(date_approved) as report_year FROM tbl_approved ORDER BY report_year DESC");
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array(date('Y'), $available_years)) {
        array_unshift($available_years, date('Y'));
    }

    // Fetch all approved monthly reports for the selected year for quick lookup
    $search_pattern_monthly = "upload/pdf/tally-report_%_{$selected_year}_m%.pdf";
    $stmt_approved_monthly = $pdo->prepare("SELECT file_path FROM tbl_approved WHERE file_path LIKE ?");
    $stmt_approved_monthly->execute([$search_pattern_monthly]);
    $approved_monthly_reports = $stmt_approved_monthly->fetchAll(PDO::FETCH_COLUMN);

    // Fetch all approved QUARTERLY reports for the selected year
    $search_pattern_quarterly = "upload/pdf/tally-report_%_{$selected_year}_q%.pdf";
    $stmt_approved_quarterly = $pdo->prepare("SELECT file_path FROM tbl_approved WHERE file_path LIKE ?");
    $stmt_approved_quarterly->execute([$search_pattern_quarterly]);
    $approved_quarterly_reports = $stmt_approved_quarterly->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching all campuses for dashboard trend filter: " . $e->getMessage());
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
                    <!--<div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">CSS Respondent (<?php echo date('Y'); ?>)</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p class="text-4xl font-bold mt-2"><?php echo htmlspecialchars(number_format($css_respondents_current_year)); ?></p>
                    </div>-->
                    <!--<div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Monthly Response</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <p id="monthly-response-count" class="text-4xl font-bold mt-2"><?php echo htmlspecialchars(number_format($total_monthly_responses)); ?></p>
                    </div>-->
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

        <!-- Trend Line Graph -->
        <div class="w-full mt-6">
            <div class="bg-[#CFD8E5] rounded-lg p-6 shadow-2xl w-full">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center mb-4">
                    <h2 class="text-3xl font-bold">Trend Analysis <?php echo date('Y'); ?></h2>
                    <div class="flex flex-col lg:flex-row items-center gap-2 mt-2 sm:mt-0">
                        <!-- Campus Filter for Trend Chart -->
                        <select id="trend-campus-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold">
                            <?php foreach ($all_campuses as $campus) : ?>
                                <option value="<?php echo htmlspecialchars($campus); ?>" <?php echo ($campus === $user_campus) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($campus); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Office Filter for Trend Chart -->
                        <select id="trend-office-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md font-bold">\
                            <option value="" hidden>Office</option>
                            <option value="">All Offices</option>
                            <?php foreach ($campus_offices as $office) : ?>
                                <option value="<?php echo htmlspecialchars($office); ?>"><?php echo htmlspecialchars($office); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Period Filter for Trend Chart -->
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
        </div>

        <!-- Campus Report Status Tables -->
        <div class="mt-8 bg-[#CFD8E5] p-6 rounded-lg shadow-2xl">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                <h2 class="text-2xl font-bold mb-2 sm:mb-0">Campus Report Submission Status</h2>
                <form id="year-filter-form" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="page" value="dashboard-css-head">
                    
                </form>
            </div>

            <!-- Monthly Table -->
            <div class="overflow-x-auto rounded-lg shadow-md mb-8">
                <table class="w-full min-w-[800px] border-collapse">
                    <thead class="bg-[#064089] text-white text-sm">
                        <tr>
                            <th class="border border-gray-300 p-2 text-left">Monthly Reports</th>
                            <?php for ($m = 1; $m <= 12; $m++) : ?>
                                <th class="border border-gray-300 p-2"><?php echo date('M', mktime(0, 0, 0, $m, 1)); ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php foreach ($all_campuses as $campus) : ?>
                            <tr class="text-center">
                                <td class="border border-gray-300 p-2 text-left font-semibold">
                                    <a href="css-head-layout.php?page=tally-report-dcc&campus=<?php echo urlencode($campus); ?>&filter_year=<?php echo $selected_year; ?>&filter_view=monthly" class="hover:underline">
                                        <?php echo htmlspecialchars($campus); ?>
                                    </a>
                                </td>
                                <?php
                                $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $campus);
                                for ($m = 1; $m <= 12; $m++) {
                                    $report_filename = "upload/pdf/tally-report_{$safe_campus_name}_{$selected_year}_m{$m}.pdf";
                                    $style = in_array($report_filename, $approved_monthly_reports) ? 'style="background-color: #064089;"' : '';
                                    echo '<td class="border border-gray-300 p-2" ' . $style . '>&nbsp;</td>';
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Quarterly Table Container -->
        <div class="mt-8 bg-[#CFD8E5] p-6 rounded-lg shadow-2xl">
            <h2 class="text-2xl font-bold mb-4">Campus Quarterly Report Status</h2>

            <!-- Quarterly Table -->
            <div class="overflow-x-auto rounded-lg shadow-md">
                <table class="w-full min-w-[600px] border-collapse">
                    <thead class="bg-[#064089] text-white text-sm">
                        <tr>
                            <th class="border border-gray-300 p-2 text-left">Quarterly Reports</th>
                            <th class="border border-gray-300 p-2">Q1</th>
                            <th class="border border-gray-300 p-2">Q2</th>
                            <th class="border border-gray-300 p-2">Q3</th>
                            <th class="border border-gray-300 p-2">Q4</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php foreach ($all_campuses as $campus) : ?>
                            <tr class="text-center">
                                <td class="border border-gray-300 p-2 text-left font-semibold">
                                    <a href="css-head-layout.php?page=tally-report-dcc&campus=<?php echo urlencode($campus); ?>&filter_year=<?php echo $selected_year; ?>&filter_view=quarterly" class="hover:underline">
                                        <?php echo htmlspecialchars($campus); ?>
                                    </a>
                                </td>
                                <?php
                                $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $campus);
                                for ($q = 1; $q <= 4; $q++) {
                                    $report_filename = "upload/pdf/tally-report_{$safe_campus_name}_{$selected_year}_q{$q}.pdf";
                                    $style = in_array($report_filename, $approved_quarterly_reports) ? 'style="background-color: #064089;"' : '';
                                    echo '<td class="border border-gray-300 p-2" ' . $style . '>&nbsp;</td>';
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let trendChartInstance = null; // To hold the chart object
        const trendLabels = <?php echo json_encode($trend_labels); ?>;
        const trendData = <?php echo json_encode($trend_data); ?>;

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
                        borderColor: '#064089',
                        borderWidth: 2.5,
                        tension: 0.4, // Makes the line smoother
                        pointBackgroundColor: '#064089',
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
                                }
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
                                }
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
                            backgroundColor: '#064089',
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
        const trendCampusSelect = document.getElementById('trend-campus-select');
        const trendPeriodSelect = document.getElementById('trend-period-select');

        // Function to update the office dropdown based on the selected campus
        const updateOfficeDropdown = async () => {
            const selectedCampus = trendCampusSelect.value;
            const url = `../../function/_dashboard/_getOfficesByCampus.php?campus=${encodeURIComponent(selectedCampus)}`;

            try {
                const response = await fetch(url);
                const offices = await response.json();

                // Clear existing options (except the placeholder)
                trendOfficeSelect.innerHTML = '<option value="">All Offices</option>';

                // Add new options
                offices.forEach(office => {
                    const option = document.createElement('option');
                    option.value = office;
                    option.textContent = office;
                    trendOfficeSelect.appendChild(option);
                });

                // After updating offices, trigger a chart update
                updateTrendChart();

            } catch (error) {
                console.error('Failed to update office dropdown:', error);
            }
        };


        const updateTrendChart = async () => {
            if (!trendChartInstance) return;

            const selectedOffice = trendOfficeSelect.value;
            const selectedCampus = trendCampusSelect.value;
            const selectedPeriod = trendPeriodSelect.value;
            const url = `../../function/_dashboard/_getTrendData.php?campus=${encodeURIComponent(selectedCampus)}&office=${encodeURIComponent(selectedOffice)}&period=${selectedPeriod}`;
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

        trendCampusSelect.addEventListener('change', updateOfficeDropdown);
        trendOfficeSelect.addEventListener('change', updateTrendChart);
        trendPeriodSelect.addEventListener('change', updateTrendChart);

        // --- Year Filter Logic for Report Tables ---
        const yearFilter = document.getElementById('year-filter');
        if (yearFilter) {
            yearFilter.addEventListener('change', () => {
                document.getElementById('year-filter-form').submit();
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