<?php

require_once '../_databaseConfig/_dbConfig.php';

header('Content-Type: application/json');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

$surveyId = $_GET['id'] ?? null;

if (!$surveyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Survey ID is required.']);
    exit;
}

try {
    // Fetch survey form details
    $stmtForm = $pdo->prepare("SELECT question_survey FROM tbl_questionaireform WHERE id = ?");
    $stmtForm->execute([$surveyId]);
    $surveyForm = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$surveyForm) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Survey not found.']);
        exit;
    }

    // Fetch questions for the survey
    $stmtQuestions = $pdo->prepare("SELECT * FROM tbl_questionaire WHERE question_survey = ? ORDER BY question_id ASC");
    $stmtQuestions->execute([$surveyForm['question_survey']]);
    $questions = $stmtQuestions->fetchAll(PDO::FETCH_ASSOC);

    // Fetch choices for each question
    $stmtChoices = $pdo->prepare("SELECT choice_text FROM tbl_choices WHERE question_id = ?");

    foreach ($questions as &$question) {
        // Map DB type to JS type
        $question['type'] = strtolower(str_replace(' ', '-', $question['question_type']));

        if ($question['type'] === 'dropdown' || $question['type'] === 'multiple-choice') {
            $stmtChoices->execute([$question['question_id']]);
            $choices = $stmtChoices->fetchAll(PDO::FETCH_COLUMN);
            $question['choices'] = $choices;
        } else {
            $question['choices'] = [];
        }
    }
    unset($question); // Unset reference

    $surveyData = [
        'survey_name' => $surveyForm['question_survey'],
        'questions' => $questions
    ];

    echo json_encode(['success' => true, 'data' => $surveyData]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
