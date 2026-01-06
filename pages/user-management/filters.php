<div class="w-full lg:w-10/12">
    <div class="flex-col lg:flex-row flex gap-2">
        <div class="relative flex-grow">
            <input type="text" id="search-input" placeholder="Search" class="border border-[#1E1E1E] py-1 pl-4 pr-10 rounded focus:border-blue-500 focus:ring-blue-500 w-full bg-[#E6E7EC] placeholder:text-[#1E1E1E]/80">
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <img src="../../resources/svg/search-icon.svg" alt="Search" class="h-5 w-5 text-gray-400">
            </div>
        </div>
        <button id="add-account-btn" class="bg-[#D6D7DC] px-4 py-1 rounded border border-[#1E1E1E]">+ <span class="font-semibold">Add Account</span></button>
    </div>

    <div class="flex-col flex lg:items-center lg:flex-row gap-2 mt-4 text-sm">
        <span class="font-semibold">FILTERS:</span>
        <div class="flex-grow w-full lg:w-auto">
            <label for="campus-filter" class="block text-xs font-medium text-[#48494A]">CAMPUS</label>
            <select name="campus_filter" id="campus-filter" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] h-7">
                <option value="" hidden>Campus</option>
                <?php foreach ($campuses as $campus) : ?>
                    <option value="<?php echo htmlspecialchars($campus); ?>"><?php echo htmlspecialchars($campus); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex-grow w-full lg:w-auto" style="flex-grow: 2;">
            <label for="unit-filter" class="block text-xs font-medium text-[#48494A]">UNIT</label>
            <select name="unit_filter" id="unit-filter" class="border border-[#1E1E1E] py-1 px-2 rounded w-full lg:w-48 bg-[#E6E7EC] h-7">
                <option value="" hidden>Unit</option>
                <?php foreach ($units as $unit) : ?>
                    <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex-grow w-full lg:w-auto" style="flex-grow: 2;">
            <label for="usertype-filter" class="block text-xs font-medium text-[#48494A]">USER TYPE</label>
            <select name="usertype_filter" id="usertype-filter" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] h-7">
                <option value="" hidden>User Type</option>
                <option value="Campus Director">Campus Director</option>
                <option value="CSS Head">CSS Head</option>
                <option value="Admin">Admin</option>
                <option value="CSS Coordinator">CSS Coordinator</option>
                <option value="Unit Head">Unit Head</option>
                <option value="DCC">DCC</option>
            </select>
        </div>

        <div class="flex-grow w-full lg:w-auto">
            <label for="date-from" class="block text-xs font-medium text-[#48494A]">DATE FROM</label>
            <input type="date" id="date-from" name="date_from" class="border border-[#1E1E1E] px-2 rounded w-full bg-[#E6E7EC] h-7 text-[#E6E7EC] focus:text-[#1E1E1E] valid:text-[#1E1E1E]">
        </div>
        <div class="flex-grow w-full lg:w-auto">
            <label for="date-to" class="block text-xs font-medium text-[#48494A]">DATE TO</label>
            <input type="date" id="date-to" name="date_to" class="border border-[#1E1E1E] px-2 rounded w-full bg-[#E6E7EC] h-7 text-[#E6E7EC] focus:text-[#1E1E1E] valid:text-[#1E1E1E]">
        </div>

        <div class="flex-grow w-full lg:w-auto">
            <label for="status-filter" class="block text-xs font-medium text-[#48494A]">STATUS</label>
            <select name="status_filter" id="status-filter" class="border border-[#1E1E1E] py-1 px-2 rounded w-full bg-[#E6E7EC] h-7">
                <option value="" hidden>Status</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
    </div>
</div>

