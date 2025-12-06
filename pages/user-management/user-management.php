<?php
require_once '../../function/_databaseConfig/_dbConfig.php';

$campuses = [];
$units = [];
$all_units_for_dialog = [];
$users = [];

try {
    // Fetch campuses
    $stmtCampuses = $pdo->query("SELECT DISTINCT campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $campuses = $stmtCampuses->fetchAll(PDO::FETCH_COLUMN);

    // Fetch all users, ordered by the most recently created
    $stmtUsers = $pdo->query("SELECT * FROM credentials ORDER BY date_created DESC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Fetch distinct units for the main filter dropdown
    $stmtUnits = $pdo->query("SELECT DISTINCT unit_name FROM tbl_unit ORDER BY unit_name ASC");
    $units = $stmtUnits->fetchAll(PDO::FETCH_COLUMN);

    // Fetch all units with their campus for the add/edit dialogs and group them
    $stmtAllUnits = $pdo->query("SELECT campus_name, unit_name FROM tbl_unit ORDER BY campus_name, unit_name ASC");
    $all_units_raw = $stmtAllUnits->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_units_raw as $unit) {
        $all_units_for_dialog[$unit['campus_name']][] = $unit['unit_name'];
    }
} catch (PDOException $e) {
    // You can log the error here if needed
    // error_log($e->getMessage());
}
?>
<div class="md:p-4 px-4 pb-4 pt-10 font-sfpro h-full lg:h-auto w-full">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <span class="font-bold text-4xl">User Management</span><br>
    <span>Manage user accounts in the system.</span><br><br>

    <?php
    include 'filters.php';
    ?><br>

    <div class="w-full overflow-x-auto">
        <table class="">
            <thead class="bg-[#064089] text-white font-normal">
                <tr>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Campus</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Unit</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">User Type</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Name</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Contact Number</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Email</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Password</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Date Created</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Status</th>
                    <th class="border border-[#1E1E1ECC] font-normal py-2">Action</th>
                </tr>
            </thead>
            <tbody id="user-table-body">
                <?php if (empty($users)) : ?>
                    <tr>
                        <td colspan="10" class="text-center border border-[#1E1E1ECC] p-2 bg-white">No users found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr class="bg-white text-sm">
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($user['campus']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($user['unit'] ?? ''); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($user['type']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2">
                                <?php
                                // Combine name parts, filtering out any empty values to avoid extra spaces
                                $nameParts = array_filter([$user['first_name'], $user['middle_name'], $user['last_name']]);
                                echo htmlspecialchars(implode(' ', $nameParts));
                                ?>
                            </td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($user['contact_number']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($user['password']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo date('M d, Y', strtotime($user['date_created'])); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2 text-center">
                                <?php if (($user['status'] ?? 'Inactive') === 'Active') : ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-[#29AB87] text-white">
                                        Active
                                    </span>
                                <?php else : ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="border border-[#1E1E1ECC] p-2">
                                <button class="edit-account-btn flex items-center gap-1 bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-full text-xs font-semibold transition hover:bg-[#c2ccd6]"
                                    data-user-id="<?php echo $user['user_id']; ?>"
                                    data-first-name="<?php echo htmlspecialchars($user['first_name']); ?>"
                                    data-middle-name="<?php echo htmlspecialchars($user['middle_name']); ?>"
                                    data-last-name="<?php echo htmlspecialchars($user['last_name']); ?>"
                                    data-contact-number="<?php echo htmlspecialchars($user['contact_number']); ?>"
                                    data-campus="<?php echo htmlspecialchars($user['campus']); ?>"
                                    data-unit="<?php echo htmlspecialchars($user['unit'] ?? ''); ?>"
                                    data-type="<?php echo htmlspecialchars($user['type']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-password="<?php echo htmlspecialchars($user['password']); ?>"
                                    data-status="<?php echo htmlspecialchars($user['status'] ?? 'Inactive'); ?>">
                                    <img src="../../resources/svg/pencil.svg" alt="Edit" class="h-4 w-4">
                                    <span>Edit</span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>