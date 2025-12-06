<?php
require_once __DIR__ . '/../../function/_databaseConfig/_dbConfig.php';

$campuses = [];
$divisions = [];
$units_by_campus_division = [];

try {
  // Fetch campuses
  $stmtCampuses = $pdo->query("SELECT campus_name FROM tbl_campus ORDER BY campus_name ASC");
  $campuses = $stmtCampuses->fetchAll(PDO::FETCH_COLUMN);

  // Fetch divisions
  $stmtDivisions = $pdo->query("SELECT division_name FROM tbl_division ORDER BY division_name ASC");
  $divisions = $stmtDivisions->fetchAll(PDO::FETCH_COLUMN);

  // Fetch all units and group them by campus and division
  $stmtUnits = $pdo->query("SELECT campus_name, division_name, unit_name FROM tbl_unit ORDER BY campus_name, division_name, unit_name ASC");
  $all_units = $stmtUnits->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("QR Code Page Error: " . $e->getMessage());
}
?>
<div class="flex-1 p-4">
  <script>
    // Apply saved font size on every page load
    (function() {
      const savedSize = localStorage.getItem('user_font_size');
      if (savedSize) {
        document.documentElement.style.fontSize = savedSize;
      }
    })();
  </script>
  <h2 class="text-4xl font-bold text-[#1e1e1e]">QR Code</h2>
  <p class="text-[#1e1e1e]">Access survey page easily by generating QR code.</p><br>

  <div class="flex flex-col lg:flex-row gap-6 lg:items-start">
    <!-- First box: QR Code Display -->
    <div class="w-full lg:w-1/3 bg-[#F1F7F9] p-6 rounded-lg shadow-md flex flex-col items-center">
      <h3 class="text-xl font-bold text-[#1E1E1E] mb-4">QR Code Generator</h3>
      <div id="qrcode" class="p-4 bg-white border rounded-lg mb-4 min-h-[288px] min-w-[288px] flex items-center justify-center text-gray-400 text-sm">
        QR Code will appear here
      </div>
      <label for="qr-text-input" class="sr-only">URL or Text to encode</label>
      <input type="text" id="qr-text-input" placeholder="Enter URL or text here" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
      <div class="flex gap-4 mt-4">
        <button id="generate-qr-btn" class="bg-[#0D2442] text-white px-6 py-2 rounded-md font-semibold hover:bg-[#064089] transition-colors">Generate</button>
        <button id="download-qr-btn" class="bg-gray-600 text-white px-6 py-2 rounded-md font-semibold hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>Download</button>
      </div>
    </div>

    <!-- Second box: Instructions -->
    <div class="w-full">
      <div class="w-full bg-[#F1F7F9] p-6 rounded-lg shadow-md">
        <p class="text-[#48494A] text-sm">SELECT THEME</p>
        <div id="theme-container" class="flex justify-center gap-5 items-center">
          <div class="theme-selector flex flex-col items-center shadow-md p-4 rounded-lg bg-white cursor-pointer border-2 border-blue-500" data-color="#064089">
            <img src="../../resources/svg/scan-qr-code-blue.svg" alt=""><br><br>
            <div class="rounded-full w-4 h-4 bg-[#064089]">
            </div>
          </div>
          <div class="theme-selector flex flex-col items-center shadow-md p-4 rounded-lg bg-white cursor-pointer border-2 border-transparent" data-color="#FF9D5C">
            <img src="../../resources/svg/scan-qr-code-orange.svg" alt=""><br><br>
            <div class="rounded-full w-4 h-4 bg-[#FF9D5C]">
            </div>
          </div>
          <div class="theme-selector flex flex-col items-center shadow-md p-4 rounded-lg bg-white cursor-pointer border-2 border-transparent" data-color="#DC8E8E">
            <img src="../../resources/svg/scan-qr-code-pink.svg" alt=""><br><br>
            <div class="rounded-full w-4 h-4 bg-[#DC8E8E]">
            </div>
          </div>
          <div class="theme-selector flex flex-col items-center shadow-md p-4 rounded-lg bg-white cursor-pointer border-2 border-transparent" data-color="#88A44F">
            <img src="../../resources/svg/scan-qr-code-green.svg" alt=""><br><br>
            <div class="rounded-full w-4 h-4 bg-[#88A44F]">
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 space-y-4">
        <div>
          <label for="qr-campus-select" class="block text-sm font-medium text-gray-700">Campus</label>
          <select id="qr-campus-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md font-bold">
            <option value="" hidden>Select Campus</option>
            <?php foreach ($campuses as $campus) : ?>
              <option value="<?php echo htmlspecialchars($campus); ?>"><?php echo htmlspecialchars($campus); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="qr-division-select" class="block text-sm font-medium text-gray-700">Division</label>
          <select id="qr-division-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md font-bold">
            <option value="" hidden>Select Division</option>
            <?php foreach ($divisions as $division) : ?>
              <option value="<?php echo htmlspecialchars($division); ?>"><?php echo htmlspecialchars($division); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="qr-unit-select" class="block text-sm font-medium text-gray-700">Unit/Office</label>
          <select id="qr-unit-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md font-bold">
            <option value="" hidden>Select Unit</option>
            <!-- Options will be populated by JavaScript -->
          </select>
        </div>
      </div>
    </div>
  </div>

