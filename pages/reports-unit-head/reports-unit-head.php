<div class="p-4 dark:text-white" id="reports-list-container">
    <?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

    $units = [];
    $user_campus = $_SESSION['user_campus'] ?? null;
    $user_unit_id = $_SESSION['user_unit_id'] ?? null;

    if ($user_unit_id) {
        try {
            // Fetch only the specific unit for the logged-in user
            $stmtUnit = $pdo->prepare("SELECT u.id, u.unit_name, d.id as division_id FROM tbl_unit u LEFT JOIN tbl_division d ON u.division_name = d.division_name WHERE u.id = ?");
            $stmtUnit->execute([$user_unit_id]);
            $unit = $stmtUnit->fetch(PDO::FETCH_ASSOC);
            if ($unit) {
                $units[] = $unit; // Place the single unit into the array for the loop
            }
        } catch (PDOException $e) {
            error_log("Error fetching user's unit for reports page: " . $e->getMessage());
        }
    }
    ?>

    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <span class="text-4xl font-bold font-sfpro">Campus Unit Reports</span><br>
    <span class="">You are viewing the generated reports of available offices for this period.</span>

    <?php include "filters.php"; ?><br>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#064089] text-white font-normal dark:bg-gray-900">
                <tr>
                    <th class="border border-[#1E1E1ECC] font-normal">Office</th>
                    <th class="border border-[#1E1E1ECC] font-normal">Action</th>
                </tr>
            </thead>
            <tbody id="reports-table-body">
                <?php if (!empty($units)) : ?>
                    <?php foreach ($units as $unit) : ?>
                        <tr class="bg-white office-row dark:text-white dark:bg-gray-700" data-unit-id="<?php echo htmlspecialchars($unit['id']); ?>">
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2 text-center">
                                <div class="flex justify-center gap-2">
                                    <button class="view-report-btn bg-[#D9E2EC] flex gap-1 p-1 rounded-full w-24 justify-center text-[#064089] hover:bg-[#c2ccd6]"><img src="../../resources/svg/eye-icon.svg" alt="" srcset="">View</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr class="bg-[#F1F7F9]">
                        <td colspan="2" class="text-center border border-[#1E1E1ECC] p-2">No offices found for your campus.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for notifications -->
