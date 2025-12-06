<?php
$quarters = [
    // Using quarter number as key for easier use in JavaScript
    1 => '1st Quarter',
    2 => '2nd Quarter',
    3 => '3rd Quarter',
    4 => '4th Quarter'
];
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];
?>
<!-- Main container for the list of quarters -->
<div id="tally-list-container" class="p-4">
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

    <?php include "filter.php"; ?>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full border-collapse">
            <thead class="bg-[#064089] text-white font-normal">
                <tr>
                    <th class="border border-[#1E1E1ECC] font-normal w-2/3">Quarter</th>
                    <th class="border border-[#1E1E1ECC] font-normal">Action</th>
                </tr>
            </thead>
            <tbody id="quarterly-tbody">
                <?php foreach ($quarters as $q_num => $q_name) : ?>
                    <tr class="bg-white">
                        <td class="border border-[#1E1E1ECC] p-3"><?php echo htmlspecialchars($q_name); ?></td>
                        <td class="border border-[#1E1E1ECC] p-3 text-center gap-2">
                            <div class="flex justify-center gap-2">
                                <button data-quarter="<?php echo $q_num; ?>" class="view-report-btn bg-[#D9E2EC] flex gap-1 p-1 w-24 rounded-full justify-center text-[#064089] hover:bg-[#c2ccd6]"><img src="../../resources/svg/eye-icon.svg" alt="" srcset="">View</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tbody id="monthly-tbody" class="hidden">
                <?php foreach ($months as $m_num => $m_name) : ?>
                    <tr class="bg-white">
                        <td class="border border-[#1E1E1ECC] p-3"><?php echo htmlspecialchars($m_name); ?></td>
                        <td class="border border-[#1E1E1ECC] p-3 text-center gap-2">
                            <div class="flex justify-center gap-2">
                                <button data-month="<?php echo $m_num; ?>" class="view-report-btn bg-[#D9E2EC] flex gap-1 p-1 w-24 rounded-full justify-center text-[#064089] hover:bg-[#c2ccd6]"><img src="../../resources/svg/eye-icon.svg" alt="" srcset="">View</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
        <div>
            <button id="submit-report-btn" class="bg-[#064089] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">Submit Report</button>
        </div>
    </div>
    <div id="report-content" class="">
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
        const viewButtons = document.querySelectorAll('.view-report-btn');
        const viewFilter = document.getElementById('filter_view');
        const yearFilter = document.getElementById('filter_year');
        const quarterlyTbody = document.getElementById('quarterly-tbody');
        const monthlyTbody = document.getElementById('monthly-tbody');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const submitReportBtn = document.getElementById('submit-report-btn');

        viewButtons.forEach(button => {
            button.addEventListener('click', async (event) => {
                const clickedButton = event.currentTarget;

                const isQuarterlyView = viewFilter.value === 'quarterly';
                const quarter = isQuarterlyView ? clickedButton.dataset.quarter : null;
                const month = !isQuarterlyView ? clickedButton.dataset.month : null;
                const year = yearFilter.value;

                let fetchUrl = `../../pages/tally-report/generate-report-tally.php?year=${year}`;
                let periodText = `Viewing report for ${year}, `;

                if (isQuarterlyView) fetchUrl += `&quarter=${quarter}`;
                else fetchUrl += `&month=${month}`;

                if (!year) {
                    alert('Please select a year from the filter first.');
                    yearFilter.focus();
                    return;
                }

                // Show the loading overlay
                loadingOverlay.classList.remove('hidden');

                try {
                    // Fetch the content from generate-report-tally.php
                    const response = await fetch(fetchUrl);
                    if (!response.ok) {
                        // If the server responded with an error status (like 404 or 500),
                        // read the response text to get the error message.
                        const errorText = await response.text();
                        throw new Error(`Server error: ${response.status} ${response.statusText}\n${errorText}`);
                    }

                    const html = await response.text();
                    reportContent.innerHTML = html;

                    if (isQuarterlyView) {
                        periodText += `Quarter ${quarter}`;
                    } else {
                        // Get month name from the button's table row
                        const monthName = clickedButton.closest('tr').querySelector('td').textContent;
                        periodText += `${monthName}`;
                    }

                    // Update the header text and switch views
                    document.getElementById('report-period-text').textContent = periodText;
                    tallyListContainer.classList.add('hidden');
                    reportViewContainer.classList.remove('hidden');
                } catch (error) {
                    // For debugging, show the detailed error in the alert.
                    const errorMessage = error.message.includes('</') ? 'An HTML error page was returned by the server.' : error.message;
                    console.error("Failed to fetch report:", error.message);
                    alert(`An error occurred while generating the report:\n\n${errorMessage}\n\nPlease check the console for more details.`);

                } finally {
                    // Hide the loading overlay
                    loadingOverlay.classList.add('hidden');
                }
            });
        });

        backBtn.addEventListener('click', () => {
            // Switch back to the list view
            reportViewContainer.classList.add('hidden');
            tallyListContainer.classList.remove('hidden');
            reportContent.innerHTML = ''; // Clear the content
        });

        viewFilter.addEventListener('change', () => {
            if (viewFilter.value === 'monthly') {
                quarterlyTbody.classList.add('hidden');
                monthlyTbody.classList.remove('hidden');
            } else {
                monthlyTbody.classList.add('hidden');
                quarterlyTbody.classList.remove('hidden');
            }
        });

        submitReportBtn.addEventListener('click', async () => {
            const reportObject = reportContent.querySelector('object');
            if (!reportObject) {
                alert('Please generate a report first before submitting.');
                return;
            }

            // The data attribute looks like: ../../upload/pdf/report.pdf?v=12345
            let pdfUrl = reportObject.getAttribute('data');
            if (!pdfUrl) {
                alert('Could not find the report file path.');
                return;
            }

            // 1. Remove the cache-busting parameter
            let cleanUrl = pdfUrl.split('?')[0];
            // 2. Remove the leading '../../' to get the path relative to the project root
            let relativePath = cleanUrl.replace('../../', '');

            loadingOverlay.querySelector('p').textContent = 'Submitting Report...';
            loadingOverlay.classList.remove('hidden');

            try {
                const response = await fetch('../../function/_tallyReport/_submitReport.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        file_path: relativePath
                    })
                });

                const result = await response.json();

                alert(result.message); // Show success or error message from the server

            } catch (error) {
                console.error('Submission failed:', error);
                alert('An error occurred while submitting the report. Please check the console.');
            } finally {
                loadingOverlay.classList.add('hidden');
            }
        });
    });
</script>