<!-- Left Section -->
<div class="hidden lg:flex lg:w-3/5 xl:w-[70rem] bg-cover bg-center flex-col justify-around p-10 lg:p-12"
  style="background-image:url('resources/svg/login-bg.svg'); background-size:cover; background-position:center;">
  <?php
  // This logic fetches the active logo path.
  // It's placed here to be accessible within this header component.
  $logo_path_header = 'resources/img/new-logo.png'; // Default fallback
  try {
    // We need a separate DB connection here as this file is included early.
    $pdo_header = new PDO("mysql:host=localhost;dbname=db_css", "root", "");
    $pdo_header->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt_header = $pdo_header->query("SELECT logo_path FROM tbl_logo WHERE status = 1 LIMIT 1");
    $active_logo_header = $stmt_header->fetchColumn();
    if ($active_logo_header) {
      $logo_path_header = $active_logo_header;
    }
  } catch (PDOException $e) {
    // On error, the default logo is used.
  }
  ?>
  <img src="<?php echo htmlspecialchars($logo_path_header); ?>" alt="URS Logo" class="size-32 object-contain">
  <div class="">
    <!-- <img src="resources/svg/urs-logo.svg" alt="URS Logo" class="w-10 mb-4"> -->
    <h1 class="text-xl uppercase tracking-wide text-white">University of Rizal System</h1>
    <h2 class="text-6xl md:text-7xl font-bold leading-tight mt-4 text-white">
      Customer<br>
      Satisfaction<br>
      Survey System
    </h2>
    <p class="italic text-white mt-4 text-lg md:text-xl">
      "Nurturing Tomorrow’s Noblest"
    </p>
  </div>
  <p class="text-sm text-white">
    &copy; 2024 University of Rizal System. All rights reserved.
  </p>
</div>