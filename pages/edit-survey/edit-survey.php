<?php
require_once '../../function/_databaseConfig/_dbConfig.php';

// Fetch the current analysis status
$analysis_is_active = 0; // Default to disabled
try {
  // Assumes a single row with id=1 in tbl_active controls this setting
  $stmt = $pdo->query("SELECT is_active FROM tbl_active WHERE id = 1 LIMIT 1");
  $result = $stmt->fetchColumn();
  if ($result !== false) {
    $analysis_is_active = (int)$result;
  }
} catch (PDOException $e) {
  error_log("Could not fetch analysis status: " . $e->getMessage());
}
?>
<div class="p-4 overflow-hidden" id="survey-list-container">
  <script>
    // Apply saved font size on every page load
    (function() {
      const savedSize = localStorage.getItem('user_font_size');
      if (savedSize) {
        document.documentElement.style.fontSize = savedSize;
      }
    })();
  </script>
  <div class="flex lg:flex-row lg:justify-between lg:items-center flex-col mb-2">
    <div>
      <h1 class="text-3xl font-bold mb-2 font-sfpro leading-5">Edit Survey</h1>
      <p class="font-sfpro">Customize your survey details and questions.</p><br>
    </div>
    <div>
      <button id="toggle-analysis-btn" data-active="<?php echo $analysis_is_active; ?>" class="bg-[#D9E2EC] text-[#064089] px-4 py-2 rounded-md font-semibold transition hover:bg-blue-200 w-full lg:w-40">
        <?php echo $analysis_is_active ? 'Disable Analysis' : 'Enable Analysis'; ?>
      </button>
    </div>
  </div>
  <?php
  // The DB connection is already included at the top.
  // We can now proceed with the rest of the page logic.
  ?>
  <?php
  try {
    // First, find the name of the currently active survey.
    // This is efficient as it's only one query before the loop.
    $activeSurveyStmt = $pdo->query("SELECT DISTINCT question_survey FROM tbl_questionaire WHERE status = 1 LIMIT 1");
    $activeSurveyName = $activeSurveyStmt->fetchColumn();

    // Then, get all questionnaire forms.
    $stmt = $pdo->query("SELECT id, question_survey, `timestamp`, date_approved, change_log FROM tbl_questionaireform ORDER BY question_survey ASC");
    $questionnaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    // You can log the error here if needed
    // error_log($e->getMessage());
    $questionnaires = [];
  }
  ?>
  <div class="w-full overflow-x-auto">
    <table class="w-full table-auto border-collapse border border-[#1E1E1ECC] shadow-lg rounded-md overflow-hidden">
      <thead class="bg-[#064089] text-white font-normal text-left w-full">
        <tr>
          <th class="border px-4 py-3 border-[#1E1E1ECC] w-12">#</th>
          <th class="border px-4 py-3 border-[#1E1E1ECC] w-1/4">Questionnaire</th>
          <th class="border px-4 py-3 border-[#1E1E1ECC] w-1/5">Date Created</th>
          <th class="border px-4 py-3 border-[#1E1E1ECC] w-1/5">Date Approved</th>
          <th class="border px-4 py-3 border-[#1E1E1ECC] w-1/4">Change Log</th>
          <th class="border px-4 py-3 border-[#1E1E1ECC] w-1/5 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($questionnaires)) : ?>
          <tr>
            <td colspan="6" class="text-center border px-4 py-3 border-[#1E1E1ECC]">No questionnaires found.</td>
          </tr>
        <?php else : ?>
          <?php $row_number = 1; ?>
          <?php foreach ($questionnaires as $q) : ?>
            <?php
            // Check if the current questionnaire in the loop is the active one.
            $isActive = ($activeSurveyName === $q['question_survey']);
            ?>
            <tr class="bg-white h-16">
              <td class="border px-4 py-2 border-[#1E1E1ECC] text-center align-middle"><?php echo $row_number++; ?></td>
              <td class="border px-4 py-2 border-[#1E1E1ECC] align-middle"><?php echo htmlspecialchars($q['question_survey']); ?></td>
              <td class="border px-4 py-2 border-[#1E1E1ECC] align-middle"><?php echo date('F j, Y, g:i a', strtotime($q['timestamp'])); ?></td>
              <td class="border px-4 py-2 border-[#1E1E1ECC] date-approved-cell align-middle">
                <div class="date-approved-display flex justify-between items-center gap-2">
                  <span class="date-text flex-grow"><?php echo $q['date_approved'] ? date('F j, Y, g:i a', strtotime($q['date_approved'])) : 'N/A'; ?></span>
                  <button class="edit-date-btn p-1 rounded hover:bg-gray-200 transition-colors flex-shrink-0"><img src="../../resources/svg/pencil.svg" alt="Edit Date" class="h-4 w-4"></button>
                </div>
                <div class="date-approved-edit hidden">
                  <input type="datetime-local" value="<?php echo $q['date_approved'] ? date('Y-m-d\TH:i:s', strtotime($q['date_approved'])) : ''; ?>" class="date-input border border-gray-300 rounded px-2 py-1 w-full text-sm">
                  <div class="flex justify-end gap-2 mt-2">
                    <button data-survey-id="<?php echo $q['id']; ?>" class="save-date-btn bg-green-100 text-green-800 px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-green-200">Save</button>
                    <button type="button" class="cancel-date-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-gray-300">Cancel</button>
                  </div>
                </div>
              </td>
              <td class="border px-4 py-2 border-[#1E1E1ECC] change-log-cell align-middle">
                <div class="change-log-display flex justify-between items-center gap-2">
                  <span class="log-text flex-grow"><?php echo htmlspecialchars($q['change_log'] ?: 'N/A'); ?></span>
                  <button class="edit-log-btn p-1 rounded hover:bg-gray-200 transition-colors flex-shrink-0"><img src="../../resources/svg/pencil.svg" alt="Edit Log" class="h-4 w-4"></button>
                </div>
                <div class="change-log-edit hidden">
                  <input type="text" value="<?php echo htmlspecialchars($q['change_log']); ?>" class="log-input border border-gray-300 rounded px-2 py-1 w-full text-sm">
                  <div class="flex justify-end gap-2 mt-2">
                    <button data-survey-id="<?php echo $q['id']; ?>" class="save-log-btn bg-green-100 text-green-800 px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-green-200">Save</button>
                    <button type="button" class="cancel-log-btn bg-gray-200 text-gray-700 px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-gray-300">Cancel</button>
                  </div>
                </div>
              </td>
              <td class="border px-4 py-2 border-[#1E1E1ECC] align-middle">
                <div class="flex justify-center items-center gap-2">
                  <?php if ($isActive) : ?>
                    <!-- If this survey is active, show a disabled "Activated" button -->
                    <button disabled class="bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-md text-xs font-semibold cursor-not-allowed">
                      Activated
                    </button>
                  <?php else : ?>
                    <!-- Otherwise, show the clickable "Activate" button -->
                    <button data-survey-id="<?php echo $q['id']; ?>" class="activate-survey-btn bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-md text-xs font-semibold transition hover:bg-blue-100">Activate</button>
                  <?php endif; ?>
                  <button data-survey-id="<?php echo $q['id']; ?>" class="view-survey-btn bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-md text-xs font-semibold transition">View</button>
                  <button data-survey-id="<?php echo $q['id']; ?>" class="edit-survey-btn bg-[#D9E2EC] text-[#064089] px-3 py-1 rounded-md text-xs font-semibold transition">Edit</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="mt-4 lg:w-auto w-full" id="button-container">
    <button id="add-new-questionnaire-btn" class="border px-4 py-1 border-[#000000cc] shadow-lg font-bold rounded-md w-full lg:w-auto"> + Add New Questionnaire</button>
  </div>
