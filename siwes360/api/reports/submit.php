<?php
/**
 * SIWES360 - Submit Weekly Report Endpoint
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/student/reports.php');
}

verify_csrf();

$placementId = (int)($_POST['placement_id'] ?? 0);
$weekNumber = (int)($_POST['week_number'] ?? 1);
$weekStart = trim($_POST['week_start'] ?? '');
$weekEnd = trim($_POST['week_end'] ?? '');
$activities = trim($_POST['activities'] ?? '');
$skillsAcquired = trim($_POST['skills_acquired'] ?? '');
$challenges = trim($_POST['challenges'] ?? '');
$solutions = trim($_POST['solutions'] ?? '');
$observations = trim($_POST['observations'] ?? '');
$action = $_POST['submit_action'] ?? 'Submit'; // 'draft' or 'Submit'
$status = ($action === 'draft') ? 'draft' : 'submitted';
$submittedAt = ($status === 'submitted') ? date('Y-m-d H:i:s') : null;

if ($placementId <= 0 || empty($weekStart) || empty($weekEnd) || empty($activities)) {
    setFlash('danger', 'Please complete all required report sections.');
    redirect('/siwes360/student/reports.php');
}

$pdo = getDBConnection();

// Verify placement
$stmt = $pdo->prepare("SELECT p.placement_id, p.supervisor_id FROM placements p JOIN students s ON p.student_id = s.student_id WHERE p.placement_id = ? AND s.user_id = ?");
$stmt->execute([$placementId, $_SESSION['user_id']]);
$placement = $stmt->fetch();
if (!$placement) {
    setFlash('danger', 'Unauthorized placement record.');
    redirect('/siwes360/student/reports.php');
}

try {
    $insStmt = $pdo->prepare("
        INSERT INTO weekly_reports (placement_id, week_number, week_start, week_end, activities, skills_acquired, challenges, solutions, observations, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insStmt->execute([$placementId, $weekNumber, $weekStart, $weekEnd, $activities, $skillsAcquired, $challenges, $solutions, $observations, $status, $submittedAt]);

    if ($status === 'submitted') {
        // Dispatch notification to supervisor
        $supStmt = $pdo->prepare("SELECT user_id FROM supervisors WHERE supervisor_id = ?");
        $supStmt->execute([$placement['supervisor_id']]);
        $supUserId = $supStmt->fetchColumn();
        if ($supUserId) {
            createNotification($supUserId, "Weekly Report Submitted", "Student submitted Week {$weekNumber} report for your official review and sign-off.", "info");
        }
    }

    logAudit($_SESSION['user_id'], 'REPORT_SUBMITTED', "Created weekly report for Week {$weekNumber} with status: {$status}");
    setFlash('success', "Week {$weekNumber} report saved successfully as {$status}.");
} catch (Exception $e) {
    error_log("Report submission error: " . $e->getMessage());
    setFlash('danger', 'Unable to submit report. Please try again.');
}

redirect('/siwes360/student/reports.php');
