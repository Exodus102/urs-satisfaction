<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$user_campus = $_SESSION['user_campus'] ?? null;
$user_unit_id = $_SESSION['user_unit_id'] ?? null; // Get the logged-in user's unit ID
$ncar_data = [];
$years = [];

// --- Get Filter Values ---
$filter_quarter = $_GET['quarter'] ?? (date('n') <= 3 ? 1 : (date('n') <= 6 ? 2 : (date('n') <= 9 ? 3 : 4)));

try {
    // Fetch distinct years from responses for the filter
    $stmtYears = $pdo->query("SELECT DISTINCT YEAR(timestamp) as response_year FROM tbl_responses WHERE YEAR(timestamp) IS NOT NULL ORDER BY response_year DESC");
    $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
    if (empty($years)) {
        $years[] = date('Y'); // Default to current year if no responses exist
    }

    // Set the filter year. Default to the latest year with data if not specified in URL.
    $filter_year = $_GET['year'] ?? $years[0];

    // --- Fetch NCAR Data (Offices with 'negative' analysis) ---
    // Only fetch data if the user is assigned to a unit
    if ($user_unit_id) {
        $sql = "
        SELECT
            MIN(r_comment.id) AS comment_id,
            MIN(r_comment.timestamp) AS date_issued,
            SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT r_comment.comment ORDER BY r_comment.id), ',', 1) AS comment,
            u.id AS unit_id,
            u.unit_name,
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
        ";

        $params = [
            ':user_unit_id' => $user_unit_id,
            ':year' => $filter_year,
            ':quarter' => $filter_quarter
        ];

        // The GROUP BY and ORDER BY must come after all WHERE conditions
        $sql .= " ORDER BY MIN(r_comment.timestamp) DESC, u.unit_name ASC";

        $stmtNcar = $pdo->prepare($sql);
        $stmtNcar->execute($params);
        $ncar_data = $stmtNcar->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // You can log the error for debugging if needed
    error_log("Error fetching data for NCAR page: " . $e->getMessage());
}
?>
<div id="ncar-list-container" class="p-4 dark:text-white">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <div class="mb-4">
        <h1 class="text-4xl font-bold">Non-conformity and Correction Action Report</h1>
        <P class="mb-5">You are viewing the generated reports of available offices for this period.</P>

        <form id="ncar-filters-form" method="GET" class="flex lg:items-center gap-2 mb-4 flex-col lg:flex-row">
            <input type="hidden" name="page" value="ncar-unit-head">

            <select name="quarter" id="filter_quarter" class="dark:text-white dark:bg-gray-900 filter-select border border-black bg-[#E6E7EC] font-bold rounded pl-2 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-left">
                <?php for ($q = 1; $q <= 4; $q++) : ?>
                    <option value="<?php echo $q; ?>" <?php echo ($filter_quarter == $q) ? 'selected' : ''; ?>><?php echo $q . ($q == 1 ? 'st' : ($q == 2 ? 'nd' : ($q == 3 ? 'rd' : 'th'))); ?> Quarter</option>
                <?php endfor; ?>
            </select>

            <select name="year" id="filter_year" class="dark:text-white dark:bg-gray-900 filter-select border border-black bg-[#E6E7EC] font-bold rounded pl-2 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-left">
                <?php foreach ($years as $year) : ?>
                    <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($filter_year == $year) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="border border-[#1E1E1ECC] shadow-lg w-full">
            <thead class="bg-[#064089] text-white font-normal dark:text-white dark:bg-gray-900">
                <tr>
                    <th class="border px-4 py-3 border-[#1E1E1ECC] text-center">NCAR No.</th>
                    <th class="border px-4 py-3 border-[#1E1E1ECC] w-2/3 text-left">Office</th>

                    <th class="border px-4 py-3 border-[#1E1E1ECC] text-center">Date Issued</th>
                    <!--<th class="border px-4 py-3 border-[#1E1E1ECC] w-2/3 text-left">Negative Comment</th>-->
                    <th class="border px-4 py-3 border-[#1E1E1ECC] text-center">Status</th>
                    <th class="border px-4 py-3 border-[#1E1E1ECC] text-center">Action</th>
                </tr>
            </thead>
            <tbody id="ncar-table-body">
                <?php if (empty($ncar_data)) : ?>
                    <tr>
                        <td colspan="5" class="text-center p-4 border border-[#1E1E1ECC]">No negative comments found for the selected period.</td>
                    </tr>
                <?php else : ?>
                    <?php
                    $ncar_counter = 0;
                    $campus_abbr = strtoupper(substr($user_campus, 0, 3));
                    ?>
                    <?php foreach ($ncar_data as $row) : ?>
                        <?php $ncar_counter++; ?>
                        <?php $ncar_number = sprintf('CSS-%s-%03d', $campus_abbr, $ncar_counter); ?>
                        <tr class="bg-white hover:bg-gray-50 ncar-row dark:text-white dark:bg-gray-700" data-unit-id="<?php echo htmlspecialchars($row['unit_id']); ?>" data-comment-id="<?php echo htmlspecialchars($row['comment_id']); ?>">
                            <td class="border border-[#1E1E1ECC] p-3 text-center"><?php echo htmlspecialchars($ncar_number); ?></td>
                            <td class="border border-[#1E1E1ECC] p-3 office-name"><?php echo htmlspecialchars($row['unit_name']); ?></td>
                            <!-- <td class="border border-[#1E1E1ECC] p-3 comment-text"><?php echo htmlspecialchars($row['comment']); ?></td>-->

                            <td class="border border-[#1E1E1ECC] p-3 text-center"><?php echo date('F j, Y', strtotime($row['date_issued'])); ?></td>
                            <td class="border border-[#1E1E1ECC] p-3 text-center status-cell">
                                <?php
                                $status = htmlspecialchars($row['ncar_status']);
                                $status_class = 'bg-[#EE6B6E] text-white'; // Default for Unresolved
                                if ($status === 'Resolved') {
                                    $status_class = 'bg-[#29AB87] text-white';
                                } elseif ($status === 'Action Taken') {
                                    $status_class = 'bg-blue-100 text-blue-800';
                                }
                                ?>
                                <span class="status-badge px-3 py-1 font-semibold leading-tight rounded-full text-xs <?php echo $status_class; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="border border-[#1E1E1ECC] p-3 text-center action-cell">
                                <div class="flex justify-center">
                                    <button class="view-ncar-btn bg-[#D9E2EC] text-[#064089] px-6 py-1 rounded-full text-xs font-semibold transition hover:bg-[#c2ccd6] flex items-center justify-center gap-1">
                                        <img src="../../resources/svg/eye-icon.svg" alt="">View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Container for the PDF viewer, initially hidden -->
<div id="ncar-view-container" class="hidden p-4">
    <div class="flex justify-between items-center">
        <div class="mb-4 flex items-center">
            <button id="back-to-ncar-list-btn" class="">
                <img src="../../resources/svg/back-arrow-rounded.svg" alt="Back to list">
            </button>
            <div class="ml-4">
                <span id="ncar-office-name" class="text-2xl font-bold font-sfpro">Office Name</span><br>
                <span id="ncar-period-text" class="font-normal text-base">NCAR Report</span>
            </div>
        </div>
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Campus Director') : ?>
            <div>
                <button id="resolve-ncar-btn" class="bg-[#064089] text-white px-6 py-1 rounded disabled:bg-gray-400 disabled:cursor-not-allowed">Resolve</button>
            </div>
        <?php endif; ?>
    </div>
    <div id="ncar-content" class="h-[80vh]">
        <!-- PDF will be embedded here -->
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filtersForm = document.getElementById('ncar-filters-form');

        const ncarListContainer = document.getElementById('ncar-list-container');
        const ncarViewContainer = document.getElementById('ncar-view-container');
        const backToNcarListBtn = document.getElementById('back-to-ncar-list-btn');
        const ncarTableBody = document.getElementById('ncar-table-body');

        // --- Form Submission on Filter Change ---
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                filtersForm.submit();
            });
        });

        // --- View Report Logic ---
        const loadNcarView = (officeName, periodText, filePath, status, commentId) => {
            const ncarContent = document.getElementById('ncar-content');
            const ncarOfficeName = document.getElementById('ncar-office-name');
            const ncarPeriodText = document.getElementById('ncar-period-text');
            const resolveBtn = document.getElementById('resolve-ncar-btn'); // Can be null if user is not director

            ncarOfficeName.textContent = officeName;
            ncarPeriodText.textContent = periodText;

            // Only interact with the resolve button if it exists
            if (resolveBtn) {
                // Set the UNIQUE comment ID, passed directly to the function
                resolveBtn.dataset.commentId = commentId;

                // Set the initial state of the resolve button based on the status
                if (status === 'Resolved') {
                    resolveBtn.textContent = 'Resolved';
                    resolveBtn.disabled = true;
                    resolveBtn.classList.remove('bg-[#064089]');
                    resolveBtn.classList.add('bg-[#29AB87]'); // Green color for resolved
                } else {
                    resolveBtn.textContent = 'Resolve';
                    resolveBtn.disabled = false;
                    resolveBtn.classList.add('bg-[#064089]');
                    resolveBtn.classList.remove('bg-[#29AB87]');
                }
            }

            // Embed the PDF using an <object> tag for better browser compatibility
            ncarContent.innerHTML = `<object data="${filePath}" type="application/pdf" width="100%" height="100%"><p>Your browser does not support embedded PDFs. You can <a href="${filePath}" target="_blank">download the PDF here</a>.</p></object>`;

            // Switch views
            ncarListContainer.classList.add('hidden');
            ncarViewContainer.classList.remove('hidden');
        };

        ncarTableBody.addEventListener('click', async (event) => {
            const viewButton = event.target.closest('.view-ncar-btn');
            if (!viewButton) return;

            const originalButtonContent = viewButton.innerHTML;
            viewButton.disabled = true;
            viewButton.innerHTML = 'Generating...';

            const row = viewButton.closest('tr.ncar-row');
            const unitId = row.dataset.unitId;
            const commentId = row.dataset.commentId;
            const officeName = row.querySelector('.office-name').textContent.trim();
            const currentStatus = row.querySelector('.status-badge').textContent.trim();

            const year = document.getElementById('filter_year').value;
            const quarterSelect = document.getElementById('filter_quarter');
            const quarter = quarterSelect.value;
            const quarterText = quarterSelect.options[quarterSelect.selectedIndex].text;

            const periodDisplayText = `${quarterText} ${year} NCAR Report`;

            try {
                const generateUrl = `../../pages/ncar-campus-director/generate-ncar-report.php?unit_id=${unitId}&year=${year}&quarter=${quarter}&comment_id=${commentId}`;
                const response = await fetch(generateUrl);
                const result = await response.json();

                if (result.success) {
                    // The relative path from the PHP script is ../../upload/pdf/...
                    // We need to adjust it for the browser from the current page's location
                    const browserPath = `../../${result.filePath}?v=${new Date().getTime()}`;
                    loadNcarView(officeName, periodDisplayText, browserPath, currentStatus, commentId);

                    // Update the status on the list page after generation
                    const statusCell = row.querySelector('.status-cell');
                    if (statusCell) {
                        statusCell.innerHTML = `
                            <span class="status-badge px-3 py-1 font-semibold leading-tight rounded-full text-xs bg-[#EE6B6E] text-white">
                                Unresolved
                            </span>`;
                    }
                } else {
                    alert('Error generating report: ' + result.message);
                }
            } catch (error) {
                console.error('Error during NCAR generation request:', error);
                alert('A network error occurred. Could not contact the server.');
            } finally {
                // Restore the button's original state
                viewButton.disabled = false;
                viewButton.innerHTML = originalButtonContent;
            }
        });

        // --- Back Button Logic ---
        backToNcarListBtn.addEventListener('click', () => {
            ncarViewContainer.classList.add('hidden');
            ncarListContainer.classList.remove('hidden');
            document.getElementById('ncar-content').innerHTML = ''; // Clear embedded content
            // Refresh the page to show the latest status in the table
            filtersForm.submit();
        });

        // --- Resolve Button Logic ---
        const resolveBtn = document.getElementById('resolve-ncar-btn');
        // Only add the event listener if the button exists on the page
        if (resolveBtn) {
            resolveBtn.addEventListener('click', async () => {
                const commentId = resolveBtn.dataset.commentId;

                if (!commentId) {
                    alert('Error: Report identifier not found.');
                    return;
                }

                if (!confirm('Are you sure you want to mark this NCAR as Resolved?')) {
                    return;
                }

                resolveBtn.disabled = true;
                resolveBtn.textContent = 'Resolving...';

                try {
                    const response = await fetch('../../pages/ncar-campus-director/_resolveNcar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            comment_id: commentId
                        })
                    });

                    const result = await response.json();
                    alert(result.message);

                    if (result.success) {
                        // Visually update the button to show it's resolved
                        resolveBtn.textContent = 'Resolved';
                        resolveBtn.classList.remove('bg-[#064089]');
                        resolveBtn.classList.add('bg-[#29AB87]');
                    }
                } catch (error) {
                    console.error('Error resolving NCAR:', error);
                    alert('An error occurred. Please check the console.');
                    resolveBtn.disabled = false; // Re-enable on error
                    resolveBtn.textContent = 'Resolve';
                }
            });
        }
    });
</script>