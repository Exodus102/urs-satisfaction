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
  <title>Verification</title>
</head>

<body class="bg-[#f2f7fa]">

  <div class="min-h-screen flex flex-col md:flex-row bg-[#f2f7fa]">

    <?php include '2fa_header.php'; ?>

    <!-- Reduced padding for center section -->
    <div class="md:w-1/2 flex flex-col justify-center items-center bg-transparent p-4 md:p-6">
      <div class="w-full max-w-sm">


        <!-- Title -->
        <h3 class="text-2xl font-bold text-[#064089] text-center mb-1">Forgot Password</h3>
        <p class="text-sm text-gray-600 text-center mb-4">
          A verification code has been sent to your email.
        </p>

        <!-- Verification Form -->
        <form action="../../function/_auth/_authenticationCodeForgotPassword.php" method="POST" class="space-y-3">

          <!-- Floating Label Input -->
          <div class="relative">
            <input type="text" name="code" id="code" required maxlength="6"
              class="peer w-full px-3 pt-3 pb-1 border border-[#064089] rounded-md 
                         focus:outline-none focus:ring-0 focus:border-[#064089]"
              placeholder=" " />
            <label for="code"
              class="absolute left-3 -top-2 bg-white px-1 text-gray-600 text-sm transition-all
                         peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:bg-transparent
                         peer-focus:-top-2 peer-focus:text-sm peer-focus:text-[#064089] peer-focus:bg-[#F1F7F9]">
              Enter 6-digit Code
            </label>
          </div>

          <!-- Error Message -->
          <?php
          session_start();
          if (isset($_SESSION['reset_error'])) {
            echo '<p class="text-red-500 text-sm text-center">' . $_SESSION['reset_error'] . '</p>';
            unset($_SESSION['reset_error']);
          }
          ?>

          <!-- Verify Button -->
          <div class="flex justify-end">
            <button type="submit"
              class="w-fit bg-[#064089] text-white font-semibold px-6 py-2 rounded-md shadow-md hover:bg-[#002266]">
              Verify Code
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

</body>

</html>