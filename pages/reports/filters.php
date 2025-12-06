<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$divisions = [];
$units = [];
$years = [];
$user_campus = $_SESSION['user_campus'] ?? null;

try {
    // Fetch all divisions
    $stmtDivisions = $pdo->query("SELECT id, division_name FROM tbl_division ORDER BY division_name ASC");
    $divisions = $stmtDivisions->fetchAll(PDO::FETCH_ASSOC);

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

    // Fetch distinct years from responses, ordered from newest to oldest
    $stmtYears = $pdo->query("SELECT DISTINCT YEAR(timestamp) as response_year FROM tbl_responses WHERE YEAR(timestamp) IS NOT NULL ORDER BY response_year DESC");
    $years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
    if (empty($years)) {
        $years[] = date('Y'); // Default to current year if no responses exist
    }
} catch (PDOException $e) {
    // Optional: log error for debugging
    // error_log("Error fetching filters: " . $e->getMessage());
}
?>
<div class="flex lg:items-center gap-1 mt-3 w-full lg:w-3/4 flex-col lg:flex-row">
    <span class="font-semibold">FILTERS:</span>

    <div class="flex-grow">
        <label for="filter_division" class="block text-xs font-medium text-[#48494A] dark:text-white">DIVISION</label>
        <select name="filter_division" id="filter_division" class="dark:text-white dark:bg-gray-900 border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] ">
            <option value="" hidden>Division</option>
            <?php foreach ($divisions as $division) : ?>
                <option value="<?php echo htmlspecialchars($division['id']); ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex-grow">
        <label for="filter_unit" class="block text-xs font-medium text-[#48494A] dark:text-white">OFFICE</label>
        <select name="filter_unit" id="filter_unit" class="dark:text-white dark:bg-gray-900 border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] ">
            <option value="" hidden>Office</option>
            <?php foreach ($units as $unit) : ?>
                <option value="<?php echo htmlspecialchars($unit['id']); ?>" data-division-id="<?php echo htmlspecialchars($unit['division_id'] ?? ''); ?>"><?php echo htmlspecialchars($unit['unit_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

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