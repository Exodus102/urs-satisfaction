<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

$campuses = [];
try {
    $stmt = $pdo->query("SELECT id, campus_name FROM tbl_campus ORDER BY campus_name ASC");
    $campuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Optional: log error
}
?>
<div class="border border-[#1E1E1E] rounded-md">
    <div class="p-4">
        <h2 class="font-bold text-lg">Campus</h2><br>
        <table class="border border-[#1E1E1ECC] w-full">
            <thead class="bg-[#064089] text-white font-normal">
                <tr>
                    <th class="border border-[#1E1E1ECC] font-normal p-2 w-16">#</th>
                    <th class="border border-[#1E1E1ECC] font-normal p-2 text-left">Campus</th>
                    <th class="border border-[#1E1E1ECC] font-normal p-2 w-48">Actions</th>
                </tr>
            </thead>
            <tbody id="campus-table-body">
                <?php if (empty($campuses)) : ?>
                    <tr>
                        <td colspan="3" class="text-center border border-[#1E1E1ECC] bg-[#F1F7F9]">No campuses found.</td>
                    </tr>
                <?php else : ?>
                    <?php $count = 1; ?>
                    <?php foreach ($campuses as $campus) : ?>
                        <tr data-campus-id="<?php echo $campus['id']; ?>" class="bg-[#F1F7F9]">
                            <td class="border border-[#1E1E1ECC] text-center p-2"><?php echo $count++; ?></td>
                            <td class="border border-[#1E1E1ECC] p-2"><?php echo htmlspecialchars($campus['campus_name']); ?></td>
                            <td class="border border-[#1E1E1ECC] p-2">
                                <div class="flex justify-center items-center gap-2">
                                    <button data-id="<?php echo $campus['id']; ?>" data-name="<?php echo htmlspecialchars($campus['campus_name']); ?>" class="edit-campus-btn flex items-center gap-1 bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-[#c2ccd6]">
                                        <img src="../../resources/svg/pencil.svg" alt="Edit" class="h-4 w-4">
                                        <span>Edit</span>
                                    </button>
                                    <button data-id="<?php echo $campus['id']; ?>" class="delete-campus-btn bg-[#FEE2E2] text-[#EF4444] px-2 py-1 rounded-md text-xs font-semibold transition hover:bg-[#fecaca]">
                                        <img src="../../resources/svg/trash-bin.svg" alt="Delete" class="h-4 w-4">
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table><br>
        <button id="add-campus-btn" class="bg-[#D6D7DC] border border-[#1E1E1ECC] px-10 inline-block rounded-md">+ <span class="font-bold">Add</span></button>
    </div>
</div>

<!-- Add Campus Dialog -->
<dialog id="add-campus-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
    <form id="add-campus-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center">Add New Campus</h3>
        <div>
            <label for="campus-name" class="block text-sm font-medium text-gray-700">CAMPUS</label>
            <input type="text" id="campus-name" name="campus_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" placeholder="Name of New Campus" required>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="cancel-add-campus" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700">Save</button>
        </div>
    </form>
</dialog>

<!-- Edit Campus Dialog -->
<dialog id="edit-campus-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
    <form id="edit-campus-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center">Edit Campus</h3>
        <input type="hidden" id="edit-campus-id" name="campus_id">
        <div>
            <label for="edit-campus-name" class="block text-sm font-medium text-gray-700">Campus Name</label>
            <input type="text" id="edit-campus-name" name="campus_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="cancel-edit-campus" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700">Update</button>
        </div>
    </form>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addCampusBtn = document.getElementById('add-campus-btn');
        const addCampusDialog = document.getElementById('add-campus-dialog');
        const cancelAddCampusBtn = document.getElementById('cancel-add-campus');
        const addCampusForm = document.getElementById('add-campus-form');

        addCampusBtn.addEventListener('click', () => addCampusDialog.showModal());
        cancelAddCampusBtn.addEventListener('click', () => addCampusDialog.close());
        addCampusDialog.addEventListener('click', (e) => e.target === addCampusDialog && addCampusDialog.close());

        addCampusForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const formData = new FormData(addCampusForm);
                const response = await fetch('../../function/_entityManagement/_addCampus.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const result = await response.json();
                alert(result.message);
                if (result.success) window.location.reload();
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('An error occurred while saving the campus. Please check the console for details.');
            }
        });

        // --- Edit Campus Logic ---
        const editCampusDialog = document.getElementById('edit-campus-dialog');
        const editCampusForm = document.getElementById('edit-campus-form');
        const cancelEditCampusBtn = document.getElementById('cancel-edit-campus');
        const editCampusIdInput = document.getElementById('edit-campus-id');
        const editCampusNameInput = document.getElementById('edit-campus-name');
        const editCampusBtns = document.querySelectorAll('.edit-campus-btn');

        editCampusBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const campusId = btn.dataset.id;
                const campusName = btn.dataset.name;

                editCampusIdInput.value = campusId;
                editCampusNameInput.value = campusName;

                editCampusDialog.showModal();
            });
        });

        cancelEditCampusBtn.addEventListener('click', () => editCampusDialog.close());
        editCampusDialog.addEventListener('click', (e) => e.target === editCampusDialog && editCampusDialog.close());

        editCampusForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const formData = new FormData(editCampusForm);
                const response = await fetch('../../function/_entityManagement/_editCampus.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const result = await response.json();
                alert(result.message);
                if (result.success) window.location.reload();
            } catch (error) {
                console.error('Error updating campus:', error);
                alert('An error occurred while updating the campus. Please check the console for details.');
            }
        });

        // --- Delete Campus Logic ---
        const deleteCampusBtns = document.querySelectorAll('.delete-campus-btn');
        deleteCampusBtns.forEach(btn => {
            btn.addEventListener('click', async () => {
                const campusId = btn.dataset.id;
                if (confirm('Are you sure you want to delete this campus? This action cannot be undone.')) {
                    try {
                        const formData = new FormData();
                        formData.append('campus_id', campusId);

                        const response = await fetch('../../function/_entityManagement/_deleteCampus.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        alert(result.message);
                        if (result.success) window.location.reload();
                    } catch (error) {
                        console.error('Error deleting campus:', error);
                        alert('An error occurred while deleting the campus.');
                    }
                }
            });
        });
    });
</script>