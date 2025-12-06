<?php
session_start();

// Ensure the user is in a pending authenticated state
if (!isset($_SESSION['user_authenticated_pending']) || !$_SESSION['user_authenticated_pending']) {
  header("Location: ../../index.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../Tailwind/src/output.css">
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

<body>

  <div class="h-screen flex flex-col md:flex-row bg-[#f2f7fa]">

    <?php include '2fa_header.php'; ?>

    <!-- Reduced padding for center section -->
    <div class="w-full lg:w-2/5 h-full">
      <div class="w-full h-full flex flex-col items-center justify-around">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <?php
          include 'logo.php';
          ?>
          <div class="text-left">
            <h2 class="font-bold text-blue-800">URSatisfaction</h2>
            <p class="text-xs text-gray-500">We comply so URSatisfied</p>
          </div>
        </div>

        <!-- Title -->
        <div class="w-full">
          <h3 class="text-2xl font-bold text-[#064089] text-center mb-1">2-Step Verification</h3>
          <p class="text-sm text-gray-600 text-center mb-4">
            A verification code has been sent to your email.
          </p>

          <!-- Verification Form -->
          <form action="../../function/_auth/_authenticationCode.php" method="POST" id="tfaForm" class="space-y-3 w-full xl:px-28 px-10 lg:p-5">

            <!-- Floating Label Input -->
            <div class="relative">
              <input type="text" id="code" name="code" required maxlength="6"
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
            if (isset($_SESSION['login_error'])) {
              echo '<p class="text-red-500 text-sm text-center">' . $_SESSION['login_error'] . '</p>';
              unset($_SESSION['login_error']);
            }
            ?>

            <!-- Verify Button -->
            <div class="flex justify-end">
              <button type="submit" id="verifyBtn"
                class="w-fit bg-[#064089] text-white font-semibold px-6 py-2 rounded-md shadow-md hover:bg-[#002266] flex items-center justify-center min-w-[90px]">
                Verify Code
              </button>
            </div>
          </form>
        </div>

        <footer class="mt-6 text-center text-xs text-gray-600 max-w-xs mx-auto">
          <p>
            You are agreeing to the
            <a href="#" class="text-blue-700 font-semibold hover:text-blue-900">Terms of Services</a>
            and
            <a href="#" class="text-blue-700 font-semibold hover:text-blue-900">Privacy Policy</a>.
          </p>
        </footer>

      </div>
    </div>
  </div>

  <!-- Full-screen Loading Overlay -->
  <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="flex flex-col items-center">
      <svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <p class="mt-4 text-white text-lg">Verifying...</p>
    </div>
  </div>

  <script>
    const tfaForm = document.getElementById("tfaForm");
    const verifyBtn = document.getElementById("verifyBtn");
    const loadingOverlay = document.getElementById("loadingOverlay");

    if (tfaForm && verifyBtn && loadingOverlay) {
      tfaForm.addEventListener("submit", (e) => {
        const codeInput = document.getElementById('code');
        // Prevent loader from showing on an empty form submission
        if (!codeInput.value.trim()) {
          return;
        }

        // Disable the button and show the full-screen overlay
        verifyBtn.disabled = true;
        loadingOverlay.classList.remove("hidden");
      });

      // Handle back-navigation
      window.addEventListener('pageshow', function(event) {
        // The event.persisted property is true if the page is from the bfcache
        if (event.persisted) {
          // Hide the overlay and re-enable the button
          loadingOverlay.classList.add('hidden');
          verifyBtn.disabled = false;
        }
      });
    }
  </script>
</body>

</html>