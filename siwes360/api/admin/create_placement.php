<?php
/**
 * SIWES360 - Admin Create / Assign Student Placement
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/admin/placements.php');
}

verify_csrf();

$studentId = (int)($_POST['student_id'] ?? 0);
$organizationId = (int)($_POST['organization_id'] ?? 0);
$supervisorId = (int)($_POST['supervisor_id'] ?? 0);
$sessionId = (int)($_POST['session_id'] ?? 0);
$position = trim($_POST['position'] ?? 'Industrial Trainee');
$startDate = trim($_POST['start_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');
$status = trim($_POST['status'] ?? 'pending');

$allowedStatuses = ['pending','approved','active','completed','rejected'];
if ($studentId <= 0 || $organizationId <= 0 || $supervisorId <= 0 || $sessionId <= 0 || empty($startDate) || empty($endDate) || !in_array($status, $allowedStatuses, true) || $endDate < $startDate) {
    setFlash('danger', 'Please choose student, company, supervisor, session, and start/end dates.');
    redirect('/siwes360/admin/placements.php');
}

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("
        INSERT INTO placements (student_id, organization_id, supervisor_id, session_id, position, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$studentId, $organizationId, $supervisorId, $sessionId, $position, $startDate, $endDate, $status]);
    $newPlacementId = (int)$pdo->lastInsertId();

    // Fetch student user_id to notify
    $stStmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
    $stStmt->execute([$studentId]);
    $studentUserId = $stStmt->fetchColumn();
    if ($studentUserId) {
        createNotification($studentUserId, "Placement Verified", "You have been assigned to an official SIWES placement. Please review your placement page.", "success");
    }

    logAudit($_SESSION['user_id'], 'PLACEMENT_CREATED', "Assigned student ID {$studentId} to org ID {$organizationId}");
    setFlash('success', 'Student placement assigned successfully.');
} catch (Exception $e) {
    error_log("Placement creation error: " . $e->getMessage());
    setFlash('danger', 'Unable to assign placement: ' . $e->getMessage());
}

redirect('/siwes360/admin/placements.php');
