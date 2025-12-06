<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$divisions = [];
$units = [];
$user_campus = $_SESSION['user_campus'] ?? null;
$ncar_data = [];
$years = [];

// --- Get Filter Values ---
$filter_division_id = $_GET['division'] ?? null;
$filter_office_id = $_GET['office'] ?? null;
$filter_quarter = $_GET['quarter'] ?? (date('n') <= 3 ? 1 : (date('n') <= 6 ? 2 : (date('n') <= 9 ? 3 : 4)));

try {
    // Fetch all divisions
    $stmtDivisions = $pdo->query("SELECT id, division_name FROM tbl_division ORDER BY division_name ASC");
    $divisions = $stmtDivisions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch distinct years from responses for the filter
    $stmtYears = $pdo->query("SELECT DISTINCT YEAR(timestamp) as response_year FROM tbl_responses WHERE YEAR(timestamp) IS NOT NULL ORDER BY response_year DESC");
    $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
    if (empty($years)) {
        $years[] = date('Y'); // Default to current year if no responses exist
    }

    // Set the filter year. Default to the latest year with data if not specified in URL.
    $filter_year = $_GET['year'] ?? $years[0];

    // Fetch units for the user's campus
    if ($user_campus) {
        $stmtUnits = $pdo->prepare("
            SELECT u.id, u.unit_name, d.id as division_id 
            FROM tbl_unit u 
            LEFT JOIN tbl_division d ON u.division_name = d.division_name
            WHERE u.campus_name = ? 
            ORDER BY u.unit_name ASC
        ");
        $stmtUnits->execute([$user_campus]);
        $units = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Fetch NCAR Data (Offices with 'negative' analysis) ---
    if ($user_campus) {
        $sql = "
            SELECT
                u.id AS unit_id,
                u.unit_name,
                COALESCE(n.status, 'Unresolved') AS ncar_status
            FROM
                tbl_unit u
            JOIN
                tbl_responses r_office ON u.unit_name = r_office.response AND r_office.question_id = -3
            LEFT JOIN
                tbl_ncar n ON n.file_path = CONCAT(
                    'upload/pdf/ncar-report_',
                    REPLACE(REPLACE(REPLACE(u.campus_name, ' ', '-'), '/', '-'), '\\\\', '-'), '_',
                    REPLACE(REPLACE(REPLACE(u.unit_name, ' ', '-'), '/', '-'), '\\\\', '-'), '_',
                    :year, '_q', :quarter, '.pdf'
                )
            WHERE
                u.campus_name = :user_campus
                AND r_office.response_id IN (
                    SELECT r_main.response_id
                    FROM tbl_responses r_main
                    WHERE r_main.analysis = 'negative'
                    AND YEAR(r_main.timestamp) = :year
                    AND QUARTER(r_main.timestamp) = :quarter
                    AND r_main.response_id IN (
                        SELECT response_id FROM tbl_responses WHERE question_id = -1 AND response = :user_campus
                    )
                )
            GROUP BY u.id, u.unit_name, n.status
        ";

        $params = [
            ':user_campus' => $user_campus,
            ':year' => $filter_year,
            ':quarter' => $filter_quarter
        ];

        if ($filter_division_id) {
            $sql .= " AND u.division_name = (SELECT division_name FROM tbl_division WHERE id = :division_id)";
            $params[':division_id'] = $filter_division_id;
        }

        if ($filter_office_id) {
            $sql .= " AND u.id = :office_id";
            $params[':office_id'] = $filter_office_id;
        }

        $sql .= " ORDER BY u.unit_name ASC, n.id DESC";

        $stmtNcar = $pdo->prepare($sql);
        $stmtNcar->execute($params);
        $ncar_data = $stmtNcar->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // You can log the error for debugging if needed
    error_log("Error fetching data for NCAR page: " . $e->getMessage());
}
?>
<div id="ncar-list-container" class="p-4">
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
            <input type="hidden" name="page" value="ncar">
            <select name="division" id="filter_division" class="filter-select border border-black bg-[#E6E7EC] font-bold rounded pl-2 pr-20 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-left w-full lg:w-52">
                <option value="">All Divisions</option>
                <?php foreach ($divisions as $division) : ?>
                    <option value="<?php echo htmlspecialchars($division['id']); ?>" <?php echo ($filter_division_id == $division['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($division['division_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="office" id="filter_office" class="filter-select border border-black bg-[#E6E7EC] font-bold rounded pl-2 pr-20 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-left w-full lg:w-52">
                <option value="">All Offices</option>
                <?php foreach ($units as $unit) : ?>
                    <option value="<?php echo htmlspecialchars($unit['id']); ?>" data-division-id="<?php echo htmlspecialchars($unit['division_id'] ?? ''); ?>" <?php echo ($filter_office_id == $unit['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($unit['unit_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="quarter" id="filter_quarter" class="filter-select border border-black bg-[#E6E7EC] font-bold rounded pl-2 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-left">
                <?php for ($q = 1; $q <= 4; $q++) : ?>
                    <option value="<?php echo $q; ?>" <?php echo ($filter_quarter == $q) ? 'selected' : ''; ?>><?php echo $q . ($q == 1 ? 'st' : ($q == 2 ? 'nd' : ($q == 3 ? 'rd' : 'th'))); ?> Quarter</option>
                <?php endfor; ?>
            </select>

            <select name="year" id="filter_year" class="filter-select border border-black bg-[#E6E7EC] font-bold rounded pl-2 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-left">
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
            <thead class="bg-[#064089] text-white font-normal">
                <tr>
                    <th class="border px-4 py-3 border-[#1E1E1ECC] w-2/3 text-left">Office</th>
                    <th class="border px-4 py-3 border-[#1E1E1ECC] text-center">Status</th>
                    <th class="border px-4 py-3 border-[#1E1E1ECC] text-center">Action</th>
                </tr>
            </thead>
            <tbody id="ncar-table-body">
                <?php if (empty($ncar_data)) : ?>
                    <tr>
                        <td colspan="3" class="text-center p-4 border border-gray-300">No offices with negative analysis found for the selected period.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($ncar_data as $row) : ?>
                        <tr class="bg-white hover:bg-gray-50 ncar-row" data-unit-id="<?php echo htmlspecialchars($row['unit_id']); ?>">
                            <td class="border border-gray-300 p-3 office-name"><?php echo htmlspecialchars($row['unit_name']); ?></td>
                            <td class="border border-gray-300 p-3 text-center status-cell">
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
                            <td class="border border-gray-300 p-3 text-center action-cell">
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
    </div>
    <div id="ncar-content" class="h-[80vh]">
        <!-- PDF will be embedded here -->
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const divisionFilter = document.getElementById('filter_division');
        const officeFilter = document.getElementById('filter_office');
        const filtersForm = document.getElementById('ncar-filters-form');

        const ncarListContainer = document.getElementById('ncar-list-container');
        const ncarViewContainer = document.getElementById('ncar-view-container');
        const backToNcarListBtn = document.getElementById('back-to-ncar-list-btn');
        const ncarTableBody = document.getElementById('ncar-table-body');

        // --- Dynamic Office Filtering ---
        const allOfficeOptions = Array.from(officeFilter.options);

        const filterOfficeDropdown = () => {
            const selectedDivisionId = divisionFilter.value;
            const currentOfficeValue = officeFilter.value;

            // Clear current options but keep the first "All Offices" placeholder
            officeFilter.innerHTML = '';
            officeFilter.appendChild(allOfficeOptions[0]); // Re-add the "All Offices" placeholder

            allOfficeOptions.forEach(option => {
                // Skip the placeholder option as it's already there
                if (!option.value) return;

                const optionDivisionId = option.dataset.divisionId;
                if (!selectedDivisionId || !option.value || optionDivisionId === selectedDivisionId) {
                    officeFilter.appendChild(option.cloneNode(true));
                }
            });
            officeFilter.value = currentOfficeValue; // Restore selection if possible
        };

        // --- Form Submission on Filter Change ---
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                if (select.id === 'filter_division') {
                    // When division changes, reset the office filter before submitting
                    document.getElementById('filter_office').value = '';
                }
                filtersForm.submit();
            });
        });

        // Initial filter application on page load
        filterOfficeDropdown();

        // --- View Report Logic ---
        const loadNcarView = (officeName, periodText, filePath, status) => {
            const ncarContent = document.getElementById('ncar-content');
            const ncarOfficeName = document.getElementById('ncar-office-name');
            const ncarPeriodText = document.getElementById('ncar-period-text');

            ncarOfficeName.textContent = officeName;
            ncarPeriodText.textContent = periodText;

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
            const officeName = row.querySelector('.office-name').textContent.trim();
            const currentStatus = row.querySelector('.status-badge').textContent.trim();

            const year = document.getElementById('filter_year').value;
            const quarterSelect = document.getElementById('filter_quarter');
            const quarter = quarterSelect.value;
            const quarterText = quarterSelect.options[quarterSelect.selectedIndex].text;

            const periodDisplayText = `${quarterText} ${year} NCAR Report`;

            try {
                const generateUrl = `../../pages/ncar/generate-ncar-report.php?unit_id=${unitId}&year=${year}&quarter=${quarter}`;
                const response = await fetch(generateUrl);
                const result = await response.json();

                if (result.success) {
                    // The relative path from the PHP script is ../../upload/pdf/...
                    // We need to adjust it for the browser from the current page's location
                    const browserPath = `../../${result.filePath}?v=${new Date().getTime()}`; // Add cache-busting param
                    loadNcarView(officeName, periodDisplayText, browserPath, currentStatus);

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
    });
</script>