</div>
<!-- Advanced QR Code generation library that supports logos -->
<script src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const allUnits = <?php echo json_encode($all_units); ?>;
    const defaultSurveyUrl = 'https://urscustomer.online/';
    const qrCodeContainer = document.getElementById('qrcode');
    const textInput = document.getElementById('qr-text-input');
    const generateBtn = document.getElementById('generate-qr-btn');
    const downloadBtn = document.getElementById('download-qr-btn');
    const themeSelectors = document.querySelectorAll('.theme-selector');
    let qrCodeStylingInstance = null;
    let selectedColor = "#064089"; // Default color
    const campusSelect = document.getElementById('qr-campus-select');
    const divisionSelect = document.getElementById('qr-division-select');
    const unitSelect = document.getElementById('qr-unit-select');

    // Pre-fill the input with the default survey URL
    textInput.value = defaultSurveyUrl;

    const generateQRCode = (color = selectedColor) => {
      const textToEncode = textInput.value.trim();

      if (!textToEncode) {
        alert('Please enter a URL or text to generate a QR code.');
        return;
      }

      // Clear the container before generating a new QR code
      qrCodeContainer.innerHTML = '';

      // Initialize the QR code with styling options
      qrCodeStylingInstance = new QRCodeStyling({
        width: 256,
        height: 256,
        data: textToEncode,
        image: "../../resources/img/urs-logo.png", // Path to your logo
        dotsOptions: {
          color: color,
          type: "rounded"
        },
        backgroundOptions: {
          color: "#ffffff",
        },
        imageOptions: {
          crossOrigin: "anonymous",
          margin: 5, // Space between the logo and the QR code dots
          imageSize: 0.3 // Logo size relative to QR code size (30%)
        },
        qrOptions: {
          errorCorrectionLevel: 'H' // High correction level is good for QR codes with logos
        }
      });

      // Append the generated QR code to the container
      qrCodeStylingInstance.append(qrCodeContainer);

      // Enable the download button
      downloadBtn.disabled = false;
    };

    // Generate QR code when the "Generate" button is clicked
    generateBtn.addEventListener('click', () => {
      updateUrlFromDropdowns();
      generateQRCode();
    });

    // Handle theme selection
    themeSelectors.forEach(selector => {
      selector.addEventListener('click', () => {
        // Remove border from all selectors
        themeSelectors.forEach(s => {
          s.classList.remove('border-blue-500');
          s.classList.add('border-transparent');
        });
        // Add border to the clicked one
        selector.classList.add('border-blue-500');
        selectedColor = selector.dataset.color;
        generateQRCode(selectedColor); // Regenerate QR with new color
      });
    });

    // --- Dropdown Logic ---
    const populateUnitDropdown = () => {
      const selectedCampus = campusSelect.value;
      const selectedDivision = divisionSelect.value;
      const currentUnit = unitSelect.value;

      unitSelect.innerHTML = '<option value="" hidden>Select Unit</option>'; // Reset

      if (selectedCampus && selectedDivision) {
        const filteredUnits = allUnits.filter(unit => unit.campus_name === selectedCampus && unit.division_name === selectedDivision);
        filteredUnits.forEach(unit => {
          const option = document.createElement('option');
          option.value = unit.unit_name;
          option.textContent = unit.unit_name;
          unitSelect.appendChild(option);
        });
      }

      unitSelect.value = currentUnit; // Restore selection if possible
    };

    const updateUrlFromDropdowns = () => {
      const campus = campusSelect.value;
      const division = divisionSelect.value;
      const unit = unitSelect.value;

      let url = new URL(defaultSurveyUrl);
      if (campus) url.searchParams.set('campus', campus);
      if (division) url.searchParams.set('division', division);
      if (unit) url.searchParams.set('unit', unit);

      textInput.value = url.toString();
    };

    campusSelect.addEventListener('change', () => {
      populateUnitDropdown();
      updateUrlFromDropdowns();
    });
    divisionSelect.addEventListener('change', () => {
      populateUnitDropdown();
      updateUrlFromDropdowns();
    });
    unitSelect.addEventListener('change', updateUrlFromDropdowns);

    // Automatically generate the default QR code on page load
    generateQRCode();

    // Handle the download button click
    downloadBtn.addEventListener('click', function() {
      if (qrCodeStylingInstance) {
        const campus = campusSelect.value;
        const division = divisionSelect.value;
        const unit = unitSelect.value;

        let filenameParts = [];
        if (campus) filenameParts.push(campus);
        if (division) filenameParts.push(division);
        if (unit) filenameParts.push(unit);

        // Sanitize and join parts to create a filename, or use a default.
        const filename = filenameParts.length > 0 ?
          filenameParts.join('-').replace(/[\s\/\\?%*:|"<>]+/g, '-') :
          "urs-survey-qrcode";

        qrCodeStylingInstance.download({
          name: filename,
          extension: "png"
        });
      } else {
        alert('Please generate a QR code first before downloading.');
      }
    });
  });
</script>