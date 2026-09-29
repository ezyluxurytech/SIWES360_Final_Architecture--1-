<?php
/**
 * SIWES360 - Clock Out Endpoint
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/student/attendance.php');
}

verify_csrf();

$attendanceId = (int)($_POST['attendance_id'] ?? 0);
$nowTime = date('Y-m-d H:i:s');

if ($attendanceId <= 0) {
    setFlash('danger', 'Invalid attendance record.');
    redirect('/siwes360/student/attendance.php');
}

$pdo = getDBConnection();

// Verify attendance belongs to student's placement
$stmt = $pdo->prepare("
    SELECT a.attendance_id, a.clock_out 
    FROM attendance a 
    JOIN placements p ON a.placement_id = p.placement_id 
    JOIN students s ON p.student_id = s.student_id 
    WHERE a.attendance_id = ? AND s.user_id = ?
");
$stmt->execute([$attendanceId, $_SESSION['user_id']]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('danger', 'Attendance record not found.');
    redirect('/siwes360/student/attendance.php');
}

if (!empty($record['clock_out'])) {
    setFlash('warning', 'You have already clocked out for this session.');
    redirect('/siwes360/student/attendance.php');
}

try {
    $upStmt = $pdo->prepare("UPDATE attendance SET clock_out = ? WHERE attendance_id = ?");
    $upStmt->execute([$nowTime, $attendanceId]);

    logAudit($_SESSION['user_id'], 'STUDENT_CLOCK_OUT', "Student clocked out at {$nowTime} for attendance ID {$attendanceId}");
    setFlash('success', "Clocked out successfully at {$nowTime}. Have a restful evening!");
} catch (Exception $e) {
    error_log("Clock out error: " . $e->getMessage());
    setFlash('danger', 'Unable to clock out. Please try again.');
}

redirect('/siwes360/student/attendance.php');