</div>

<div id="questionnaire-creator-container" class="hidden p-4">
  <div class="mb-4">
    <button id="back-to-list-btn" class="border px-4 py-1 border-[#000000cc] shadow-lg font-bold rounded-md">&larr; Back to List</button>
  </div>

  <!-- <button id="addQuestionBtn">Add Question</button> -->

  <dialog id="questionTypeDialog" class="w-full lg:w-auto lg:max-w-lg rounded-md">
    <div class="p-4">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Select Question Type</h2>
        <button id="closeDialogBtn" aria-label="close" class="hidden lg:block text-2xl font-bold leading-none">&times;</button>
      </div>
      <div class="dialog-form lg:grid lg:grid-cols-2 lg:gap-4 flex flex-col gap-4">
        <button type="button" class="question-type-btn p-4 border rounded-md hover:bg-gray-100" data-type="dropdown">Dropdown</button>
        <button type="button" class="question-type-btn p-4 border rounded-md hover:bg-gray-100" data-type="text">Text</button>
        <button type="button" class="question-type-btn p-4 border rounded-md hover:bg-gray-100" data-type="description">Description</button>
        <button type="button" class="question-type-btn p-4 border rounded-md hover:bg-gray-100" data-type="multiple-choice">Multiple Choices</button>
      </div>
      <div class="mt-6 text-center lg:hidden">
        <button type="button" id="cancelDialogBtn" class="px-6 py-2 bg-[#D6D7DC] border border-[#1E1E1E] text-black font-semibold rounded-md hover:bg-gray-300 transition-colors w-full">Cancel</button>
      </div>
    </div>
  </dialog>

  <form id="surveyForm" class="mt-8">
    <div>
      <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
        <input type="hidden" id="surveyId" name="survey_id" />
        <label for="surveyName" class="block text-lg font-semibold text-[#064089] mb-2">Survey Name</label>
        <input type="text" id="surveyName" name="survey_name" placeholder="e.g., 2025 Questionnaire v1.2" class="mt-1 block w-full rounded-md border border-[#1E1E1E] bg-[#E6E7EC] py-2 px-3 h-10 focus:border-blue-500 focus:ring-blue-500 sm:text-sm placeholder:text-gray-500" required>
      </div>
    </div>

    <div id="questions-container" class="mt-8 space-y-6">
      <!-- New questions will be added here -->
    </div>

    <div class="mt-8 flex items-center gap-4 flex-col-reverse lg:flex-row">
      <button type="submit" id="saveSurveyBtn" class="px-6 py-2 bg-[#064089] text-white font-semibold rounded-md hover:bg-blue-800 transition-colors w-full lg:w-auto">Save Survey</button>
      <button type="button" id="addQuestionBtn" class="px-6 py-2 bg-[#D6D7DC] border border-[#1E1E1E] text-black font-semibold rounded-md hover:bg-gray-300 transition-colors w-full lg:w-auto">Add Question</button>
    </div>
  </form>