<!-- Add Account Dialog -->
<dialog id="add-account-dialog" class="relative p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
    <!-- Loading Overlay for Dialog -->
    <div id="add-account-loader" class="hidden absolute inset-0 bg-white bg-opacity-75 z-10 flex items-center justify-center rounded-md">
        <div class="flex flex-col items-center">
            <svg class="animate-spin h-10 w-10 text-[#064089]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
    <form id="add-account-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center">Add New Account</h3>

        <div>
            <label for="add-first-name" class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" id="add-first-name" name="first_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="add-middle-name" class="block text-sm font-medium text-gray-700">Middle Name</label>
            <input type="text" id="add-middle-name" name="middle_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="add-last-name" class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" id="add-last-name" name="last_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="add-contact-number" class="block text-sm font-medium text-gray-700">Contact Number</label>
            <input type="tel" id="add-contact-number" name="contact_number" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="add-campus" class="block text-sm font-medium text-gray-700">Campus</label>
            <select id="add-campus" name="campus" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <option value="" hidden>Select Campus</option>
                <?php foreach ($campuses as $campus) : ?>
                    <option value="<?php echo htmlspecialchars($campus); ?>"><?php echo htmlspecialchars($campus); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="add-unit" class="block text-sm font-medium text-gray-700">Unit</label>
            <select id="add-unit" name="unit" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <option value="" hidden>Select a campus first</option>
            </select>
        </div>

        <div>
            <label for="add-user-type" class="block text-sm font-medium text-gray-700">User Type</label>
            <select id="add-user-type" name="type" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <option value="" hidden>Select User Type</option>
                <option value="Campus Director">Campus Director</option>
                <option value="CSS Head">CSS Head</option>
                <option value="Admin">Admin</option>
                <option value="CSS Coordinator">CSS Coordinator</option>
                <option value="Unit Head">Unit Head</option>
                <option value="DCC">DCC</option>
            </select>
        </div>

        <div>
            <label for="add-email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="add-email" name="email" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="add-password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="add-password" name="password" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="add-date-created" class="block text-sm font-medium text-gray-700">Date Created</label>
            <input type="date" id="add-date-created" name="date_created" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required readonly>
        </div>

        <div class="mt-6 flex justify-end gap-4">
            <button type="button" id="cancel-add-account" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700">Save Account</button>
        </div>
    </form>
</dialog>

