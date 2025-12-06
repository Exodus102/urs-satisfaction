<?php
session_start();

// Check if the username is available in the session
if (!isset($_SESSION['login_username'])) {
  header("Location: ../../index.php");
  exit();
}

$username_for_password = $_SESSION['login_username'];
$first_name_for_display = $_SESSION['login_first_name'] ?? 'User';

// 🔹 Error, attempts & lockout values
$error = $_SESSION['login_error'] ?? '';
$attempts = $_SESSION['attempts'] ?? 0;
$max_attempts = 3;
$lockout_time = $_SESSION['lockout_time'] ?? 0;
$remaining = max(0, $lockout_time - time());

// ✅ Reset everything after lockout time expires
if ($remaining <= 0 && $lockout_time > 0) {
  unset($_SESSION['login_error']);
  unset($_SESSION['lockout_time']);
  $_SESSION['attempts'] = 0;   // reset attempts
  $error = '';
  $attempts = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../Tailwind/src/output.css">
  <title>Customer Satisfaction</title>
</head>

<body>
  <div class="h-screen flex flex-col md:flex-row bg-[#f2f7fa]">

    <?php include 'password_header.php'; ?>

    <!-- reduced padding -->
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

        <div class="flex flex-col items-center w-full">
          <!-- Avatar -->
          <div class="flex justify-center mb-3">
            <div class="w-16 h-16 rounded-full bg-[#064089] flex items-center justify-center text-2xl font-bold text-white overflow-hidden">
              <?php
              $dp_path = $_SESSION['login_user_dp'] ?? '';
              $full_dp_path = '../../' . $dp_path;

              if (!empty($dp_path) && file_exists($full_dp_path)) {
                // Display the image if it exists
                echo '<img src="' . htmlspecialchars($full_dp_path) . '" alt="User Avatar" class="w-full h-full object-cover border-2 border-[#1E1E1E]">';
              } else {
                // Display the first letter of the first name as an initial
                $firstName = $_SESSION['login_first_name'] ?? 'U';
                $initial = strtoupper(substr($firstName, 0, 1));
                echo '<span>' . htmlspecialchars($initial) . '</span>';
              }
              ?>
            </div>
          </div>

          <!-- Title -->
          <h3 class="text-2xl font-bold text-[#064089] text-center mb-1">
            Welcome, <?php echo htmlspecialchars($first_name_for_display); ?>
          </h3>
          <p class="text-sm text-center mb-4">
            <a href="../../index.php" class="text-gray-600 underline hover:text-[#064089]">not you?</a>
          </p>

          <!-- 🔹 Error message -->
          <?php if ($error): ?>
            <div class="text-center mb-3">
              <p class="text-sm text-[#8B0000]" id="error-msg">
                <?php echo htmlspecialchars($error); ?>
              </p>
            </div>
          <?php endif; ?>

          <!-- 🔹 Attempts indicator -->
          <?php if ($attempts > 0 && $remaining <= 0): ?>
            <div class="text-center mb-3">
              <p class="text-sm font-semibold text-red-600">
                Attempts: <?php echo min($attempts, $max_attempts) . '/' . $max_attempts; ?>
              </p>
            </div>
          <?php endif; ?>

          <!-- 🔹 Lockout timer -->
          <?php if ($remaining > 0): ?>
            <div class="text-center mb-3">
              <p class="text-sm text-[#8B0000]">
                Please wait <span id="timer"><?php echo $remaining; ?></span> seconds before retrying.
              </p>
            </div>
          <?php endif; ?>

          <!-- Password form -->
          <form action="../../function/_auth/_getPassword.php" method="post" id="passwordForm" class="space-y-3 w-full xl:px-28 px-10 lg:p-5">
            <div class="relative">
              <input type="password" name="pass" id="pass" required
                class="peer w-full px-3 pt-3 pb-1 border rounded-md 
                        focus:outline-none focus:ring-0 
                        <?php echo $error ? 'border-red-600 focus:border-red-600' : 'border-[#064089] focus:border-[#064089]'; ?>"
                placeholder=" " <?php echo $remaining > 0 ? 'disabled' : ''; ?> />

              <label for="pass"
                class="absolute left-3 -top-2 bg-white px-1 text-gray-600 text-sm transition-all
                         peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:text-base peer-placeholder-shown:bg-transparent
                         peer-focus:-top-2 peer-focus:text-sm peer-focus:text-[#064089] peer-focus:bg-[#F1F7F9]">
                Password
              </label>
            </div>

            <!-- Show password + Forgot password -->
            <div class="flex flex-col xl:flex-row items-start justify-between text-sm gap-3 xl:gap-0">
              <div class="flex items-center gap-2 text-gray-700">
                <input type="checkbox" id="showPass" onclick="togglePassword()" class="cursor-pointer" <?php echo $remaining > 0 ? 'disabled' : ''; ?>>
                <label for="showPass" class="cursor-pointer">Show password</label>
              </div>
              <a href="../../function/_auth/_sendPasswordResetCode.php?email=<?php echo urlencode($username_for_password); ?>" class="text-[#064089] hover:underline">Forgot password?</a>
            </div>

            <!-- Next Button -->
            <div class="flex justify-end">
              <button type="submit" id="nextBtn"
                class="w-fit bg-[#064089] text-white font-semibold px-6 py-2 rounded-md shadow-md hover:bg-[#002266] flex items-center justify-center min-w-[90px]"
                <?php echo $remaining > 0 ? 'disabled' : ''; ?>>
                Next
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
      <p class="mt-4 text-white text-lg">Loading...</p>
    </div>
  </div>

  <script>
    function togglePassword() {
      const passInput = document.getElementById("pass");
      passInput.type = passInput.type === "password" ? "text" : "password";
    }

    // 🔹 Countdown timer
    let timeLeft = <?php echo $remaining; ?>;
    if (timeLeft > 0) {
      const timerEl = document.getElementById("timer");
      const btn = document.querySelector("button[type='submit']");
      const passInput = document.getElementById("pass");
      const errorEl = document.getElementById("error-msg");
      const attemptsEl = document.querySelector("p.font-semibold.text-red-600"); // Attempts indicator

      const interval = setInterval(() => {
        timeLeft--;
        if (timerEl) timerEl.textContent = timeLeft;

        if (timeLeft <= 0) {
          clearInterval(interval);

          // ✅ Enable inputs again
          if (btn) btn.disabled = false;
          if (passInput) passInput.disabled = false;

          // ✅ Clean up messages
          if (errorEl) errorEl.textContent = "";
          if (attemptsEl) attemptsEl.remove(); // remove attempts 3/3
          if (timerEl) timerEl.parentElement.innerHTML = "You may now try again.";
        }
      }, 1000);
    }

    const passwordForm = document.getElementById("passwordForm");
    const nextBtn = document.getElementById("nextBtn");
    const loadingOverlay = document.getElementById("loadingOverlay");

    if (passwordForm && nextBtn && loadingOverlay) {
      passwordForm.addEventListener("submit", (e) => {
        const passwordInput = document.getElementById('pass');
        // Prevent loader from showing on an empty form submission
        if (!passwordInput.value.trim()) {
          return;
        }

        // Disable the button and show the full-screen overlay
        nextBtn.disabled = true;
        loadingOverlay.classList.remove("hidden");
      });

      // Handle back-navigation (e.g., from 2FA page)
      window.addEventListener('pageshow', function(event) {
        // The event.persisted property is true if the page is from the bfcache
        if (event.persisted) {
          // Hide the overlay and re-enable the button
          loadingOverlay.classList.add('hidden');
          nextBtn.disabled = false;
        }
      });
    }
  </script>

</body>

</html>