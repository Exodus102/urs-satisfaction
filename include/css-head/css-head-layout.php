<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'dashboard-css-head';
$page_title = ucwords(str_replace(['-', '_'], ' ', $page));

// Handle specific cases for acronyms or special names
if (strtolower($page_title) === 'ncar') $page_title = 'NCAR';
if (strtolower($page_title) === 'qr code') $page_title = 'QR Code';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../Tailwind/src/output.css">
    <title>Customer Satisfaction</title>
</head>

<body class="bg-[#E6E7EC] font-sans leading-normal tracking-normal overflow-hidden">
    <div class="h-16 relative">
        <?php include "css-head-header.php"; ?>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden"></div>

    <div class="flex h-[calc(100vh-4rem)]">

        <?php include "css-head-navigation.php"; ?>

        <main class="flex-1 lg:p-5 w-4/5 overflow-y-auto">
            <h1 class="lg:text-3xl lg:font-bold lg:mb-6">
            </h1>
            <?php
            $filePath = "../../pages/{$page}/{$page}.php";
            if (file_exists($filePath)) {
                include $filePath;
            } else {
                echo "<div>Page not found.</div>";
            }
            ?>
        </main>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const hamburgerBtn = document.getElementById('hamburger-btn');
            const sideNav = document.getElementById('side-nav');
            const sidebarOverlay = document.getElementById('sidebar-overlay');

            const toggleSidebar = () => {
                sidebarOverlay.classList.toggle('hidden');
                sideNav.classList.toggle('-translate-x-full');
            };

            if (hamburgerBtn && sideNav && sidebarOverlay) {
                hamburgerBtn.addEventListener('click', () => {
                    toggleSidebar();
                });

                sidebarOverlay.addEventListener('click', () => {
                    toggleSidebar();
                });

                // Close sidebar when a nav link is clicked on mobile
                sideNav.addEventListener('click', (e) => {
                    if (e.target.closest('a') && window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            }
        });
    </script>
    <?php include '../../JavaScript/autoLogout/idleLogOut.php'; ?>
</body>

</html>