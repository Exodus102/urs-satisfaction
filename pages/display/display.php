<div class="p-4 h-full lg:h-auto dark:text-white">
    <script>
        // Apply saved theme and font size on every page load
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('dark');
            }

            const savedSize = localStorage.getItem('user_font_size');
            if (savedSize) {
                document.documentElement.style.fontSize = savedSize;
            }
        })();
    </script>
    <h1 class="text-3xl font-bold mb-2 font-sfpro leading-5">Display</h1>
    <p class="font-sfpro">Customize the appearance and layout of your system display.</p><br>

    <div class="flex flex-col">
        <div class="flex flex-col lg:flex-row gap-4 mb-4">
            <div class="p-4 bg-[#F1F7F9] dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-md lg:w-1/3 w-full">
                <div class="w-3/4 mx-auto">
                    <h3 class="text-lg font-bold">Logo</h3>
                    <button id="change-logo-btn" class="bg-[#D6D7DC] dark:bg-gray-500 dark:text-white border border-[#1E1E1E] dark:border-gray-400 px-2 py-1 rounded shadow-sm text-sm flex items-center h-7 gap-2 mt-2 w-full lg:w-auto">
                        <img src="../../resources/svg/change-logo.svg" alt="" srcset="">
                        <p class="font-bold">Change Logo</p>
                    </button>
                </div>
            </div>
            <div class="w-full lg:w-1/3 p-4 bg-[#F1F7F9] dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-md flex flex-col">
                <div class="flex-grow flex flex-col justify-center">
                    <div class="w-3/4 mx-auto">
                        <h3 class="text-lg font-bold mb-2">Font Size</h3>
                        <div id="font-size-slider" class="relative flex items-center justify-between">
                            <div class="absolute left-0 top-1/2 w-full h-0.5 bg-gray-300 transform -translate-y-1/2"></div>
                            <!-- Slider Points -->
                            <div class="font-size-point relative w-2 h-2 bg-gray-500 rounded-full z-10 cursor-pointer" data-size="14px"></div>
                            <div class="font-size-point relative w-3 h-3 bg-gray-500 rounded-full z-10 cursor-pointer" data-size="15px"></div>
                            <div class="font-size-point relative w-4 h-4 bg-[#064089] rounded-full z-10 cursor-pointer shadow-md" data-size="16px"></div>
                            <div class="font-size-point relative w-5 h-5 bg-gray-500 rounded-full z-10 cursor-pointer" data-size="17px"></div>
                            <div class="font-size-point relative w-6 h-6 bg-gray-500 rounded-full z-10 cursor-pointer" data-size="18px"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-full lg:w-1/3 p-4 bg-[#F1F7F9] dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-md flex flex-col">
                <div class="flex-grow flex flex-col justify-center">
                    <div class="w-3/4 mx-auto">
                        <h3 class="text-lg font-bold mb-2">Theme</h3>
                        <div class="flex lg:flex-row flex-col gap-4 justify-center">
                            <button id="theme-light-btn" class="flex-1 justify-center bg-[#D6D7DC] dark:bg-gray-500 dark:text-white border border-[#1E1E1E] dark:border-gray-400 py-1 rounded shadow-sm text-sm flex items-center h-7 gap-2">
                                <p class="font-bold">Light</p>
                            </button>
                            <button id="theme-dark-btn" class="flex-1 justify-center bg-[#0D2442] py-1 rounded shadow-sm text-sm flex items-center h-7 gap-2">
                                <p class="font-bold text-white">Lights Out</p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Logo Dialog -->
<dialog id="upload-logo-dialog" class="p-6 rounded-md shadow-lg backdrop:bg-black backdrop:bg-opacity-50 w-full max-w-md bg-[#F1F7F9] dark:bg-gray-800">
    <form id="upload-logo-form" method="POST" class="space-y-4">
        <h3 class="font-bold text-lg mb-4 text-center dark:text-white">Change System Logo</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 text-center">Select an image file (PNG, JPG, GIF). The recommended size is square (e.g., 200x200 pixels). Max file size: 2MB.</p>
        <div id="logo-drop-zone" class="flex flex-col items-center justify-center w-full">
            <label for="logo-file-input" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors bg-[#749DC8]/20">
                <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4">
                    <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                    </svg>
                    <p id="logo-drop-zone-text" class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF (MAX. 2MB)</p>
                </div>
                <input type="file" id="logo-file-input" name="logo_file" class="hidden" accept="image/png, image/jpeg, image/gif" required>
            </label>
        </div>
        <button type="button" id="cancel-upload-logo" class="hidden">Cancel</button>
    </form>
</dialog>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sliderPoints = document.querySelectorAll('.font-size-point');
        const lightThemeBtn = document.getElementById('theme-light-btn');
        const darkThemeBtn = document.getElementById('theme-dark-btn');
        const rootElement = document.documentElement;

        // Function to update the visual state of the slider
        const updateSliderVisuals = (activeSize) => {
            sliderPoints.forEach(p => {
                p.classList.remove('bg-[#064089]', 'shadow-md');
                p.classList.add('bg-gray-500');
                if (p.dataset.size === activeSize) {
                    p.classList.add('bg-[#064089]', 'shadow-md');
                    p.classList.remove('bg-gray-500');
                }
            });
        };

        sliderPoints.forEach(point => {
            point.addEventListener('click', function() {
                const newSize = this.dataset.size;
                // Apply the font size to the page
                rootElement.style.fontSize = newSize;
                // Save the setting to localStorage
                localStorage.setItem('user_font_size', newSize);
                // Update the slider to show the new active circle
                updateSliderVisuals(newSize);
            });
        });

        // On page load, check if a font size is saved and update the slider's active state
        const savedSize = localStorage.getItem('user_font_size');
        if (savedSize) {
            sliderPoints.forEach(p => {
                updateSliderVisuals(savedSize);
            });
        }

        // --- Theme Switching Logic ---
        if (lightThemeBtn) {
            lightThemeBtn.addEventListener('click', () => {
                document.documentElement.classList.remove('dark');
                document.body.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                console.log('Light theme applied');
            });
        }

        if (darkThemeBtn) {
            darkThemeBtn.addEventListener('click', () => {
                document.documentElement.classList.add('dark');
                document.body.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                console.log('Dark theme applied');
            });
        }

        // --- Logo Upload Logic ---
        const changeLogoBtn = document.getElementById('change-logo-btn');
        const uploadLogoDialog = document.getElementById('upload-logo-dialog');
        const uploadLogoForm = document.getElementById('upload-logo-form');
        const cancelUploadLogoBtn = document.getElementById('cancel-upload-logo');
        const logoFileInput = document.getElementById('logo-file-input');
        const logoDropZone = document.getElementById('logo-drop-zone');
        const logoDropZoneText = document.getElementById('logo-drop-zone-text');

        if (changeLogoBtn) {
            changeLogoBtn.addEventListener('click', () => uploadLogoDialog.showModal());
        }

        if (cancelUploadLogoBtn) {
            cancelUploadLogoBtn.addEventListener('click', () => uploadLogoDialog.close());
        }

        if (uploadLogoDialog) {
            uploadLogoDialog.addEventListener('click', (e) => {
                if (e.target === uploadLogoDialog) {
                    uploadLogoDialog.close();
                }
            });
        }

        const handleLogoUpload = async () => {
            if (!logoFileInput.files || logoFileInput.files.length === 0) {
                alert('Please select an image file to upload.');
                return;
            }

            logoDropZoneText.innerHTML = `<span class="font-semibold text-blue-600">Uploading ${logoFileInput.files[0].name}...</span>`;

            const formData = new FormData(uploadLogoForm);

            try {
                const response = await fetch('../../function/_display/_uploadLogo.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    window.location.reload(); // Reload to reflect changes
                } else {
                    logoDropZoneText.innerHTML = `<span class="font-semibold">Click to upload</span> or drag and drop`;
                }
            } catch (error) {
                alert('An error occurred during the upload. Please check the console.');
                console.error('Upload Error:', error);
                logoDropZoneText.innerHTML = `<span class="font-semibold">Click to upload</span> or drag and drop`;
            } finally {
                uploadLogoDialog.close();
            }
        };

        // --- Drag and Drop Logic for Logo ---
        if (logoDropZone && logoFileInput && logoDropZoneText) {
            const preventDefaults = (e) => {
                e.preventDefault();
                e.stopPropagation();
            };

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                logoDropZone.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                logoDropZone.addEventListener(eventName, () => {
                    logoDropZone.querySelector('label').classList.add('border-blue-500', 'bg-blue-50');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                logoDropZone.addEventListener(eventName, () => {
                    logoDropZone.querySelector('label').classList.remove('border-blue-500', 'bg-blue-50');
                }, false);
            });

            logoDropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0) {
                    logoFileInput.files = files;
                    logoFileInput.dispatchEvent(new Event('change'));
                }
            }, false);

            logoFileInput.addEventListener('change', () => {
                if (logoFileInput.files.length > 0) {
                    logoDropZoneText.innerHTML = `<span class="font-semibold text-green-600">${logoFileInput.files[0].name}</span> selected`;
                    handleLogoUpload();
                } else {
                    logoDropZoneText.innerHTML = `<span class="font-semibold">Click to upload</span> or drag and drop`;
                }
            });
        }
    });
</script>