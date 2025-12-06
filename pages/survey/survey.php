<?php
require_once '../../function/_databaseConfig/_dbConfig.php';

try {
    // Find the active survey name by looking for questions with status = 1
    $stmt_active_survey = $pdo->query("SELECT DISTINCT question_survey FROM tbl_questionaire WHERE status = 1 LIMIT 1");
    $active_survey_name = $stmt_active_survey->fetchColumn();

    if ($active_survey_name) {
        // Fetch the details of the active survey form
        $stmt_form_details = $pdo->prepare("SELECT `timestamp` FROM tbl_questionaireform WHERE question_survey = ?");
        $stmt_form_details->execute([$active_survey_name]);
        $survey_form = $stmt_form_details->fetch(PDO::FETCH_ASSOC);
        $last_updated = $survey_form ? date('F j, Y', strtotime($survey_form['timestamp'])) : 'N/A';

        // Fetch all active questions for this survey
        $stmt_questions = $pdo->prepare("SELECT * FROM tbl_questionaire WHERE question_survey = ? AND status = 1 ORDER BY question_id ASC");
        $stmt_questions->execute([$active_survey_name]);
        $questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

        // Prepare statement for fetching choices
        $stmt_choices = $pdo->prepare("SELECT choice_text FROM tbl_choices WHERE question_id = ?");
?>
        <div class="p-4 dark:bg-gray-800 dark:text-white">
            <script>
                // Apply saved font size on every page load
                (function() {
                    const savedSize = localStorage.getItem('user_font_size');
                    if (savedSize) {
                        document.documentElement.style.fontSize = savedSize;
                    }
                })();
            </script>
            <h1 class="text-4xl font-bold font-sfpro"><?php echo htmlspecialchars($active_survey_name); ?></h1>
            <p class="font-sfpro">You are viewing the survey questionnaire currently in use. Last updated: <span class="text-[#064089] dark:text-[#EEE4B1]"><?php echo $last_updated; ?>.</span></p><br>

            <?php if (empty($questions)) : ?>
                <div class="bg-[#F1F7F9] p-5 rounded-md dark:bg-gray-900 dark:text-white">
                    <p class="font-sfpro">This survey has no active questions.</p>
                </div>
            <?php else : ?>
                <div class="bg-[#F1F7F9] p-5 rounded-md dark:bg-gray-900 dark:text-white">
                    <?php foreach ($questions as $index => $q) : ?>
                        <div>
                            <span class="inline-block box-border border-2 rounded-md border-[#1E1E1E] px-3 font-sfpro dark:border-white"><?php echo ($index + 1) . '. ' . htmlspecialchars(ucfirst($q['question_type'])); ?></span><br><br>
                            <p class="font-bold text-lg font-sfpro"><?php echo htmlspecialchars($q['question']); ?></p><br>

                            <?php
                            if ($q['question_type'] === 'Dropdown' || $q['question_type'] === 'Multiple Choice') {
                                $stmt_choices->execute([$q['question_id']]);
                                $choices = $stmt_choices->fetchAll(PDO::FETCH_COLUMN);
                                if (!empty($choices)) {
                                    echo '<div class="choices-container space-y-2 pl-4">';
                                    foreach ($choices as $c_index => $choice) {
                                        echo '<span class="font-sfpro">' . ($c_index + 1) . '. ' . htmlspecialchars($choice) . '</span><br>';
                                    }
                                    echo '</div><br>';
                                } else {
                                    echo '<span class="text-gray-500 pl-4 font-sfpro">No choices defined for this question.</span><br><br>';
                                }
                            } elseif ($q['question_type'] === 'Text' || $q['question_type'] === 'Description') {
                                echo '<span class="pl-4 font-sfpro">Answer: _________________________</span><br><br>';
                            }
                            ?>
                        </div>
                        <?php if ($index < count($questions) - 1) : ?>
                            <hr class="my-6 border-[#1E1E1E] dark:border-white">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
    } else {
        // No active survey found
    ?>
        <div>
            <h1 class="text-4xl font-bold font-sfpro">No Active Survey</h1>
            <p class="font-sfpro">There is currently no active survey questionnaire. An administrator can activate one from the "Edit Survey" page.</p><br>
        </div>
<?php
    }
} catch (PDOException $e) {
    // A simple error message for the user.
    // In a production environment, you would log the detailed error and show a generic message.
    echo '<div><p class="text-red-500 font-sfpro">Error: Could not connect to the database or fetch survey data. Please contact support.</p></div>';
    // For debugging: error_log($e->getMessage());
}
?>