<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$divisions = [];
$units_by_name = [];
$units = []; // Units for the main table filters, based on selected campus
$all_units_for_add_dialog = []; // All units for the add response dialog
$years = [];
$active_questions = [];
$customer_types = [];
$user_campus = $_SESSION['user_campus'] ?? null; // Logged-in user's campus
$total_responses = 0;
$total_pages = 1;
$current_page = isset($_GET['data_page']) ? (int)$_GET['data_page'] : 1;
$rows_per_page = 10;

// --- Get Filter Values ---
$filter_division_id = $_GET['filter_division'] ?? null;
$filter_campus_id = $_GET['filter_campus'] ?? $user_campus; // New: Campus filter, defaults to user's campus
$filter_unit_id = $_GET['filter_unit'] ?? null;
$filter_year = $_GET['filter_year'] ?? null;
$filter_quarter = $_GET['filter_quarter'] ?? null;


try {
    // Fetch all divisions
    $stmtDivisions = $pdo->query("SELECT id, division_name FROM tbl_division ORDER BY division_name ASC");
    $divisions = $stmtDivisions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch units for the user's campus, along with their division ID
    // Fetch ALL units with their division ID and campus for the add response dialog
    $stmtAllUnits = $pdo->query("
        SELECT u.id, u.unit_name, u.campus_name, d.id as division_id, d.division_name
        FROM tbl_unit u
        LEFT JOIN tbl_division d ON u.division_name = d.division_name
        ORDER BY u.campus_name, u.unit_name ASC
    ");
    $all_units_for_add_dialog = $stmtAllUnits->fetchAll(PDO::FETCH_ASSOC);

    // Fetch units based on selected campus (or user's campus if no filter) for the main table filters
    if ($filter_campus_id) {
        $stmtUnits = $pdo->prepare("
            SELECT u.id, u.unit_name, d.id as division_id 
            FROM tbl_unit u 
            LEFT JOIN tbl_division d ON u.division_name = d.division_name
            WHERE u.campus_name = ? 
            ORDER BY u.unit_name ASC
        ");
        $stmtUnits->execute([$filter_campus_id]);
        $units = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);
        foreach ($units as $unit) {
            // Create a lookup map by unit name for efficient processing later
            $units_by_name[$unit['unit_name']] = $unit; // This is for internal processing, not directly for JS
        }
    }

    // Fetch all customer types for the dropdown
    $stmtCustomerTypes = $pdo->query("SELECT customer_type FROM tbl_customer_type ORDER BY customer_type ASC");
    $customer_types = $stmtCustomerTypes->fetchAll(PDO::FETCH_COLUMN);

    // Fetch active, answerable questions for the "Add Response" dialog.
    // Includes Multiple Choice, Dropdown, and Text types, but excludes Description.
    $stmt_active_questions = $pdo->prepare("
        SELECT question_id, question
        FROM tbl_questionaire
        WHERE status = 1 AND question_type != 'Description'
        ORDER BY CASE WHEN question_id = 1 THEN 0 ELSE 1 END, question_id ASC");
    $stmt_active_questions->execute();
    $active_questions = $stmt_active_questions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all campuses for the new dropdown
    $all_campuses = [];
    $stmt_all_campuses = $pdo->query("SELECT campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $all_campuses = $stmt_all_campuses->fetchAll(PDO::FETCH_COLUMN);

    if ($filter_campus_id) {
        // --- Build the query to get filtered response_ids ---
        $base_sql = "SELECT DISTINCT r.response_id FROM tbl_responses r";
        $joins = " JOIN tbl_responses r_campus ON r.response_id = r_campus.response_id AND r_campus.question_id = -1 AND r_campus.response = :filter_campus_id";
        $where = " WHERE 1=1";
        $params = [':filter_campus_id' => $filter_campus_id];

        if ($filter_year) {
            $where .= " AND YEAR(r.timestamp) = :year";
            $params[':year'] = $filter_year;
        }
        if ($filter_quarter) {
            $where .= " AND QUARTER(r.timestamp) = :quarter";
            $params[':quarter'] = $filter_quarter;
        }
        if ($filter_unit_id) {
            $joins .= " JOIN tbl_responses r_unit ON r.response_id = r_unit.response_id AND r_unit.question_id = -3 AND r_unit.response = (SELECT unit_name FROM tbl_unit WHERE id = :unit_id)";
            $params[':unit_id'] = $filter_unit_id;
        }
        if ($filter_division_id && !$filter_unit_id) {
            $joins .= " JOIN tbl_responses r_div ON r.response_id = r_div.response_id AND r_div.question_id = -2 AND r_div.response = (SELECT division_name FROM tbl_division WHERE id = :division_id)";
            $params[':division_id'] = $filter_division_id;
        }

        // --- Get total count for pagination ---
        $count_sql = "SELECT COUNT(DISTINCT r.response_id) FROM tbl_responses r" . $joins . $where;
        $stmt_count = $pdo->prepare($count_sql);
        $stmt_count->execute($params);
        $total_responses = $stmt_count->fetchColumn();
        $total_pages = ceil($total_responses / $rows_per_page);

        // --- Get the response_ids for the current page ---
        $offset = ($current_page - 1) * $rows_per_page;
        $id_sql = $base_sql . $joins . $where . " ORDER BY r.response_id DESC LIMIT :limit OFFSET :offset";
        $stmt_ids = $pdo->prepare($id_sql);
        foreach ($params as $key => &$val) {
            $stmt_ids->bindParam($key, $val);
        }
        $stmt_ids->bindParam(':limit', $rows_per_page, PDO::PARAM_INT);
        $stmt_ids->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt_ids->execute();
        $paged_response_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

        $responses_raw = [];
        if (!empty($paged_response_ids)) {
            // --- Fetch full data only for the paged response_ids ---
            $in_clause = implode(',', array_fill(0, count($paged_response_ids), '?'));
            $data_sql = "SELECT * FROM tbl_responses WHERE response_id IN ($in_clause) ORDER BY response_id DESC, id DESC";
            $stmt_data = $pdo->prepare($data_sql);
            $stmt_data->execute($paged_response_ids);
            $responses_raw = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $responses_raw = []; // No campus, no data
    }

    // Step 2: Process the raw data in PHP to correctly group and interpret it.
    $grouped_responses = [];
    foreach ($responses_raw as $row) {
        $response_id = $row['response_id'];
        // Initialize the group if it's the first time we see this response_id
        if (!isset($grouped_responses[$response_id])) {
            $grouped_responses[$response_id] = [
                'id' => $response_id,
                'timestamp' => $row['timestamp'],
                'comment' => $row['comment'],
                'analysis' => $row['analysis'],
                'campus' => null,
                'division_name' => null,
                'unit_name' => null,
                'customer_type' => null,
                'unit_id' => null, // Will be determined from metadata
                'division_id' => null, // Will be determined from metadata
                'responses' => [],
            ];
        }

        if ($row['question_id'] > 0) {
            // This is a real answer to a question
            $grouped_responses[$response_id]['responses'][$row['question_id']] = $row['response'];
        } else {
            // This is metadata, so we assign it to the correct property
            switch ($row['question_id']) {
                case -1:
                    $grouped_responses[$response_id]['campus'] = $row['response'];
                    break;
                case -2:
                    $grouped_responses[$response_id]['division_name'] = $row['response'];
                    break;
                case -3:
                    $unit_name = $row['response'];
                    $grouped_responses[$response_id]['unit_name'] = $unit_name;
                    // Find unit_id and division_id from the $all_units_for_add_dialog array
                    $found_unit = array_filter($all_units_for_add_dialog, function($u) use ($unit_name) {
                        return $u['unit_name'] === $unit_name;
                    });
                    if (!empty($found_unit)) {
                        $first_found = reset($found_unit);
                        $grouped_responses[$response_id]['unit_id'] = $first_found['id'];
                        $grouped_responses[$response_id]['division_id'] = $first_found['division_id'];
                    }
                    break;
                case -4:
                    $grouped_responses[$response_id]['customer_type'] = $row['response'];
                    break;
            }
        }
    }

    // Get unique years from the data for the year filter
    $years = [];
    if (!empty($grouped_responses)) {
        $timestamps = array_column($grouped_responses, 'timestamp');
        $years = array_unique(array_map(function ($ts) {
            return date('Y', strtotime($ts));
        }, $timestamps));
        rsort($years); // Sort years in descending order (e.g., 2025, 2024)
    }
} catch (PDOException $e) {
    error_log("Error fetching data for data-response: " . $e->getMessage());
}
?>
<div class="p-4 w-full lg:h-full">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <!-- Header -->
    <div class="mb-6 w-full">
        <h1 class="text-4xl font-bold text-[#1E1E1E]">Data Responses</h1>
        <p class="text-[#1E1E1E]">
            You are viewing the responses from the survey questionnaire currently in use.
        </p>
    </div>

    <!-- Filters -->
    <div class="flex xl:items-end mb-6 w-full justify-between flex-col xl:flex-row gap-4 xl:gap-0">
        <div class="flex lg:items-end gap-1 lg:flex-row flex-col">
            <span class="font-semibold text-gray-700">FILTERS:</span>
            <form id="data-response-filters-form" method="GET" class="flex lg:items-end gap-1 lg:flex-row flex-col">
                <input type="hidden" name="page" value="data-response-css-head">
                <div class="lg:w-72 w-full">
                    <label for="filter_campus" class="block text-xs font-medium text-[#48494A]">CAMPUS</label>
                    <select name="filter_campus" id="filter_campus" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
                        <option value="" hidden>Campuses</option>
                        <?php foreach ($all_campuses as $campus_option) : ?>
                            <option value="<?php echo htmlspecialchars($campus_option); ?>" <?php echo ($filter_campus_id == $campus_option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($campus_option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:w-72 w-full">
                    <label for="filter_division" class="block text-xs font-medium text-[#48494A]">DIVISION</label>
                    <select name="filter_division" id="filter_division" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
                        <option value="">All Divisions</option>
                        <?php foreach ($divisions as $division) : ?>
                            <option value="<?php echo htmlspecialchars($division['id']); ?>" <?php echo ($filter_division_id == $division['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($division['division_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:w-72 w-full">
                    <label for="filter_unit" class="block text-xs font-medium text-[#48494A]">OFFICE</label>
                    <select name="filter_unit" id="filter_unit" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
                        <option value="">All Offices</option>
                        <?php foreach ($units as $unit) : ?>
                            <option value="<?php echo htmlspecialchars($unit['id']); ?>" data-division-id="<?php echo htmlspecialchars($unit['division_id'] ?? ''); ?>" <?php echo ($filter_unit_id == $unit['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($unit['unit_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- <div class="flex-grow">
                    <label for="filter_year" class="block text-xs font-medium text-[#48494A]">YEAR</label>
                    <select name="filter_year" id="filter_year" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year) : ?>
                            <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($filter_year == $year) ? 'selected' : ''; ?>><?php echo htmlspecialchars($year); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div> -->
                <div class="flex-grow">
                    <label for="filter_quarter" class="block text-xs font-medium text-[#48494A]">QUARTER</label>
                    <select name="filter_quarter" id="filter_quarter" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
                        <option value="">All Quarters</option>
                        <option value="1" <?php echo ($filter_quarter == 1) ? 'selected' : ''; ?>>1st Quarter</option>
                        <option value="2" <?php echo ($filter_quarter == 2) ? 'selected' : ''; ?>>2nd Quarter</option>
                        <option value="3" <?php echo ($filter_quarter == 3) ? 'selected' : ''; ?>>3rd Quarter</option>
                        <option value="4" <?php echo ($filter_quarter == 4) ? 'selected' : ''; ?>>4th Quarter</option>
                    </select>
                </div>
            </form>
        </div>

        <!--<div class="flex items-center gap-2">
             Add Response button 
            <button id="add-response-btn" class="bg-[#D6D7DC] border border-[#1E1E1E] px-4 py-2 rounded shadow-sm text-sm flex items-center h-7">
                Add Response
            </button>
             Upload CSV button 
            <button id="upload-csv-btn" class="bg-[#D6D7DC] border border-[#1E1E1E] px-4 py-2 rounded shadow-sm text-sm flex items-center h-7">
                <img src="../../resources/svg/upload-data-window.svg" alt="Upload CSV" class="mr-2">
                Upload CSV
            </button>
        </div> -->
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-300 rounded-lg w-full overflow-x-auto">
        <table class="border-collapse w-full table-fixed">
            <thead>
                <tr class="bg-[#064089] text-[#F1F7F9] text-sm font-semibold">
                    <th class="px-4 py-2 border border-gray-300 w-16">ID</th>
                    <th class="px-4 py-2 border border-gray-300 w-40">Timestamp</th>
                    <th class="px-4 py-2 border border-gray-300 w-32">Campus</th>
                    <th class="px-4 py-2 border border-gray-300 w-48">Division</th>
                    <th class="px-4 py-2 border border-gray-300 w-48">Office</th>
                    <th class="px-4 py-2 border border-gray-300 w-36">Customer Type</th>
                    <th class="px-4 py-2 border border-gray-300 w-48 truncate">Purpose of Visit</th>
                    <?php foreach ($active_questions as $question) : ?>
                        <?php
                        if ($question['question_id'] == 1) continue;
                        ?>
                        <th class="px-4 py-2 border border-gray-300 w-48"><?php echo htmlspecialchars($question['question']); ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="px-4 py-2 border border-gray-300 w-64">Comments & Suggestions</th>
                    <th class="px-4 py-2 border border-gray-300 w-28">Analysis</th>
                </tr>
            </thead>
            <tbody id="response-table-body">
                <?php if (empty($grouped_responses)) : ?>
                    <tr class="no-results-row">
                        <td colspan="<?php echo count($active_questions) + 8; ?>" class="px-4 py-3 border border-gray-300 text-center text-gray-500">No responses found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($grouped_responses as $group) : ?>
                        <?php
                        $unit_id = $group['unit_id'] ?? null;
                        $division_id = $group['division_id'] ?? '';
                        ?>
                        <tr class="response-row" data-unit-id="<?php echo htmlspecialchars($unit_id); ?>" data-division-id="<?php echo htmlspecialchars($division_id); ?>" data-year="<?php echo date('Y', strtotime($group['timestamp'])); ?>" data-quarter="<?php echo ceil(date('n', strtotime($group['timestamp'])) / 3); ?>">
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars($group['id']); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars(date('m/d/Y H:i:s', strtotime($group['timestamp']))); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars($group['campus'] ?? ''); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars($group['division_name'] ?? ''); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars($group['unit_name'] ?? ''); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars($group['customer_type'] ?? ''); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate">
                                <?php echo isset($group['responses'][1]) ? htmlspecialchars($group['responses'][1]) : ''; ?>
                            </td>
                            <?php foreach ($active_questions as $question) : ?>
                                <?php if ($question['question_id'] == 1) continue; ?>
                                <td class="px-4 py-2 border border-gray-300 text-center truncate">
                                    <?php echo isset($group['responses'][$question['question_id']]) ? htmlspecialchars($group['responses'][$question['question_id']]) : ''; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="px-4 py-2 border border-gray-300 text-center truncate"><?php echo htmlspecialchars($group['comment']); ?></td>
                            <td class="px-4 py-2 border border-gray-300 text-center">
                                <?php
                                $analysis = htmlspecialchars($group['analysis']);
                                $colorClass = 'bg-gray-400'; // Neutral/Default
                                if ($analysis === 'Positive' || $analysis === 'positive') $colorClass = 'bg-green-500';
                                if ($analysis === 'Negative' || $analysis === 'negative') $colorClass = 'bg-red-500';
                                ?>
                                <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo $colorClass; ?> text-white">
                                    <?php echo $analysis; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="no-results-row" style="display: none;">
                    <td colspan="<?php echo count($active_questions) + 8; ?>" class="px-4 py-3 border border-gray-300 text-center text-gray-500">No matching responses found.</td>
                </tr>
            </tbody>
        </table>
    </div>


    <!-- Pagination -->
    <div class="flex items-end gap-4 mt-4 text-sm">
        <!-- Previous -->
        <a href="?page=data-response&data_page=<?php echo max(1, $current_page - 1); ?>&<?php echo http_build_query(compact('filter_division_id', 'filter_unit_id', 'filter_year', 'filter_quarter')); ?>" id="pagination-prev" class="border border-[#1E1E1E] py-1 px-3 rounded bg-[#E6E7EC] <?php echo $current_page <= 1 ? 'text-gray-400 pointer-events-none' : 'text-gray-700'; ?>">
            &lt; Previous
        </a>

        <!-- Current Page -->
        <div>
            <span id="pagination-current-page" class="inline-block text-center border border-[#1E1E1E] py-1 px-4 rounded bg-white">
                <?php echo $current_page; ?>
            </span>
        </div>

        <!-- Next -->
        <a href="?page=data-response&data_page=<?php echo min($total_pages, $current_page + 1); ?>&<?php echo http_build_query(compact('filter_division_id', 'filter_unit_id', 'filter_year', 'filter_quarter')); ?>" id="pagination-next" class="border border-[#1E1E1E] py-1 px-3 rounded bg-[#E6E7EC] <?php echo $current_page >= $total_pages ? 'text-gray-400 pointer-events-none' : 'text-gray-700'; ?>">
            Next Page &gt;
        </a>

        <span class="ml-4 text-gray-600">
            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> (Total: <?php echo $total_responses; ?> responses)
        </span>
    </div>

    <!-- Upload CSV Dialog -->
    <dialog id="upload-csv-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
        <form id="upload-csv-form" method="POST" class="space-y-4">
            <h3 class="font-bold text-lg mb-4 text-center">Upload CSV File</h3>
            <p class="text-sm text-gray-600 text-center">Select a CSV file containing response data. Ensure the columns match the required format and order.</p>

            <!-- Download Excel Template Link -->
            <div class="text-center">
                <a href="../../upload/csv-template/csv-template.csv" download="csv-template.csv" class="text-sm text-blue-600 hover:underline font-medium inline-flex items-center gap-1">
                    <img src="../../resources/svg/download-outline.svg" alt="Download" class="w-4 h-4">
                    Download CSV Template
                </a>
            </div>

            <div id="csv-drop-zone" class="flex flex-col items-center justify-center w-full">
                <label for="csv-file-input" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors bg-[#749DC8]/20">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                        <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                        </svg>
                        <p id="csv-drop-zone-text" class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500">CSV files (MAX. 5MB)</p>
                    </div>
                    <input type="file" id="csv-file-input" name="csv_file" class="hidden" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </label>
            </div>
            <!-- Buttons removed for automatic upload -->
            <button type="button" id="cancel-upload-csv" class="hidden">Cancel</button> <!-- Hidden but kept for programmatic closing if needed -->
        </form>
    </dialog>

    <!-- Add Response Dialog -->
    <dialog id="add-response-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-7xl bg-[#F1F7F9]">
        <form id="add-response-form" method="POST" class="space-y-4">
            <h3 class="font-bold text-lg mb-4 text-center">Add New Response</h3>
            <div class="max-h-[60vh] overflow-x-auto p-1 border rounded-md bg-white">
                <table class="min-w-full border-collapse table-fixed w-full">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr id="add-response-header">
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Timestamp</th>
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Division</th>
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Office</th>
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Customer Type</th>
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Purpose of Visit</th>
                            <?php foreach ($active_questions as $question) : ?>
                                <?php if ($question['question_id'] == 1) continue; ?>
                                <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52"><?php echo htmlspecialchars($question['question']); ?></th>
                            <?php endforeach; ?>
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Comments & Suggestions</th>
                            <th class="p-2 text-center font-semibold text-gray-700 border align-top w-52">Analysis</th>
                        </tr>
                    </thead>
                    <tbody id="add-response-body">
                        <?php if (empty($active_questions)) : ?>
                            <tr>
                                <td class="p-4 text-center text-gray-500">No active, answerable questions found to create a response.</td>
                            </tr>
                        <?php else : ?>
                            <tr class="response-entry-row">
                                <td class="p-1 border align-top">
                                    <input type="datetime-local" name="answers[0][timestamp]" class="w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500" required>
                                </td>
                                <td class="p-1 border align-top">
                                    <select name="answers[0][-2]" class="response-division-select w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500" required>
                                        <option value="" hidden>Select Division</option>
                                        <?php foreach ($divisions as $division) : ?>
                                            <option value="<?php echo htmlspecialchars($division['division_name']); ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="p-1 border align-top">
                                    <select name="answers[0][-3]" class="response-office-select w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500" required>
                                        <option value="" hidden>Select Office</option>
                                    </select>
                                </td>
                                <td class="p-1 border align-top">
                                    <select name="answers[0][-4]" class="w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500" required>
                                        <option value="" hidden>Select Customer Type</option>
                                        <?php foreach ($customer_types as $type) : ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="p-1 border align-top"><input type="text" name="answers[0][1]" class="w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500" required></td>
                                <?php foreach ($active_questions as $question) : ?>
                                    <?php if ($question['question_id'] == 1) continue; ?>
                                    <td class="p-1 border align-top"><input type="text" name="answers[0][<?php echo $question['question_id']; ?>]" class="w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500"></td>
                                <?php endforeach; ?>
                                <td class="p-1 border align-top"><input type="text" name="answers[0][comment]" class="w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500"></td>
                                <td class="p-1 border align-top"><input type="text" name="answers[0][analysis]" class="w-full px-2 py-1 h-full bg-transparent border-b border-gray-400 focus:outline-none focus:border-blue-500"></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <button type="button" id="add-new-response-row" class="px-4 py-2 bg-green-100 text-green-800 rounded shadow-sm text-sm hover:bg-green-200 font-semibold" <?php echo empty($active_questions) ? 'disabled' : ''; ?>>+ Add New Row</button>
                <div>
                    <button type="button" id="cancel-add-response" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700" <?php echo empty($active_questions) ? 'disabled' : ''; ?>>Save Response</button>
                </div>
            </div>
        </form>
    </dialog>
    <?php
    // Add the loading overlay
    echo '
    <!-- Full-screen Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="flex flex-col items-center">
            <svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-white text-lg">Loading...</p>
        </div>
    </div>';
    include 'data-response-script.php';
    ?>