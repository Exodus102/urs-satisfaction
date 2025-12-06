<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Get Filter Values & Determine Campus ---
$user_campus = $_SESSION['user_campus'] ?? null;
$selected_campus = $_GET['campus'] ?? $user_campus; // Prioritize GET param, fallback to user's campus
$filter_view = $_GET['filter_view'] ?? 'quarterly';
$filter_year = $_GET['filter_year'] ?? date('Y');

$reports = [];
$all_campuses = [];

try {
    // Fetch all campuses for the new dropdown filter
    $stmt_campuses = $pdo->query("SELECT campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $all_campuses = $stmt_campuses->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching campuses for DCC report filter: " . $e->getMessage());
}

if ($selected_campus) {
    try {
        // Sanitize campus name to match the format in the filename
        $safe_campus_name = preg_replace('/[\s\/\\?%*:|"<>]+/', '-', $selected_campus);

        // Determine the search pattern based on the view filter
        $pattern_prefix = ($filter_view === 'quarterly') ? 'q' : 'm';
        $search_pattern = "upload/pdf/tally-report_{$safe_campus_name}_{$filter_year}_{$pattern_prefix}%.pdf";

        $stmt = $pdo->prepare("SELECT file_path FROM tbl_approved WHERE file_path LIKE ? ORDER BY file_path DESC");
        $stmt->execute([$search_pattern]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process results to create a display name
        foreach ($results as $row) {
            $file_path = $row['file_path'];
            $filename = basename($file_path);
            $display_name = 'Unknown Report';

            // Example filename: tally-report_Binangonan-Campus_2024_q4.pdf
            if (preg_match("/tally-report_.*_(\d{4})_([qm])(\d+)\.pdf/", $filename, $matches)) {
                $year = $matches[1];
                $type = $matches[2];
                $num = (int)$matches[3];

                if ($type === 'q') {
                    $display_name = $num . ($num == 1 ? 'st' : ($num == 2 ? 'nd' : ($num == 3 ? 'rd' : 'th'))) . " Quarter {$year} Report";
                } else {
                    $display_name = date('F', mktime(0, 0, 0, $num, 1)) . " {$year} Report";
                }
            }
            $reports[] = ['display_name' => $display_name, 'file_path' => $file_path];
        }
    } catch (PDOException $e) {
        error_log("Error fetching submitted reports: " . $e->getMessage());
    }
}
?>
<!-- Main container for the list of quarters -->
<div id="tally-list-container" class="p-4 dark:text-white">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <div>
        <span class="text-4xl font-bold font-sfpro">CSS Reports</span><br>
        <span>You are viewing the generated reports of available offices for this period.</span>
    </div>

    <!-- Filters Form -->
    <form id="filters-form" method="GET" action="">
        <input type="hidden" name="page" value="tally-report-dcc">
        <?php include "filter.php"; ?>
    </form>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse">
            <thead class="bg-[#064089] text-white font-normal dark:bg-gray-900">
                <tr>
                    <th class="border border-[#1E1E1ECC] font-normal p-3 text-left">Report Name</th>
                    <th class="border border-[#1E1E1ECC] font-normal">Action</th>
                </tr>
            </thead>
            <tbody id="reports-tbody">
                <?php if (empty($reports)) : ?>
                    <tr>
                        <td colspan="2" class="text-center p-4 border border-gray-300">No submitted reports found for the selected period.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($reports as $report) : ?>
                        <tr class="bg-white dark:bg-gray-700 dark:text-white">
                            <td class="border border-[#1E1E1ECC] p-3"><?php echo htmlspecialchars($report['display_name']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-3 text-center gap-2">
                                <div class="flex justify-center gap-2">
                                    <button data-path="<?php echo htmlspecialchars($report['file_path']); ?>" class="view-report-btn bg-[#D9E2EC] flex gap-1 p-1 w-24 rounded-full justify-center text-[#064089] hover:bg-[#c2ccd6]"><img src="../../resources/svg/eye-icon.svg" alt="" srcset="">View</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Container for the dynamically loaded report, hidden by default -->
<div id="report-view-container" class="hidden p-4">
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center">
            <button id="back-to-tally-list-btn" class="">
                <img src="../../resources/svg/back-arrow-rounded.svg" alt="Back" srcset="">
            </button>
            <div class="ml-4">
                <span id="report-title" class="text-2xl font-bold font-sfpro">CSS Report</span><br>
                <span id="report-period-text" class="font-normal text-base"></span>
            </div>
        </div>
        
    </div>
    <div id="report-content" class="h-[80vh]">
        <!-- Content from generate-report-tally.php will be loaded here -->
    </div>
</div>

<!-- Full-screen Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="flex flex-col items-center">
        <svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-4 text-white text-lg">Generating Report...</p>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tallyListContainer = document.getElementById('tally-list-container');
        const reportViewContainer = document.getElementById('report-view-container');
        const reportContent = document.getElementById('report-content');
        const backBtn = document.getElementById('back-to-tally-list-btn');
        const reportsTbody = document.getElementById('reports-tbody');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const filtersForm = document.getElementById('filters-form');

        // --- Filter Submission Logic ---
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                filtersForm.submit();
            });
        });

        // --- View Report Logic ---
        reportsTbody.addEventListener('click', (event) => {
            const viewButton = event.target.closest('.view-report-btn');
            if (!viewButton) return;

            const filePath = viewButton.dataset.path;
            const reportName = viewButton.closest('tr').querySelector('td').textContent;


            // Construct the full URL for the PDF
            const pdfUrl = `../../${filePath}?v=${new Date().getTime()}`; // Add cache-busting

            // Update the report view
            document.getElementById('report-title').textContent = reportName;
            document.getElementById('report-period-text').textContent = "Viewing submitted report";
            reportContent.innerHTML = `<object data="${pdfUrl}" type="application/pdf" width="100%" height="100%">
                <div class="p-4 text-red-500">
                    <p>Your browser does not support embedded PDFs.</p>
                    <a href="${pdfUrl}" target="_blank" class="text-blue-600 hover:underline">Click here to download or view the report.</a>
                </div>
            </object>`;

            // Switch to the report view
            tallyListContainer.classList.add('hidden');
            reportViewContainer.classList.remove('hidden');
        });


        backBtn.addEventListener('click', () => {
            // Switch back to the list view
            reportViewContainer.classList.add('hidden');
            tallyListContainer.classList.remove('hidden');
            reportContent.innerHTML = ''; // Clear the content
        });

        // --- Approve Report Logic ---
        // The event listener for approving is removed as it's no longer needed on this page.
    });
</script>