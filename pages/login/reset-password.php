<?php
session_start();

// Check if the user is authorized to be on this page
if (!isset($_SESSION['authorized_to_reset']) || !$_SESSION['authorized_to_reset']) {
  $_SESSION['reset_error'] = "You are not authorized to access this page.";
  header("Location: forgot_password.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/css_website_admin_migration/Tailwind/src/output.css">
  <script>
    // Apply saved font size on every page load
    (function() {
      const savedSize = localStorage.getItem('user_font_size');
      if (savedSize) {
        document.documentElement.style.fontSize = savedSize;
      }
    })();
  </script>
  <title>Reset Password</title>
</head>

<body class="bg-[#f2f7fa]">

  <div class="min-h-screen flex flex-col md:flex-row bg-[#f2f7fa]">

    <?php include '2fa_header.php'; ?>

    <!-- Center Section -->
    <div class="md:w-1/2 flex flex-col justify-center items-center bg-transparent p-4 md:p-6">
      <div class="w-full max-w-sm">

        <!-- Title -->
        <h3 class="text-2xl font-bold text-[#064089] text-center mb-1">Reset Password</h3>
        <p class="text-sm text-gray-600 text-center mb-4">
          Please enter your new password below.
        </p>

        <!-- Reset Password Form -->
        <form action="../../function/_auth/_updatePassword.php" method="POST" class="space-y-3">

          <!-- New Password -->
          <div class="relative">
            <input type="password" id="new_password" name="new_password" required
              class="peer w-full px-3 pt-3 pb-1 border border-[#064089] rounded-md
                         focus:outline-none focus:ring-0 focus:border-[#064089]"
              placeholder=" " />
            <label for="new_password"
              class="absolute left-3 -top-2 bg-white px-1 text-gray-600 text-sm transition-all
                         peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:bg-transparent
                         peer-focus:-top-2 peer-focus:text-sm peer-focus:text-[#064089] peer-focus:bg-[#F1F7F9]">
              New Password
            </label>
          </div>

          <!-- Confirm Password -->
          <div class="relative">
            <input type="password" id="confirm_password" name="confirm_password" required
              class="peer w-full px-3 pt-3 pb-1 border border-[#064089] rounded-md
                         focus:outline-none focus:ring-0 focus:border-[#064089]"
              placeholder=" " />
            <label for="confirm_password"
              class="absolute left-3 -top-2 bg-white px-1 text-gray-600 text-sm transition-all
                         peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:bg-transparent
                         peer-focus:-top-2 peer-focus:text-sm peer-focus:text-[#064089] peer-focus:bg-[#F1F7F9]">
              Confirm Password
            </label>
          </div>

          <!-- Error Message -->
          <?php
          if (isset($_SESSION['reset_error'])) {
            echo '<p class="text-red-500 text-sm text-center">' . $_SESSION['reset_error'] . '</p>';
            unset($_SESSION['reset_error']);
          }
          ?>

          <!-- Reset Button -->
          <div class="flex justify-end">
            <button type="submit"
              class="w-fit bg-[#064089] text-white font-semibold px-6 py-2 rounded-md shadow-md hover:bg-[#002266]">
              Reset Password
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

</body>

</html>