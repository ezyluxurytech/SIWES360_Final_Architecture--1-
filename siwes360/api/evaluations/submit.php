<?php
/**
 * SIWES360 - Evaluation Submission Handler
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('supervisor', 'coordinator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/index.php');
}

verify_csrf();

$placementId = (int)($_POST['placement_id'] ?? 0);
$technical = (int)($_POST['technical_skills'] ?? 0);
$punctuality = (int)($_POST['punctuality'] ?? 0);
$communication = (int)($_POST['communication'] ?? 0);
$teamwork = (int)($_POST['teamwork'] ?? 0);
$problemSolving = (int)($_POST['problem_solving'] ?? 0);
$professionalism = (int)($_POST['professionalism'] ?? 0);
$comments = trim($_POST['comments'] ?? '');

$evaluatorId = $_SESSION['user_id'];
$evaluatorRole = $_SESSION['role']; // 'supervisor' or 'coordinator'

// Compute overall score average
$overall = round(($technical + $punctuality + $communication + $teamwork + $problemSolving + $professionalism) / 6, 2);

$pdo = getDBConnection();

// Verify placement exists and fetch student user_id for notification
$plStmt = $pdo->prepare("
    SELECT p.placement_id, s.user_id AS student_user_id
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    WHERE p.placement_id = ?
");
$plStmt->execute([$placementId]);
$placement = $plStmt->fetch();

if (!$placement) {
    setFlash('danger', 'Placement record not found.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/siwes360/index.php');
}

if ($evaluatorRole === 'supervisor') {
    $authStmt = $pdo->prepare("SELECT 1 FROM placements p JOIN supervisors sp ON p.supervisor_id = sp.supervisor_id WHERE p.placement_id = ? AND sp.user_id = ?");
    $authStmt->execute([$placementId, $evaluatorId]);
    if (!$authStmt->fetchColumn()) {
        setFlash('danger', 'You are not assigned to this student placement.');
        redirect('/siwes360/supervisor/evaluations.php');
    }
} else {
    $authStmt = $pdo->prepare("
        SELECT 1
        FROM placements p
        JOIN students s ON p.student_id = s.student_id
        JOIN coordinators c ON s.department_id = c.department_id
        WHERE p.placement_id = ? AND c.user_id = ?
    ");
    $authStmt->execute([$placementId, $evaluatorId]);
    if (!$authStmt->fetchColumn()) {
        setFlash('danger', 'You can only evaluate students within your coordinator department.');
        redirect('/siwes360/coordinator/evaluations.php');
    }
}

$ratings = [$technical, $punctuality, $communication, $teamwork, $problemSolving, $professionalism];
foreach ($ratings as $rating) {
    if ($rating < 0 || $rating > 100) {
        setFlash('danger', 'Evaluation scores must be between 0 and 100.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/siwes360/index.php');
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO evaluations (placement_id, evaluator_id, evaluator_role, technical_skills, punctuality, communication, teamwork, problem_solving, professionalism, overall_score, comments)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$placementId, $evaluatorId, $evaluatorRole, $technical, $punctuality, $communication, $teamwork, $problemSolving, $professionalism, $overall, $comments]);

    // Send notification to student
    createNotification(
        $placement['student_user_id'],
        "Performance Evaluation Submitted",
        "Your {$evaluatorRole} submitted an official performance score ({$overall}%). Check your evaluation dashboard.",
        "success"
    );

    logAudit($evaluatorId, 'EVALUATION_RECORDED', "Submitted evaluation for placement ID {$placementId} with overall score {$overall}%");
    setFlash('success', "Evaluation recorded successfully! Overall calculated score: {$overall}%.");
} catch (Exception $e) {
    error_log("Evaluation error: " . $e->getMessage());
    setFlash('danger', 'Error recording evaluation. Please retry.');
}

$redirectUrl = ($evaluatorRole === 'supervisor') ? '/siwes360/supervisor/evaluations.php' : '/siwes360/coordinator/evaluations.php';
redirect($redirectUrl);
