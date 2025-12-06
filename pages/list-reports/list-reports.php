<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$all_campuses = [];
$approved_monthly_reports = [];
$approved_quarterly_reports = [];
$available_years = [];

// --- New Logic for Report Status Table ---

// 1. Get selected year, default to current year
$selected_year = $_GET['year'] ?? date('Y');

// 2. Fetch all campuses
try {
    $stmt_campuses = $pdo->query("SELECT campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $all_campuses = $stmt_campuses->fetchAll(PDO::FETCH_COLUMN);

    // Fetch distinct years from approved reports for the filter dropdown
    $stmt_years = $pdo->query("SELECT DISTINCT YEAR(date_approved) as report_year FROM tbl_approved ORDER BY report_year DESC");
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array(date('Y'), $available_years)) {
        array_unshift($available_years, date('Y'));
    }
} catch (PDOException $e) {
    error_log("Error fetching data for DCC dashboard table: " . $e->getMessage());
}

// 3. Fetch all approved monthly reports for the selected year for quick lookup
$search_pattern = "upload/pdf/tally-report_%_{$selected_year}_m%.pdf";
$stmt_approved = $pdo->prepare("SELECT file_path FROM tbl_approved WHERE file_path LIKE ?");
$stmt_approved->execute([$search_pattern]);
$approved_monthly_reports = $stmt_approved->fetchAll(PDO::FETCH_COLUMN);

// 4. Fetch all approved QUARTERLY reports for the selected year
$search_pattern_quarterly = "upload/pdf/tally-report_%_{$selected_year}_q%.pdf";
$stmt_approved_quarterly = $pdo->prepare("SELECT file_path FROM tbl_approved WHERE file_path LIKE ?");
$stmt_approved_quarterly->execute([$search_pattern_quarterly]);
$approved_quarterly_reports = $stmt_approved_quarterly->fetchAll(PDO::FETCH_COLUMN);
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
            <h1 class="text-3xl font-bold">List Reports</h1>
            <p class="">List of reports for every campuses.</p>
        </div>

        <!-- Key Metrics Cards and Charts -->
        <div class="flex flex-col lg:flex-row gap-6 shadow-around mt-6 lg:w-full">

            <!-- Left Column: Metrics and Bar Char -->
            <div class="flex flex-col w-full">
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-1">
                    <!--<div class="bg-[#CFD8E5] rounded-lg p-4 shadow-2xl flex flex-col justify-between">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg">Survey Version</h2>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <span class="inline-block text-lg font-bold px-3 py-1 mt-2 rounded-lg"><?php echo htmlspecialchars($active_survey_version); ?></span>
                    </div>-->
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

        <!-- Campus Report Status Table -->
        <div class=" bg-[#CFD8E5] p-6 rounded-lg shadow-2xl">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 ">
                <h2 class="text-2xl font-bold mb-2 sm:mb-0">Campus Report Submission Status</h2>
                <form id="year-filter-form" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="page" value="dashboard-dcc">
                </form>
            </div>

            <div class="overflow-x-auto rounded-lg shadow-md mb-8">
                <table class="w-full min-w-[800px] border-collapse">
                    <thead class="bg-[#064089] text-white text-sm">
                        <tr>
                            <th class="border border-gray-300 p-2 text-left">Campus</th>
                            <?php for ($m = 1; $m <= 12; $m++) : ?>
                                <th class="border border-gray-300 p-2"><?php echo date('M', mktime(0, 0, 0, $m, 1)); ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php foreach ($all_campuses as $campus) : ?>
                            <tr class="text-center">
                                <td class="border border-gray-300 p-2 text-left font-semibold">
                                    <?php echo htmlspecialchars($campus); ?>
                                </td>
                                <?php
                                $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $campus);
                                for ($m = 1; $m <= 12; $m++) {
                                    $report_filename = "upload/pdf/tally-report_{$safe_campus_name}_{$selected_year}_m{$m}.pdf";
                                    $has_report = in_array($report_filename, $approved_monthly_reports);
                                    // If there is no report, display 'X'. Otherwise, display an empty space.
                                    echo '<td class="border border-gray-300 p-2">' . ($has_report ? '✔️' : 'X') . '</td>';
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Campus Quarterly Report Status Table -->
        <div class="mt-8 bg-[#CFD8E5] p-6 rounded-lg shadow-2xl">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                <h2 class="text-2xl font-bold mb-2 sm:mb-0">Campus Quarterly Report Status</h2>
            </div>

            <div class="overflow-x-auto rounded-lg shadow-md">
                <table class="w-full min-w-[600px] border-collapse">
                    <thead class="bg-[#064089] text-white text-sm">
                        <tr>
                            <th class="border border-gray-300 p-2 text-left">Campus</th>
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
                                    <?php echo htmlspecialchars($campus); ?>
                                </td>
                                <?php
                                $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $campus);
                                for ($q = 1; $q <= 4; $q++) {
                                    $report_filename = "upload/pdf/tally-report_{$safe_campus_name}_{$selected_year}_q{$q}.pdf";
                                    $has_report = in_array($report_filename, $approved_quarterly_reports);
                                    // If there is no report, display 'X'. Otherwise, display an empty space.
                                    echo '<td class="border border-gray-300 p-2">' . ($has_report ? '✔️' : 'X') . '</td>';
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
        // --- Year Filter Logic ---
        const yearFilter = document.getElementById('year-filter');
        if (yearFilter) {
            yearFilter.addEventListener('change', () => {
                document.getElementById('year-filter-form').submit();
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