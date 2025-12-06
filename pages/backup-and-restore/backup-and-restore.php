<?php
require_once '../../function/_databaseConfig/_dbConfig.php';
$backups = [];
try {
    $stmt = $pdo->query("SELECT id, available_backups, version, size, timestamp FROM tbl_backup ORDER BY timestamp DESC");
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // This can happen if the table doesn't exist yet. We can ignore it on the first load.
    // error_log("Could not fetch backups: " . $e->getMessage());
}
?>
<div class="lg:p-4 px-4 pb-4 pt-10 lg:px-0 lg:pb-0 lg:pt-0">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>

    <!-- Title -->
    <h1 class="text-2xl font-bold mb-1">Backup & Restore</h1>
    <p class="text-[#1E1E1E] mb-6">Maintain data security with backup and restoration options.</p>

    <!-- Data Restore Section -->
    <div class="bg-transparent border border-black rounded-md mb-6 p-4">
        <h2 class="text-lg font-semibold">Data Restore</h2>
        <p class="text-[#1E1E1E] text-sm">Recover data from saved backups.</p><br>
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-[#064089] text-white">
                        <th class="px-3 py-2 w-10 border border-black">Select</th>
                        <th class="px-3 py-2 border border-black">#</th>
                        <th class="px-3 py-2 border border-black">Timestamp</th>
                        <th class="px-3 py-2 border border-black">Available Backups</th>
                        <th class="px-3 py-2 border border-black">Version</th>
                        <th class="px-3 py-2 border border-black">Size</th>
                        <th class="px-3 py-2 border border-black">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-[#F1F7F9] text-center">
                    <?php if (empty($backups)) : ?>
                        <tr>
                            <td colspan="7" class="px-3 py-2 border border-black text-center">No backups found.</td>
                        </tr>
                    <?php else : ?>
                        <?php $count = 1; ?>
                        <?php foreach ($backups as $backup) : ?>
                            <tr>
                                <td class="px-3 py-2 border border-black"><input type="radio" name="selected_backup" value="<?php echo htmlspecialchars($backup['id']); ?>"></td>
                                <td class="px-3 py-2 border border-black"><?php echo $count++; ?></td>
                                <td class="px-3 py-2 border border-black text-left"><?php echo date('Y/m/d H:i', strtotime($backup['timestamp'])); ?></td>
                                <td class="px-3 py-2 border border-black text-left"><?php echo htmlspecialchars($backup['available_backups']); ?></td>
                                <td class="px-3 py-2 border border-black"><?php echo htmlspecialchars($backup['version']); ?></td>
                                <td class="px-3 py-2 border border-black"><?php echo htmlspecialchars($backup['size']); ?></td>
                                <td class="px-3 py-2 border border-black">
                                    <button class="delete-backup-btn bg-[#FEE2E2] p-1 rounded-md" data-backup-id="<?php echo htmlspecialchars($backup['id']); ?>" title="Delete Backup">
                                        <img src="../../resources/svg/trash-bin.svg" alt="Delete" class="h-5 w-5">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table><br>
            <button id="restore-btn" class="bg-[#D6D7DC] px-4 rounded flex border border-[#1E1E1E] items-center gap-1 w-full lg:w-auto justify-center lg:justify-normal">
                <img src="../../resources/svg/refresh.svg" alt="Restore" class="h-5 w-5">Restore
            </button>
        </div>
    </div>

    <!-- Data Backup Section -->
    <div class="bg-transparent border border-black rounded-md p-4" id="backup-section">
        <h2 class="text-lg font-semibold">Data Backup</h2>
        <p class="text-[#1E1E1E] text-sm mb-3">Create a new backup file of this system’s current version.</p>
        <button id="backup-btn" class="bg-[#D6D7DC] px-4 rounded flex border border-[#1E1E1E] items-center gap-1 w-full lg:w-auto justify-center lg:justify-normal">
            <img src="../../resources/svg/backup.svg" alt="Restore" class="h-5 w-5">Backup
        </button>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const backupBtn = document.getElementById('backup-btn');
        const restoreBtn = document.getElementById('restore-btn');

        // --- Backup Logic ---
        backupBtn.addEventListener('click', async () => {
            if (!confirm('Are you sure you want to create a new backup?')) {
                return;
            }

            backupBtn.disabled = true;
            backupBtn.innerHTML = '<img src="../../resources/svg/backup.svg" alt="Backup" class="h-5 w-5 animate-spin">Backing up...';

            try {
                const response = await fetch('../../function/_backupAndRestore/_backup.php', {
                    method: 'POST'
                });
                const result = await response.json();

                alert(result.message);
                if (result.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Backup request failed:', error);
                alert('A network error occurred while creating the backup.');
            } finally {
                backupBtn.disabled = false;
                backupBtn.innerHTML = '<img src="../../resources/svg/backup.svg" alt="Backup" class="h-5 w-5">Backup';
            }
        });

        // --- Restore Logic ---
        restoreBtn.addEventListener('click', async () => {
            const selectedRadio = document.querySelector('input[name="selected_backup"]:checked');
            if (!selectedRadio) {
                alert('Please select a backup to restore.');
                return;
            }

            if (!confirm('WARNING: Restoring from a backup will overwrite all current data. This action cannot be undone. Are you sure you want to continue?')) {
                return;
            }

            restoreBtn.disabled = true;
            restoreBtn.innerHTML = '<img src="../../resources/svg/refresh.svg" alt="Restore" class="h-5 w-5 animate-spin">Restoring...';

            const backupId = selectedRadio.value;

            try {
                const response = await fetch('../../function/_backupAndRestore/_restore.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        backup_id: backupId
                    })
                });
                const result = await response.json();

                alert(result.message);
                if (result.success) {
                    // You might want to redirect or just inform the user
                    alert("Restore complete. Please log out and log back in to see the changes.");
                }
            } catch (error) {
                console.error('Restore request failed:', error);
                alert('A network error occurred during the restore process.');
            } finally {
                restoreBtn.disabled = false;
                restoreBtn.innerHTML = '<img src="../../resources/svg/refresh.svg" alt="Restore" class="h-5 w-5">Restore';
            }
        });

        // --- Delete Logic ---
        document.querySelectorAll('.delete-backup-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const backupId = e.currentTarget.dataset.backupId;
                if (!confirm('Are you sure you want to permanently delete this backup? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch('../../function/_backupAndRestore/_delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            backup_id: backupId
                        })
                    });
                    const result = await response.json();

                    alert(result.message);
                    if (result.success) {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Delete request failed:', error);
                    alert('A network error occurred while deleting the backup.');
                }
            });
        });
    });
</script>