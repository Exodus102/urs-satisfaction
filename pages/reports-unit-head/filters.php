<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$years = [];

try {
    // Fetch distinct years from responses, ordered from newest to oldest
    $stmtYears = $pdo->query("SELECT DISTINCT YEAR(timestamp) as response_year FROM tbl_responses WHERE YEAR(timestamp) IS NOT NULL ORDER BY response_year DESC");
    $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
    if (empty($years)) {
        $years[] = date('Y'); // Default to current year if no responses exist
    }
} catch (PDOException $e) {
    // Optional: log error for debugging
    error_log("Error fetching filters: " . $e->getMessage());
}
?>
<div class="flex lg:items-center gap-1 mt-3 w-full lg:w-3/4 flex-col lg:flex-row">
    <span class="font-semibold">FILTERS:</span>

    <div class="flex-groww w-full lg:w-28">
        <label for="filter_year" class="block text-xs font-medium text-[#48494A] dark:text-white">YEAR</label>
        <select name="filter_year" id="filter_year" class="dark:text-white dark:bg-gray-900 border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] ">
            <option value="" hidden>Year</option>
            <?php foreach ($years as $year) : ?>
                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($year == date('Y')) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($year); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex-grow">
        <label for="filter_quarter" class="block text-xs font-medium text-[#48494A] dark:text-white">QUARTER</label>
        <select name="filter_quarter" id="filter_quarter" class="dark:text-white dark:bg-gray-900 border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] ">
            <option value="" hidden>Quarter</option>
            <option value="1">1st Quarter</option>
            <option value="2">2nd Quarter</option>
            <option value="3">3rd Quarter</option>
            <option value="4">4th Quarter</option>
        </select>
    </div>
</div>