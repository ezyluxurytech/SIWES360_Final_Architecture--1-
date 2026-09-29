<?php
/**
 * SIWES360 - Clock In Endpoint
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/student/attendance.php');
}

verify_csrf();

$placementId = (int)($_POST['placement_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$today = date('Y-m-d');
$nowTime = date('Y-m-d H:i:s');
$clockTime = date('H:i:s');

if ($placementId <= 0) {
    setFlash('danger', 'Invalid placement specified.');
    redirect('/siwes360/student/attendance.php');
}

$pdo = getDBConnection();

// Verify placement belongs to authenticated student
$stmt = $pdo->prepare("SELECT p.placement_id
FROM placements p
JOIN students s ON p.student_id = s.student_id
WHERE p.placement_id = ?
  AND s.user_id = ?
  AND p.status = 'active'
  AND ? BETWEEN p.start_date AND p.end_date");
$stmt->execute([$placementId, $_SESSION['user_id'], $today]);
if (!$stmt->fetch()) {
    setFlash('danger', 'Unauthorized placement record.');
    redirect('/siwes360/student/attendance.php');
}

// Check for duplicate attendance today
$chkStmt = $pdo->prepare("SELECT attendance_id FROM attendance WHERE placement_id = ? AND attendance_date = ?");
$chkStmt->execute([$placementId, $today]);
if ($chkStmt->fetch()) {
    setFlash('warning', 'You have already clocked in today.');
    redirect('/siwes360/student/attendance.php');
}

// Status: if clocked in after 08:00:00 -> 'late', otherwise 'present'
$status = ($clockTime > '08:00:00') ? 'late' : 'present';

try {
    $insStmt = $pdo->prepare("INSERT INTO attendance (placement_id, attendance_date, clock_in, status, notes) VALUES (?, ?, ?, ?, ?)");
    $insStmt->execute([$placementId, $today, $nowTime, $status, $notes ?: null]);

    logAudit($_SESSION['user_id'], 'STUDENT_CLOCK_IN', "Student clocked in for {$today} at {$nowTime} (Status: {$status})");
    setFlash('success', "Clocked in successfully at {$nowTime} with status: {$status}.");
} catch (Exception $e) {
    error_log("Clock in error: " . $e->getMessage());
    setFlash('danger', 'Unable to clock in. Please try again.');
}

redirect('/siwes360/student/attendance.php');
