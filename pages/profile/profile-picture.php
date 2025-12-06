<div class="w-full lg:w-1/4 bg-[#F1F7F9] p-6 rounded-lg shadow-md dark:bg-gray-700 dark:text-white">
    <form id="dp-upload-form" action="../../function/_profile/_updateProfilePicture.php" method="post" enctype="multipart/form-data">
        <div class="flex flex-col items-center space-y-4">
            <div class="w-28 h-28 rounded-full flex items-center justify-center text-4xl font-bold border-2 border-[#1E1E1E] overflow-hidden">
                <?php
                $dp_path = $_SESSION['user_dp'] ?? '';
                $full_dp_path = '../../' . $dp_path;

                if (!empty($dp_path) && file_exists($full_dp_path)) {
                    // Display the image
                    echo '<img src="' . htmlspecialchars($full_dp_path) . '?v=' . time() . '" alt="User" class="w-full h-full object-cover">';
                } else {
                    // Display the first letter of the first name
                    $firstName = $_SESSION['user_first_name'] ?? 'U';
                    $initial = strtoupper(substr($firstName, 0, 1));
                    echo '<span class="bg-[#064089] text-white w-full h-full flex items-center justify-center">' . htmlspecialchars($initial) . '</span>';
                }
                ?>
            </div>
            <div class="text-center">
                <h2 class="text-xl font-bold text-[#1E1E1E] dark:text-white">
                    <?php
                    $fullName = trim(($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''));
                    echo htmlspecialchars($fullName ?: 'User');
                    ?></h2>
                <p class="text-gray-500 dark:text-white"><?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Guest'); ?></p><br>
                <label for="dp-file-input" class="dark:bg-gray-900 dark:text-white cursor-pointer gap-1 flex justify-center items-center bg-[#D6D7DC] border border-[#1E1E1E] px-4 py-1 rounded shadow-sm text-sm font-bold">
                    <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.4535 0.75C11.1656 0.75 10.8777 0.859934 10.658 1.07959L9.30012 2.43751L12.1126 5.25003L13.4706 3.89211C13.9099 3.4528 13.9099 2.7406 13.4706 2.30128L12.2489 1.07959C12.0292 0.859934 11.7413 0.75 11.4535 0.75ZM8.17511 3.56252L1.57118 10.1665C1.57118 10.1665 2.08742 10.1202 2.2798 10.3126C2.47218 10.505 2.31381 11.7638 2.55007 12.0001C2.78632 12.2363 4.03727 12.07 4.21671 12.2495C4.39614 12.4289 4.3837 12.979 4.3837 12.979L10.9876 6.37504L8.17511 3.56252ZM0.862553 12.0001L0.331909 13.503C0.311049 13.5623 0.300279 13.6247 0.300049 13.6876C0.300049 13.8368 0.359312 13.9799 0.464803 14.0854C0.570293 14.1908 0.713368 14.2501 0.862553 14.2501C0.925425 14.2499 0.987814 14.2391 1.04712 14.2182C1.04896 14.2175 1.05079 14.2168 1.05262 14.216L1.0669 14.2117C1.068 14.2109 1.0691 14.2102 1.0702 14.2095L2.55007 13.6876L1.70631 12.8438L0.862553 12.0001Z" fill="#1E1E1E" />
                    </svg>
                    Change Picture
                </label>
                <input type="file" name="profile_picture" id="dp-file-input" class="hidden" accept="image/png, image/jpeg, image/gif">
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.getElementById('dp-file-input');
        const form = document.getElementById('dp-upload-form');

        if (fileInput && form) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    form.submit();
                }
            });
        }
    });
</script>