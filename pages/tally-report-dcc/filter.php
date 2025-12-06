<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

$years = [];

try {
    // Fetch distinct years from responses, ordered from newest to oldest
    $stmtYears = $pdo->query("SELECT DISTINCT YEAR(timestamp) as response_year FROM tbl_responses WHERE YEAR(timestamp) IS NOT NULL ORDER BY response_year DESC");
    $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
    if (empty($years)) {
        $years[] = date('Y'); // Default to current year if no responses exist
    }
} catch (PDOException $e) {
    // You can log the error for debugging if needed
    // error_log("Error fetching years for tally report filter: " . $e->getMessage());
}
?>
<div class="flex lg:items-center gap-1 mt-3 w-full lg:w-3/4 flex-col lg:flex-row">
    <span class="font-semibold">FILTERS:</span>

    <div class="flex-groww lg:w-48 w-full">
        <label for="filter_campus" class="block text-xs font-medium text-[#48494A] dark:text-white">CAMPUS</label>
        <select name="campus" id="filter_campus" class="dark:bg-gray-900 dark:text-white filter-select border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
            <option value="" hidden>Select Campus</option>
            <?php foreach ($all_campuses as $campus_option) : ?>
                <option value="<?php echo htmlspecialchars($campus_option); ?>" <?php echo ($selected_campus == $campus_option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($campus_option); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex-groww lg:w-32 w-full">
        <label for="filter_view" class="block text-xs font-medium text-[#48494A] dark:text-white">VIEW</label>
        <select name="filter_view" id="filter_view" class="dark:bg-gray-900 dark:text-white filter-select border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
            <option value="quarterly" <?php echo (isset($filter_view) && $filter_view === 'quarterly') ? 'selected' : ''; ?>>Quarterly</option>
            <option value="monthly" <?php echo (isset($filter_view) && $filter_view === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
        </select>
    </div>
    <div class="flex-groww lg:w-28 w-full">
        <label for="filter_year" class="block text-xs font-medium text-[#48494A] dark:text-white">YEAR</label>
        <select name="filter_year" id="filter_year" class="dark:bg-gray-900 dark:text-white filter-select border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] font-bold">
            <option value="" hidden>Year</option>
            <?php foreach ($years as $year) : ?>
                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo (isset($filter_year) && $filter_year == $year) ? 'selected' : ''; ?>><?php echo htmlspecialchars($year); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>