<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

$divisions = [];
try {
    $stmt = $pdo->query("SELECT id, division_name FROM tbl_division ORDER BY division_name ASC");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optional: log error for debugging if the table doesn't exist
    // error_log($e->getMessage());
}
?>
<div class="border border-[#1E1E1E] rounded-md">
    <div class="p-4">
        <h2 class="font-bold text-lg">Division</h2><br>
        <table class="border border-[#1E1E1ECC] w-full">
            <thead class="bg-[#064089] text-white font-normal">
                <tr>
                    <th class="border border-[#1E1E1ECC] font-normal p-2 w-16">#</th>
                    <th class="border border-[#1E1E1ECC] font-normal p-2 text-left">Division</th>
                    <th class="border border-[#1E1E1ECC] font-normal p-2 w-48">Actions</th>
                </tr>
            </thead>
            <tbody id="division-table-body">
                <?php if (empty($divisions)) : ?>
                    <tr>
                        <td colspan="3" class="text-center border border-[#1E1E1ECC] p-2 bg-[#F1F7F9]">No divisions found.</td>
                    </tr>
                <?php else : ?>
                    <?php $count = 1; ?>
                    <?php foreach ($divisions as $division) : ?>
                        <tr data-id="<?php echo $division['id']; ?>" class="bg-[#F1F7F9]">
                            <td class="border border-[#1E1E1ECC] text-center p-2"><?php echo $count++; ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($division['division_name']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2">
                                <div class="flex justify-center items-center gap-2">
                                    <button data-id="<?php echo $division['id']; ?>" data-name="<?php echo htmlspecialchars($division['division_name']); ?>" class="edit-division-btn flex items-center gap-1 bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-[#c2ccd6]">
                                        <img src="../../resources/svg/pencil.svg" alt="Edit" class="h-4 w-4">
                                        <span>Edit</span>
                                    </button>
                                    <button data-id="<?php echo $division['id']; ?>" class="delete-division-btn bg-[#FEE2E2] text-[#EF4444] px-2 py-1 rounded-md text-xs font-semibold transition hover:bg-[#fecaca]">
                                        <img src="../../resources/svg/trash-bin.svg" alt="Delete" class="h-4 w-4">
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table><br>
        <button id="add-division-btn" class="bg-[#D6D7DC] border border-[#1E1E1ECC] px-10 inline-block rounded-md">+ <span class="font-bold">Add</span></button>
    </div>
</div>

<!-- Add Division Dialog -->
<dialog id="add-division-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
    <form id="add-division-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center">Add New Division</h3>
        <div>
            <label for="division-name" class="block text-sm font-medium text-gray-700">DIVISION</label>
            <input type="text" id="division-name" name="division_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" placeholder="Name of New Division" required>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="cancel-add-division" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700">Save</button>
        </div>
    </form>
</dialog>

<!-- Edit Division Dialog -->
<dialog id="edit-division-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
    <form id="edit-division-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center">Edit Division</h3>
        <input type="hidden" id="edit-division-id" name="division_id">
        <div>
            <label for="edit-division-name" class="block text-sm font-medium text-gray-700">Division Name</label>
            <input type="text" id="edit-division-name" name="division_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="cancel-edit-division" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700">Update</button>
        </div>
    </form>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const handleRequest = async (url, formData) => {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) window.location.reload();
        };

        // --- Add Logic ---
        const addDialog = document.getElementById('add-division-dialog');
        const addForm = document.getElementById('add-division-form');
        document.getElementById('add-division-btn').addEventListener('click', () => addDialog.showModal());
        document.getElementById('cancel-add-division').addEventListener('click', () => addDialog.close());
        addDialog.addEventListener('click', (e) => e.target === addDialog && addDialog.close());
        addForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleRequest('../../function/_entityManagement/_addDivision.php', new FormData(addForm));
        });

        // --- Edit Logic ---
        const editDialog = document.getElementById('edit-division-dialog');
        const editForm = document.getElementById('edit-division-form');
        const editIdInput = document.getElementById('edit-division-id');
        const editNameInput = document.getElementById('edit-division-name');

        document.querySelectorAll('.edit-division-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                editIdInput.value = btn.dataset.id;
                editNameInput.value = btn.dataset.name;
                editDialog.showModal();
            });
        });

        document.getElementById('cancel-edit-division').addEventListener('click', () => editDialog.close());
        editDialog.addEventListener('click', (e) => e.target === editDialog && editDialog.close());
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleRequest('../../function/_entityManagement/_editDivision.php', new FormData(editForm));
        });

        // --- Delete Logic ---
        document.querySelectorAll('.delete-division-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Are you sure you want to delete this division? This action cannot be undone.')) {
                    const formData = new FormData();
                    formData.append('division_id', btn.dataset.id);
                    handleRequest('../../function/_entityManagement/_deleteDivision.php', formData);
                }
            });
        });
    });
</script>