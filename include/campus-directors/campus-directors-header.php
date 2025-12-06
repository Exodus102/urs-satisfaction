<header class="absolute top-0 right-0 bg-[#F1F7F9] shadow-md p-4 flex items-center w-full lg:z-50 z-0 dark:bg-gray-900 dark:text-white">
    <div class="flex items-center space-x-4 w-5/6">
        <div class="flex items-center gap-9 text-gray-500">
            <button id="hamburger-btn" class="focus:outline-none flex lg:hidden">
                <img src="../../resources/svg/hamburger.svg" alt="" class="w-7 h-7">
            </button>
            <span class="lg:flex gap-2 hidden">
                <?php
                // This logic fetches the active logo path.
                // It's placed here to be self-contained within the header.
                $logo_path = '../../resources/img/new-logo.png'; // Default fallback logo
                try {
                    // We need to include the DB config here as the header is loaded before the page content.
                    require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

                    $stmt = $pdo->query("SELECT logo_path FROM tbl_logo WHERE status = 1 LIMIT 1");
                    $active_logo_path = $stmt->fetchColumn();

                    if ($active_logo_path) {
                        $logo_path = '../../' . $active_logo_path;
                    }
                } catch (PDOException $e) {
                    // On error, the default logo is used. You could log the error here.
                }
                ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="URSatisfaction Logo" class="size-14 object-contain">
                <p class="flex flex-col">
                    <span class="font-bold text-[#064089] dark:text-white">URSatisfaction</span>
                    <span class="flex flex-col text-xs text-[#064089] leading-none dark:text-white">
                        <span>Customer Satisfaction</span>
                        <span>Survey System</span>
                    </span>
                </p>
            </span>
            <img src="../../resources/svg/nav-arrow-right.svg" alt="" srcset="" class="hidden lg:flex">
            <span class="text-2xl font-bold text-[#064089] dark:text-white"><?php echo htmlspecialchars($page_title); ?></span>
        </div>
    </div>

    <a href="campus-directors-layout.php?page=profile" class="hidden group lg:flex place-content-end items-center space-x-2 w-1/6 rounded-lg p-1 transition-colors duration-200">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold">
            <?php
            $dp_path = $_SESSION['user_dp'] ?? '';
            $full_dp_path = '../../' . $dp_path;

            if (!empty($dp_path) && file_exists($full_dp_path)) {
                // Display the image
                echo '<img src="' . htmlspecialchars($full_dp_path) . '" alt="User" class="w-full h-full rounded-full object-cover">';
            } else {
                // Display the first letter of the first name
                $firstName = $_SESSION['user_first_name'] ?? 'U';
                $initial = strtoupper(substr($firstName, 0, 1));
                echo '<span class="bg-[#064089] text-white w-full h-full rounded-full flex items-center justify-center dark:text-white">' . htmlspecialchars($initial) . '</span>';
            }
            ?>
        </div>
        <div class="flex flex-col text-sm">
            <span class="font-semibold text-gray-800 group-hover:underline dark:text-white">
                <?php
                $fullName = trim(($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''));
                echo htmlspecialchars($fullName ?: 'User');
                ?>
            </span>
            <span class="text-gray-500 dark:text-white"><?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Guest'); ?></span>
        </div>
    </a>
</header>