<div id="notification-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div id="modal-icon" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full">
                <!-- Icon will be inserted here by JS -->
            </div>
            <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900"></h3>
            <div class="mt-2 px-7 py-3">
                <p id="modal-message" class="text-sm text-gray-500"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="modal-close-btn" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="report-view-container" class="hidden p-4">
    <div class="mb-4 flex items-center">
        <button id="back-to-reports-list-btn" class="">
            <img src="../../resources/svg/back-arrow-rounded.svg" alt="" srcset="">
        </button>
        <div class="ml-4">
            <span id="report-office-name" class="text-2xl font-bold font-sfpro">Office Name</span><br>
            <span id="report-quarter-text" class="font-normal text-base">2024 4th Quarter CSS Report</span>
        </div>
    </div>
    <div id="report-content"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Debugging: Log session values to the console ---
        const userCampusForDebug = <?php echo json_encode($user_campus); ?>;
        const userUnitIdForDebug = <?php echo json_encode($user_unit_id); ?>;

        console.log("User's Campus from session:", userCampusForDebug);
        console.log("User's Unit ID from session:", userUnitIdForDebug);

        const reportsListContainer = document.getElementById('reports-list-container');
        const reportViewContainer = document.getElementById('report-view-container');
        const reportContent = document.getElementById('report-content');
        const backToReportsListBtn = document.getElementById('back-to-reports-list-btn');
        const tableBody = document.getElementById('reports-table-body');

        // Modal elements
        const modal = document.getElementById('notification-modal');
        const modalIconContainer = document.getElementById('modal-icon');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalCloseBtn = document.getElementById('modal-close-btn');

        // --- Modal Logic ---
        const showModal = (isSuccess, message) => {
            if (!modal) return;

            // Clear previous state
            modalIconContainer.innerHTML = '';
            modalIconContainer.classList.remove('bg-green-100', 'bg-red-100');

            if (isSuccess) {
                modalTitle.textContent = 'Success!';
                modalIconContainer.classList.add('bg-green-100');
                modalIconContainer.innerHTML = '<svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
            } else {
                modalTitle.textContent = 'Error!';
                modalIconContainer.classList.add('bg-red-100');
                modalIconContainer.innerHTML = '<svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            }

            modalMessage.textContent = message;
            modal.classList.remove('hidden');
        };

        modalCloseBtn.addEventListener('click', () => {
            if (modal) {
                modal.classList.add('hidden');
            }
        });

        // --- View Report Logic ---
        const loadReportView = async (officeName, quarterDisplayText, filePath) => {
            if (!reportsListContainer || !reportViewContainer || !reportContent || !officeName || !quarterDisplayText || !filePath) return;

            // Construct URL, passing the specific file path to the viewer
            const url = `../../pages/reports/view-report.php?filePath=${encodeURIComponent(filePath)}`;

            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const html = await response.text();
                reportContent.innerHTML = html;

                // Update the office name in the header
                const reportOfficeName = document.getElementById('report-office-name');
                if (reportOfficeName) {
                    reportOfficeName.textContent = officeName;
                }

                // Update the quarter text in the header
                const reportQuarterText = document.getElementById('report-quarter-text');
                if (reportQuarterText) {
                    reportQuarterText.textContent = `${quarterDisplayText} CSS Report`;
                }

                // Switch views
                reportsListContainer.classList.add('hidden');
                reportViewContainer.classList.remove('hidden');
            } catch (error) {
                console.error('Error loading report view:', error);
                reportContent.innerHTML = '<p class="text-red-500">Failed to load the report. Please try again.</p>';
            }
        };

        tableBody.addEventListener('click', async (event) => {
            const viewButton = event.target.closest('.view-report-btn');
            if (viewButton) {
                // Disable button and show loader to prevent double-clicks
                const originalButtonContent = viewButton.innerHTML;
                viewButton.disabled = true;
                viewButton.innerHTML = `
                    <svg class="animate-spin h-5 w-5 text-[#064089]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-1">Generating...</span>
                `;

                const row = viewButton.closest('tr.office-row');
                const unitId = row.dataset.unitId;
                const officeName = row.querySelector('td').textContent.trim();

                const quarterFilter = document.getElementById('filter_quarter');
                const yearFilter = document.getElementById('filter_year');
                let quarterValue = quarterFilter.value;
                let yearValue = yearFilter.value || new Date().getFullYear(); // Use selected year or default to current
                let quarterDisplay = '';

                if (quarterValue) {
                    // A quarter is selected from the dropdown
                    quarterDisplay = `${yearValue} ${quarterFilter.options[quarterFilter.selectedIndex].text}`;
                } else {
                    // No quarter selected, so we calculate the current one
                    const currentMonth = new Date().getMonth(); // 0-11
                    const currentQuarter = Math.floor(currentMonth / 3) + 1;
                    quarterValue = currentQuarter;

                    let suffix = 'th';
                    if (currentQuarter === 1) suffix = 'st';
                    else if (currentQuarter === 2) suffix = 'nd';
                    else if (currentQuarter === 3) suffix = 'rd';

                    quarterDisplay = `${yearValue} ${currentQuarter}${suffix} Quarter`;
                }

                // --- New Flow: Generate first, then view ---
                try {
                    const generateUrl = `../../pages/reports/generate-report.php?unit_id=${unitId}&quarter=${quarterValue}&year=${yearValue}`;
                    const response = await fetch(generateUrl);
                    const result = await response.json();

                    if (result.success) {
                        // On success, show the success modal and then load the viewer
                        loadReportView(officeName, quarterDisplay, result.filePath);
                    } else {
                        // On failure, show the error modal with the message from the server.
                        showModal(false, result.message || 'An unknown error occurred during PDF generation.');
                    }
                } catch (error) {
                    console.error('Error during PDF generation request:', error);
                    showModal(false, 'A network error occurred. Could not contact the server.');
                } finally {
                    // Restore the button's original state
                    viewButton.disabled = false;
                    viewButton.innerHTML = originalButtonContent;
                }
            }
        });

        backToReportsListBtn.addEventListener('click', () => {
            reportViewContainer.classList.add('hidden');
            reportsListContainer.classList.remove('hidden');
            reportContent.innerHTML = ''; // Clear content
        });
    });
</script>