<!-- Edit Account Dialog -->
<dialog id="edit-account-dialog" class="relative p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9]">
    <!-- Loading Overlay for Dialog -->
    <div id="edit-account-loader" class="hidden absolute inset-0 bg-white bg-opacity-75 z-10 flex items-center justify-center rounded-md">
        <div class="flex flex-col items-center">
            <svg class="animate-spin h-10 w-10 text-[#064089]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
    <form id="edit-account-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center">Edit Account</h3>
        <input type="hidden" id="edit-user-id" name="user_id">

        <div>
            <label for="edit-first-name" class="block text-sm font-medium text-gray-700">First Name</label>
            <input type="text" id="edit-first-name" name="first_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="edit-middle-name" class="block text-sm font-medium text-gray-700">Middle Name</label>
            <input type="text" id="edit-middle-name" name="middle_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="edit-last-name" class="block text-sm font-medium text-gray-700">Last Name</label>
            <input type="text" id="edit-last-name" name="last_name" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="edit-contact-number" class="block text-sm font-medium text-gray-700">Contact Number</label>
            <input type="tel" id="edit-contact-number" name="contact_number" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="edit-campus" class="block text-sm font-medium text-gray-700">Campus</label>
            <select id="edit-campus" name="campus" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <?php foreach ($campuses as $campus) : ?>
                    <option value="<?php echo htmlspecialchars($campus); ?>"><?php echo htmlspecialchars($campus); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="edit-unit" class="block text-sm font-medium text-gray-700">Unit</label>
            <select id="edit-unit" name="unit" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <option value="" hidden>Select a campus first</option>
            </select>
        </div>

        <div>
            <label for="edit-user-type" class="block text-sm font-medium text-gray-700">User Type</label>
            <select id="edit-user-type" name="type" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <option value="Campus Director">Campus Director</option>
                <option value="CSS Head">CSS Head</option>
                <option value="Admin">Admin</option>
                <option value="CSS Coordinator">CSS Coordinator</option>
                <option value="Unit Head">Unit Head</option>
                <option value="DCC">DCC</option>
            </select>
        </div>

        <div>
            <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="edit-email" name="email" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
        </div>

        <div>
            <label for="edit-password" class="block text-sm font-medium text-gray-700">Password (leave blank to keep current)</label>
            <input type="password" id="edit-password" name="password" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="edit-status" class="block text-sm font-medium text-gray-700">Status</label>
            <select id="edit-status" name="status" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-1 px-2 h-7 focus:border-blue-500 focus:ring-blue-500" required>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>

        <div class="mt-6 flex justify-end gap-4">
            <button type="button" id="cancel-edit-account" class="px-4 py-2 bg-[#D6D7DC] border border-[#1E1E1E] rounded shadow-sm text-sm hover:bg-gray-300">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-[#064089] text-white rounded shadow-sm text-sm hover:bg-blue-700">Update Account</button>
        </div>
    </form>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const allUnitsByCampus = <?php echo json_encode($all_units_for_dialog ?? []); ?>;

        const populateUnitDropdown = (campusDropdown, unitDropdown, selectedUnit = '') => {
            const selectedCampus = campusDropdown.value;
            unitDropdown.innerHTML = '<option value="" hidden>Select Unit</option>'; // Reset

            if (selectedCampus && allUnitsByCampus[selectedCampus]) {
                allUnitsByCampus[selectedCampus].forEach(unitName => {
                    const option = document.createElement('option');
                    option.value = unitName;
                    option.textContent = unitName;
                    if (unitName === selectedUnit) {
                        option.selected = true;
                    }
                    unitDropdown.appendChild(option);
                });
            }
        };
        const addAccountBtn = document.getElementById('add-account-btn');
        const addAccountDialog = document.getElementById('add-account-dialog');
        const cancelAddAccountBtn = document.getElementById('cancel-add-account');

        addAccountBtn.addEventListener('click', () => addAccountDialog.showModal());
        cancelAddAccountBtn.addEventListener('click', () => addAccountDialog.close());
        addAccountDialog.addEventListener('click', (e) => {
            if (e.target === addAccountDialog) {
                addAccountDialog.close();
            }
        });

        // --- Add Account: Link Campus and Unit dropdowns ---
        const addCampusDropdown = document.getElementById('add-campus');
        const addUnitDropdown = document.getElementById('add-unit');
        addCampusDropdown.addEventListener('change', () => populateUnitDropdown(addCampusDropdown, addUnitDropdown));


        const addAccountForm = document.getElementById('add-account-form');
        addAccountForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const loader = document.getElementById('add-account-loader');
            loader.classList.remove('hidden');

            const formData = new FormData(addAccountForm);
            try {
                const response = await fetch('../../function/_userManagement/_addUser.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while adding the account.');
            } finally {
                loader.classList.add('hidden');
            }
        });

        // --- Edit Account Logic ---
        const editAccountDialog = document.getElementById('edit-account-dialog');
        const editAccountForm = document.getElementById('edit-account-form');
        const cancelEditAccountBtn = document.getElementById('cancel-edit-account');
        const editCampusDropdown = document.getElementById('edit-campus');
        const editUnitDropdown = document.getElementById('edit-unit');


        document.querySelectorAll('.edit-account-btn').forEach(button => {
            button.addEventListener('click', () => {
                const data = button.dataset;

                // Populate the form
                document.getElementById('edit-user-id').value = data.userId;
                document.getElementById('edit-first-name').value = data.firstName;
                document.getElementById('edit-middle-name').value = data.middleName;
                document.getElementById('edit-last-name').value = data.lastName;
                document.getElementById('edit-contact-number').value = data.contactNumber;
                editCampusDropdown.value = data.campus;
                document.getElementById('edit-user-type').value = data.type;
                document.getElementById('edit-email').value = data.email;
                document.getElementById('edit-status').value = data.status;
                document.getElementById('edit-password').value = ''; // Clear password field

                // Populate and select the unit
                populateUnitDropdown(editCampusDropdown, editUnitDropdown, data.unit);

                editAccountDialog.showModal();
            });
        });

        cancelEditAccountBtn.addEventListener('click', () => editAccountDialog.close());
        editAccountDialog.addEventListener('click', (e) => {
            if (e.target === editAccountDialog) {
                editAccountDialog.close();
            }
        });

        // --- Edit Account: Link Campus and Unit dropdowns ---
        editCampusDropdown.addEventListener('change', () => populateUnitDropdown(editCampusDropdown, editUnitDropdown));

        editAccountForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const loader = document.getElementById('edit-account-loader');
            loader.classList.remove('hidden');

            const formData = new FormData(editAccountForm);

            try {
                const response = await fetch('../../function/_userManagement/_editUser.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the account.');
            } finally {
                loader.classList.add('hidden');
            }
        });

        // --- Filtering and Search Logic ---
        const searchInput = document.getElementById('search-input');
        const userTableBody = document.getElementById('user-table-body');
        const campusFilter = document.getElementById('campus-filter');
        const unitFilter = document.getElementById('unit-filter');
        const usertypeFilter = document.getElementById('usertype-filter');
        const dateFromFilter = document.getElementById('date-from');
        const dateToFilter = document.getElementById('date-to');
        const statusFilter = document.getElementById('status-filter');

        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.style.display = 'none';
        noResultsRow.innerHTML = `<td colspan="10" class="text-center border border-[#1E1E1ECC] p-2 bg-white">No matching users found.</td>`;

        if (userTableBody) {
            userTableBody.appendChild(noResultsRow);

            const applyFilters = () => {
                const searchTerm = searchInput.value.toLowerCase();
                const campus = campusFilter.value;
                const unit = unitFilter.value;
                const userType = usertypeFilter.value;
                const dateFrom = dateFromFilter.value;
                const dateTo = dateToFilter.value;
                const status = statusFilter.value;

                const tableRows = userTableBody.querySelectorAll('tr:not(.no-results-row)');
                let visibleRows = 0;

                tableRows.forEach(row => {
                    if (row.cells.length === 1 && row.cells[0].getAttribute('colspan') === '10') {
                        row.style.display = 'none'; // Hide initial "No users found" row
                        return;
                    }

                    const rowData = {
                        campus: row.cells[0].textContent,
                        unit: row.cells[1].textContent,
                        userType: row.cells[2].textContent,
                        dateCreated: row.cells[7].textContent,
                        status: row.cells[8].textContent.trim()
                    };

                    const rowText = row.textContent.toLowerCase();
                    let isVisible = true;

                    // Apply all filters
                    if (searchTerm && !rowText.includes(searchTerm)) isVisible = false;
                    if (campus && rowData.campus !== campus) isVisible = false;
                    if (unit && rowData.unit !== unit) isVisible = false;
                    if (userType && rowData.userType !== userType) isVisible = false;
                    if (status && rowData.status !== status) isVisible = false;

                    // Date filtering
                    if (isVisible && (dateFrom || dateTo)) {
                        const rowDate = new Date(rowData.dateCreated);
                        if (dateFrom && rowDate < new Date(dateFrom)) {
                            isVisible = false;
                        }
                        if (dateTo) {
                            const toDate = new Date(dateTo);
                            toDate.setHours(23, 59, 59, 999); // Include the whole "to" day
                            if (rowDate > toDate) {
                                isVisible = false;
                            }
                        }
                    }

                    if (isVisible) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                noResultsRow.style.display = visibleRows === 0 ? '' : 'none';
            };

            [searchInput, campusFilter, unitFilter, usertypeFilter, dateFromFilter, dateToFilter, statusFilter].forEach(el => el.addEventListener('input', applyFilters));
        }
    });
</script>