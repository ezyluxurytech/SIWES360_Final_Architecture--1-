<?php
/**
 * SIWES360 - Student Dashboard
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch Student Profile & Placement Details
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email, d.name AS department_name, d.faculty,
           p.placement_id, p.position, p.start_date, p.end_date, p.status AS placement_status,
           o.name AS organization_name, o.address AS organization_address,
           sup_u.full_name AS supervisor_name, sup.position AS supervisor_position,
           ses.name AS session_name
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN placements p ON s.student_id = p.student_id AND p.status IN ('active', 'approved')
    LEFT JOIN organizations o ON p.organization_id = o.organization_id
    LEFT JOIN supervisors sup ON p.supervisor_id = sup.supervisor_id
    LEFT JOIN users sup_u ON sup.user_id = sup_u.user_id
    LEFT JOIN it_sessions ses ON p.session_id = ses.session_id
    WHERE s.user_id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$student = $stmt->fetch();

$placementId = $student['placement_id'] ?? null;
$todayDate = date('Y-m-d');

// Today's attendance status
$todayAttendance = null;
if ($placementId) {
    $attStmt = $pdo->prepare("SELECT * FROM attendance WHERE placement_id = ? AND attendance_date = ? LIMIT 1");
    $attStmt->execute([$placementId, $todayDate]);
    $todayAttendance = $attStmt->fetch();
}

// Attendance stats
$totalDaysPresent = 0;
$totalDaysLate = 0;
$attendancePercentage = 0;
if ($placementId) {
    $statsStmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS presents, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS lates FROM attendance WHERE placement_id = ?");
    $statsStmt->execute([$placementId]);
    $stats = $statsStmt->fetch();
    $totalRecords = (int)$stats['total'];
    $totalDaysPresent = (int)$stats['presents'];
    $totalDaysLate = (int)$stats['lates'];
    $attendancePercentage = $totalRecords > 0 ? round((($totalDaysPresent + $totalDaysLate) / max(1, $totalRecords)) * 100, 1) : 0;
}

// Activity count & weekly report counts
$totalActivities = 0;
$totalReports = 0;
$pendingReviews = 0;
if ($placementId) {
    $actCountStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_activities WHERE placement_id = ?");
    $actCountStmt->execute([$placementId]);
    $totalActivities = (int)$actCountStmt->fetchColumn();

    $repCountStmt = $pdo->prepare("SELECT COUNT(*) FROM weekly_reports WHERE placement_id = ?");
    $repCountStmt->execute([$placementId]);
    $totalReports = (int)$repCountStmt->fetchColumn();

    $pendCountStmt = $pdo->prepare("SELECT COUNT(*) FROM weekly_reports WHERE placement_id = ? AND status = 'submitted'");
    $pendCountStmt->execute([$placementId]);
    $pendingReviews = (int)$pendCountStmt->fetchColumn();
}

// Recent Notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifStmt->execute([$userId]);
$recentNotifications = $notifStmt->fetchAll();

$pageTitle = "Student Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Welcome back, <?= e($student['full_name']) ?>!</h1>
        <p>Matric No: <strong><?= e($student['matric_no']) ?></strong> | <?= e($student['department_name']) ?> (Level <?= e($student['level']) ?>)</p>
    </div>
    <div class="page-actions" style="display: flex; gap: 8px;">
        <a href="/siwes360/student/attendance.php" class="btn btn-primary">⏱️ Attendance Portal</a>
        <a href="/siwes360/student/activities.php" class="btn btn-secondary">📖 Daily Logbook</a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">⏱️</div>
        <div class="stat-details">
            <h3><?= $attendancePercentage ?>%</h3>
            <p>Attendance Rate</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📝</div>
        <div class="stat-details">
            <h3><?= $totalActivities ?></h3>
            <p>Submitted Activities</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📑</div>
        <div class="stat-details">
            <h3><?= $totalReports ?></h3>
            <p>Weekly Reports</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">⏳</div>
        <div class="stat-details">
            <h3><?= $pendingReviews ?></h3>
            <p>Pending Reviews</p>
        </div>
    </div>
</div>

<!-- Quick Attendance Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Today's Attendance Status (<?= date('l, d F Y') ?>)</div>
        <span class="badge badge-<?= $todayAttendance ? strtolower($todayAttendance['status']) : 'incomplete' ?>">
            <?= $todayAttendance ? e($todayAttendance['status']) : 'Not Clocked In' ?>
        </span>
    </div>
    <div class="card-body">
        <div class="attendance-hero">
            <div class="clock-panel">
                <div>
                    <span class="clock-date">Current System Time</span>
                    <div class="clock-live" id="liveClock">--:--:--</div>
                </div>
                <div style="margin-top: 20px;">
                    <p style="font-size: 13px; opacity: 0.85;">
                        Clock-in logs an immutable record with your IP address into the attendance database.
                    </p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; justify-content: center; gap: 16px;">
                <?php if (!$placementId): ?>
                    <div class="alert alert-warning">
                        You do not currently have an active placement assigned. Please check with your coordinator.
                    </div>
                <?php elseif (!$todayAttendance): ?>
                    <p style="font-size: 14px; color: var(--text-muted);">
                        You have not recorded your attendance for today yet.
                    </p>
                    <form action="/siwes360/api/attendance/clock_in.php" method="POST">
                        <input type="hidden" name="placement_id" value="<?= e($placementId) ?>">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 15px;">
                            👉 Clock In Now
                        </button>
                    </form>
                <?php elseif ($todayAttendance && empty($todayAttendance['clock_out'])): ?>
                    <div>
                        <p style="font-size: 14px; margin-bottom: 4px;"><strong>Clocked In At:</strong> <?= e($todayAttendance['clock_in']) ?></p>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">Don't forget to clock out at the conclusion of your training day.</p>
                    </div>
                    <form action="/siwes360/api/attendance/clock_out.php" method="POST">
                        <input type="hidden" name="attendance_id" value="<?= e($todayAttendance['attendance_id']) ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 12px 24px; font-size: 15px;">
                            ⏹️ Clock Out for Today
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-bottom: 0;">
                        ✅ Full attendance completed for today! <br>
                        Clock-In: <strong><?= e($todayAttendance['clock_in']) ?></strong> | Clock-Out: <strong><?= e($todayAttendance['clock_out']) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Placement Overview & Notifications Grid -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Placement Information -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Placement & Organization</div>
            <a href="/siwes360/student/placement.php" class="btn btn-secondary btn-sm">Full Details</a>
        </div>
        <div class="card-body">
            <?php if ($placementId): ?>
                <table class="data-table" style="font-size: 13px;">
                    <tr>
                        <td style="font-weight: 600; width: 140px;">Organization</td>
                        <td><?= e($student['organization_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Position</td>
                        <td><?= e($student['position']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Supervisor</td>
                        <td><?= e($student['supervisor_name']) ?> (<?= e($student['supervisor_position']) ?>)</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Training Session</td>
                        <td><?= e($student['session_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Placement Status</td>
                        <td><span class="badge badge-success"><?= e($student['placement_status']) ?></span></td>
                    </tr>
                </table>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 13.5px;">No active placement record found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Notifications -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Notifications</div>
            <a href="/siwes360/student/notifications.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentNotifications)): ?>
                <p style="padding: 20px; color: var(--text-muted); font-size: 13.5px;">No notifications yet.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column;">
                    <?php foreach ($recentNotifications as $notif): ?>
                        <div style="padding: 12px 20px; border-bottom: 1px solid var(--border-color); display: flex; gap: 12px; align-items: flex-start; <?= !$notif['is_read'] ? 'background: #f0fdf4;' : '' ?>">
                            <span style="font-size: 16px;"><?= $notif['type'] === 'success' ? '✅' : 'ℹ️' ?></span>
                            <div style="flex: 1;">
                                <div style="font-size: 13.5px; font-weight: 600; color: var(--primary-navy);"><?= e($notif['title']) ?></div>
                                <div style="font-size: 12.5px; color: var(--text-muted);"><?= e($notif['message']) ?></div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?= e($notif['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
