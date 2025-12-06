<?php

require_once '../_databaseConfig/_dbConfig.php';

header('Content-Type: application/json');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
$surveyId = $data['survey_id'] ?? null;

if (!$surveyId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Survey ID is required.']);
    exit;
}

$pdo->beginTransaction();
try {
    // 1. Get the survey name for the given ID
    $stmtForm = $pdo->prepare("SELECT question_survey FROM tbl_questionaireform WHERE id = ?");
    $stmtForm->execute([$surveyId]);
    $surveyName = $stmtForm->fetchColumn();

    if (!$surveyName) {
        throw new Exception('Survey not found.');
    }

    // 2. Deactivate all questions in all surveys first to ensure only one is active
    $stmtDeactivateAll = $pdo->prepare("UPDATE tbl_questionaire SET status = 0");
    $stmtDeactivateAll->execute();

    // 3. Activate all questions for the selected survey
    $stmtActivate = $pdo->prepare("UPDATE tbl_questionaire SET status = 1 WHERE question_survey = ?");
    $stmtActivate->execute([$surveyName]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Survey '{$surveyName}' has been activated."]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
