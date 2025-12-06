<div class="p-4">
    <script>
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <h1 class="text-4xl font-bold text-[#1E1E1E] dark:text-white">Profile</h1>
    <p class="text-[#1E1E1E] dark:text-white">
        Manage your profile information in this page.
    </p><br>

    <!-- Profile content with two boxes -->
    <div class="flex flex-col-reverse lg:flex-row gap-6 lg:items-start">

        <!-- First box (longer) -->
        <?php
        include "information.php";
        ?>

        <!-- Second box (shorter) -->
        <?php
        include "profile-picture.php";
        ?>
    </div>
</div>