<div class="font-sfpro p-4">
    <script>
        // Apply saved font size on every page load
        (function() {
            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <h1 class="text-4xl font-bold">Entity Management</h1>
    <p class="mt-1">Manage entities in the survey questionnaire easily.</p><br>

    <?php
    include 'add-customer-type.php';
    ?><br><br>
    <?php
    include 'add-campus.php';
    ?><br><br>
    <?php
    include 'add-division.php';
    ?><br><br>
    <?php
    include 'add-unit.php';
    ?>

</div>