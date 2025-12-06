<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// --- Pagination Logic ---
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$limit = 15; // Number of records per page
$offset = ($page - 1) * $limit;

$audit_logs = [];
$total_records = 0;

try {
  // Get total number of records for pagination
  $total_stmt = $pdo->query("SELECT COUNT(*) FROM tbl_audit_trail");
  $total_records = $total_stmt->fetchColumn();

  // Fetch records for the current page
  $stmt = $pdo->prepare("SELECT * FROM tbl_audit_trail ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
  $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Audit trail fetch failed: " . $e->getMessage());
  // Display a user-friendly error message
  echo "<tr><td colspan='4' class='text-center p-4'>Could not retrieve audit logs. Please try again later.</td></tr>";
}

$total_pages = ceil($total_records / $limit);
?>

<div class="p-4">
  <script>
    // Apply saved font size on every page load
    (function() {
      const savedSize = localStorage.getItem('user_font_size');
      if (savedSize) {
        document.documentElement.style.fontSize = savedSize;
      }
    })();
  </script>
  <h1 class="text-4xl font-bold">Audit Trail</h1>
  <P class="mb-5">Monitor updates, edits, and user activities.</P>
  <div class="overflow-x-auto">
    <table class="min-w-full border-collapse border border-[#1E1E1E] shadow-lg">
      <thead class="bg-[#064089] text-white font-normal">
        <tr>
          <th class="border px-4 py-3 border-[#1E1E1E] w-1/4">Timestamp</th>
          <th class="border px-4 py-3 border-[#1E1E1E] w-1/4">Unit</th>
          <th class="border px-4 py-3 border-[#1E1E1E] w-1/4">User</th>
          <th class="border px-4 py-3 border-[#1E1E1E] w-1/4">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 text-sm text-[#1E1E1E]">
        <?php if (empty($audit_logs)) : ?>
          <tr>
            <td colspan="4" class="text-center p-4 border border-[#1E1E1E]">No audit trail records found.</td>
          </tr>
        <?php else : ?>
          <?php foreach ($audit_logs as $log) : ?>
            <tr class="bg-white hover:bg-gray-50">
              <td class="border border-[#1E1E1E] p-3"><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($log['timestamp']))); ?></td>
              <td class="border border-[#1E1E1E] p-3"><?php echo htmlspecialchars($log['unit_name']); ?></td>
              <td class="border border-[#1E1E1E] p-3"><?php echo htmlspecialchars($log['user_name']); ?></td>
              <td class="border border-[#1E1E1E] p-3"><?php echo htmlspecialchars($log['action']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <div class="flex justify-between items-center mt-4 text-sm">
    <div>
      Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> results
    </div>
    <div class="flex items-center gap-2">
      <?php if ($page > 1) : ?>
        <a href="?page=audit-trail&p=<?php echo $page - 1; ?>" class="px-3 py-1 border rounded-md hover:bg-gray-100">&laquo; Previous</a>
      <?php endif; ?>

      <?php
      // Pagination links logic
      $start_page = max(1, $page - 2);
      $end_page = min($total_pages, $page + 2);

      if ($start_page > 1) {
        echo '<a href="?page=audit-trail&p=1" class="px-3 py-1 border rounded-md hover:bg-gray-100">1</a>';
        if ($start_page > 2) echo '<span class="px-3 py-1">...</span>';
      }

      for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i == $page) ? 'bg-[#064089] text-white' : 'hover:bg-gray-100';
        echo '<a href="?page=audit-trail&p=' . $i . '" class="px-3 py-1 border rounded-md ' . $active_class . '">' . $i . '</a>';
      }

      if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) echo '<span class="px-3 py-1">...</span>';
        echo '<a href="?page=audit-trail&p=' . $total_pages . '" class="px-3 py-1 border rounded-md hover:bg-gray-100">' . $total_pages . '</a>';
      }
      ?>

      <?php if ($page < $total_pages) : ?>
        <a href="?page=audit-trail&p=<?php echo $page + 1; ?>" class="px-3 py-1 border rounded-md hover:bg-gray-100">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
</div>