</div>

<script src="../../JavaScript/pages/edit-survey/new-questionaire-page.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toggleAnalysisBtn = document.getElementById('toggle-analysis-btn');

    if (toggleAnalysisBtn) {
      toggleAnalysisBtn.addEventListener('click', async () => {
        const currentlyActive = toggleAnalysisBtn.dataset.active === '1';
        const newStatus = currentlyActive ? 0 : 1;

        const originalText = toggleAnalysisBtn.textContent;
        toggleAnalysisBtn.disabled = true;
        toggleAnalysisBtn.textContent = 'Updating...';

        try {
          const response = await fetch('../../function/_analysis/_toggleAnalysis.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              is_active: newStatus
            }),
          });

          const result = await response.json();
          alert(result.message);

          if (result.success) {
            // Update button state and text
            toggleAnalysisBtn.dataset.active = newStatus;
            toggleAnalysisBtn.textContent = newStatus === 1 ? 'Disable Analysis' : 'Enable Analysis';
          } else {
            toggleAnalysisBtn.textContent = originalText; // Revert on failure
          }
        } catch (error) {
          console.error('Error toggling analysis status:', error);
          alert('An error occurred. Please check the console.');
          toggleAnalysisBtn.textContent = originalText; // Revert on error
        } finally {
          toggleAnalysisBtn.disabled = false;
        }
      });
    }

    const listView = document.getElementById('survey-list-container');
    const creatorView = document.getElementById('questionnaire-creator-container');
    const addNewBtn = document.getElementById('add-new-questionnaire-btn');
    const backBtn = document.getElementById('back-to-list-btn');
    const viewBtns = document.querySelectorAll('.view-survey-btn');
    const surveyIdInput = document.getElementById('surveyId');
    const activateBtns = document.querySelectorAll('.activate-survey-btn');

    if (addNewBtn) {
      addNewBtn.addEventListener('click', () => {
        listView.classList.add('hidden');
        creatorView.classList.remove('hidden');
        // Ensure we're in "create" mode by clearing the ID
        if (surveyIdInput) surveyIdInput.value = '';
      });
    }

    if (backBtn) {
      backBtn.addEventListener('click', () => {
        creatorView.classList.add('hidden');
        listView.classList.remove('hidden');
        // Reset the form for the next use
        document.getElementById('surveyForm').reset();
        if (surveyIdInput) surveyIdInput.value = '';
        document.getElementById('questions-container').innerHTML = '';
      });
    }

    activateBtns.forEach(button => {
      button.addEventListener('click', async (event) => {
        const surveyId = event.currentTarget.dataset.surveyId;

        if (!confirm('Are you sure you want to activate this survey? This will deactivate any other active survey.')) {
          return;
        }

        try {
          const response = await fetch('../../function/_questionaire/_activateSurvey.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              survey_id: surveyId
            }),
          });

          const result = await response.json();
          alert(result.message);

          if (result.success) {
            window.location.reload(); // Reload to reflect the change in button state
          }

        } catch (error) {
          console.error('Error activating survey:', error);
          alert('An error occurred while activating the survey. Please check the console.');
        }
      });
    });

    document.querySelectorAll('.change-log-cell').forEach(cell => {
      const displayView = cell.querySelector('.change-log-display');
      const editView = cell.querySelector('.change-log-edit');
      const editBtn = cell.querySelector('.edit-log-btn');
      const saveBtn = cell.querySelector('.save-log-btn');
      const cancelBtn = cell.querySelector('.cancel-log-btn');
      const inputField = cell.querySelector('.log-input');

      editBtn.addEventListener('click', () => {
        displayView.classList.add('hidden');
        editView.classList.remove('hidden');
        inputField.focus();
      });

      cancelBtn.addEventListener('click', () => {
        editView.classList.add('hidden');
        displayView.classList.remove('hidden');
      });

      saveBtn.addEventListener('click', async () => {
        const surveyId = saveBtn.dataset.surveyId;
        const newLogMessage = inputField.value;

        // Disable button to prevent multiple clicks
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
          const response = await fetch('../../function/_questionaire/_updateChangeLog.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              survey_id: surveyId,
              change_log: newLogMessage.trim()
            }),
          });

          const result = await response.json();
          alert(result.message);

          if (result.success) {
            window.location.reload(); // Reload to show the updated log
          }
        } catch (error) {
          console.error('Error updating change log:', error);
          alert('An error occurred. Please check the console for details.');
        } finally {
          // Re-enable button
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save';
        }
      });
    });

    document.querySelectorAll('.date-approved-cell').forEach(cell => {
      const displayView = cell.querySelector('.date-approved-display');
      const editView = cell.querySelector('.date-approved-edit');
      const editBtn = cell.querySelector('.edit-date-btn');
      const saveBtn = cell.querySelector('.save-date-btn');
      const cancelBtn = cell.querySelector('.cancel-date-btn');
      const inputField = cell.querySelector('.date-input');

      editBtn.addEventListener('click', () => {
        displayView.classList.add('hidden');
        editView.classList.remove('hidden');
        inputField.focus();
      });

      cancelBtn.addEventListener('click', () => {
        editView.classList.add('hidden');
        displayView.classList.remove('hidden');
      });

      saveBtn.addEventListener('click', async () => {
        const surveyId = saveBtn.dataset.surveyId;
        const newDateValue = inputField.value;

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
          const response = await fetch('../../function/_questionaire/_updateDateApproved.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              survey_id: surveyId,
              date_approved: newDateValue // Can be empty to set to NULL
            }),
          });

          const result = await response.json();
          alert(result.message);

          if (result.success) {
            window.location.reload();
          }
        } catch (error) {
          console.error('Error updating approval date:', error);
          alert('An error occurred. Please check the console for details.');
        } finally {
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save';
        }
      });
    });
  });
</script>