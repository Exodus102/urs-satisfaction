<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // This is already in tally-results.php, but safe to have here.
}

$divisions = [];
$years = [];

try {
    // Fetch all divisions
    $stmtDivisions = $pdo->query("SELECT id, division_name FROM tbl_division ORDER BY division_name ASC");
    $divisions = $stmtDivisions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch distinct years from responses, ordered from newest to oldest
    $stmtYears = $pdo->query("SELECT DISTINCT YEAR(timestamp) as response_year FROM tbl_responses WHERE YEAR(timestamp) IS NOT NULL ORDER BY response_year DESC");
    $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Optional: log error for debugging
    // error_log("Error fetching filters for tally-results: " . $e->getMessage());
}
?>
<form id="tally-filters-form" method="GET" action="">
    <input type="hidden" name="page" value="tally-results">
    <div class="bg-[#E6E7EC] lg:pt-4 mb-4 pt-4 dark:bg-gray-800">
        <div class="flex lg:flex-row lg:items-end gap-2 flex-col">
            <span class="font-semibold text-[#1E1E1E] lg:self-center dark:text-white">FILTERS:</span>

            <!-- Division -->
            <div class="flex flex-col w-full lg:w-44">
                <label for="filter_division" class="text-xs text-[#48494A] uppercase dark:text-white">Division</label>
                <select id="filter_division" name="filter_division" class="dark:bg-gray-900 dark:text-white filter-select border border-black bg-[#E6E7EC] rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Divisions</option>
                    <?php foreach ($divisions as $division) : ?>
                        <option value="<?php echo htmlspecialchars($division['id']); ?>" <?php echo (isset($filter_division_id) && $filter_division_id == $division['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($division['division_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Year -->
           <!-- <div class="flex flex-col w-full lg:w-20">
                <label for="filter_year" class="text-xs text-[#48494A] uppercase dark:text-white">Year</label>
                <select id="filter_year" name="filter_year" class="dark:bg-gray-900 dark:text-white filter-select border border-black bg-[#E6E7EC] rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" hidden>Year</option>
                    <?php
                    if (empty($years)) $years[] = date('Y');
                    foreach ($years as $year) : ?>
                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo (isset($filter_year) && $filter_year == $year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div> -->

            <!-- Quarter -->
            <div class="flex flex-col w-full lg:w-28">
                <label for="filter_quarter" class="text-xs text-[#48494A] uppercase dark:text-white">Quarter</label>
                <select id="filter_quarter" name="filter_quarter" class="dark:bg-gray-900 dark:text-white filter-select border border-black bg-[#E6E7EC] rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Quarter</option>
                    <option value="1" <?php echo (isset($filter_quarter) && $filter_quarter == 1) ? 'selected' : ''; ?>>1st Quarter</option>
                    <option value="2" <?php echo (isset($filter_quarter) && $filter_quarter == 2) ? 'selected' : ''; ?>>2nd Quarter</option>
                    <option value="3" <?php echo (isset($filter_quarter) && $filter_quarter == 3) ? 'selected' : ''; ?>>3rd Quarter</option>
                    <option value="4" <?php echo (isset($filter_quarter) && $filter_quarter == 4) ? 'selected' : ''; ?>>4th Quarter</option>
                </select>
            </div>

            <!-- Month -->
            <div class="flex flex-col lg:w-30">
                <label for="filter_month" class="text-xs text-[#48494A] uppercase dark:text-white">Month</label>
                <select id="filter_month" name="filter_month" class="dark:bg-gray-900 dark:text-white filter-select border border-black bg-[#E6E7EC] rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">View All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++) :
                        $month_name = date('F', mktime(0, 0, 0, $m, 1));
                        $is_selected = (isset($filter_month) && $filter_month == $m) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $m; ?>" <?php echo $is_selected; ?>><?php echo $month_name; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('tally-filters-form');
        const quarterFilter = document.getElementById('filter_quarter');
        const monthFilter = document.getElementById('filter_month');

        // Handle interaction between Quarter and Month filters
        quarterFilter.addEventListener('change', function() {
            // If a quarter is selected, reset the month filter
            if (this.value !== '') {
                monthFilter.value = ''; // This corresponds to "View All Months"
            }
            form.submit();
        });

        monthFilter.addEventListener('change', function() {
            // If any month option is selected (including "View All Months"), reset the quarter filter.
            quarterFilter.value = ''; // This corresponds to the "Quarter" placeholder
            form.submit();
        });

        // Add listeners for other filters that don't have special interactions
        ['filter_division', 'filter_year'].forEach(id => {
            document.getElementById(id).addEventListener('change', function() {
                form.submit(); // Submit the form on any filter change
            });
        });
    });
</script>