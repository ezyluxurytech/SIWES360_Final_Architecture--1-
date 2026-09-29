<?php
/**
 * SIWES360 - Supervisor Weekly Report Review Endpoint
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('supervisor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/supervisor/reports.php');
}

verify_csrf();

$reportId = (int)($_POST['report_id'] ?? 0);
$decision = trim($_POST['decision'] ?? 'approved'); // 'approved', 'rejected', 'revision_requested'
$comment = trim($_POST['comment'] ?? '');

if ($reportId <= 0 || empty($comment)) {
    setFlash('danger', 'Please provide a review feedback comment.');
    redirect('/siwes360/supervisor/reports.php');
}

$pdo = getDBConnection();

// Fetch supervisor_id from session user_id
$supStmt = $pdo->prepare("SELECT supervisor_id FROM supervisors WHERE user_id = ?");
$supStmt->execute([$_SESSION['user_id']]);
$supervisorId = $supStmt->fetchColumn();

// Verify that report belongs to this supervisor's placements
$repStmt = $pdo->prepare("
    SELECT r.report_id, r.week_number, p.student_id, s.user_id AS student_user_id
    FROM weekly_reports r
    JOIN placements p ON r.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    WHERE r.report_id = ? AND p.supervisor_id = ?
");
$repStmt->execute([$reportId, $supervisorId]);
$report = $repStmt->fetch();

if (!$report) {
    setFlash('danger', 'Unauthorized or report not found.');
    redirect('/siwes360/supervisor/reports.php');
}

try {
    $pdo->beginTransaction();

    // Map decision to weekly_reports status
    $newReportStatus = ($decision === 'approved') ? 'approved' : (($decision === 'rejected') ? 'rejected' : 'under_review');

    // Update weekly_reports status
    $upStmt = $pdo->prepare("UPDATE weekly_reports SET status = ? WHERE report_id = ?");
    $upStmt->execute([$newReportStatus, $reportId]);

    // Insert into report_reviews table
    $revStmt = $pdo->prepare("INSERT INTO report_reviews (report_id, supervisor_id, decision, comment) VALUES (?, ?, ?, ?)");
    $revStmt->execute([$reportId, $supervisorId, $decision, $comment]);

    // Notify student
    $notifType = ($decision === 'approved') ? 'success' : (($decision === 'rejected') ? 'error' : 'warning');
    createNotification(
        $report['student_user_id'],
        "Weekly Report {$decision}",
        "Your Week {$report['week_number']} report was evaluated: {$decision}. Supervisor Comment: \"{$comment}\"",
        $notifType
    );

    logAudit($_SESSION['user_id'], 'REPORT_REVIEWED', "Supervisor marked Report ID {$reportId} as {$decision}");

    $pdo->commit();
    setFlash('success', "Weekly Report successfully evaluated ({$decision}).");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Report review error: " . $e->getMessage());
    setFlash('danger', 'Error processing review. Please retry.');
}

redirect('/siwes360/supervisor/reports.php');
