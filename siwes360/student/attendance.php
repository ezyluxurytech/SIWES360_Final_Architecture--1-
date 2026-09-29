<?php
/**
 * SIWES360 - Student Attendance Portal
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch placement
$stmt = $pdo->prepare("SELECT placement_id FROM placements p JOIN students s ON p.student_id = s.student_id WHERE s.user_id = ? AND p.status IN ('active', 'approved') LIMIT 1");
$stmt->execute([$userId]);
$placementId = $stmt->fetchColumn();

$today = date('Y-m-d');
$todayAttendance = null;
if ($placementId) {
    $attStmt = $pdo->prepare("SELECT * FROM attendance WHERE placement_id = ? AND attendance_date = ? LIMIT 1");
    $attStmt->execute([$placementId, $today]);
    $todayAttendance = $attStmt->fetch();
}

// Attendance stats
$presents = 0;
$lates = 0;
$absents = 0;
$totalRecords = 0;
$attendancePercentage = 0;

if ($placementId) {
    $statsStmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS presents, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS lates, SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absents FROM attendance WHERE placement_id = ?");
    $statsStmt->execute([$placementId]);
    $st = $statsStmt->fetch();
    $totalRecords = (int)$st['total'];
    $presents = (int)$st['presents'];
    $lates = (int)$st['lates'];
    $absents = (int)$st['absents'];
    $attendancePercentage = $totalRecords > 0 ? round((($presents + $lates) / $totalRecords) * 100, 1) : 0;
}

// Filter query
$filterMonth = $_GET['month'] ?? '';
$records = [];
if ($placementId) {
    $sql = "SELECT * FROM attendance WHERE placement_id = ?";
    $params = [$placementId];
    if (!empty($filterMonth)) {
        $sql .= " AND attendance_date LIKE ?";
        $params[] = $filterMonth . '%';
    }
    $sql .= " ORDER BY attendance_date DESC";
    $recStmt = $pdo->prepare($sql);
    $recStmt->execute($params);
    $records = $recStmt->fetchAll();
}

$pageTitle = "Attendance System";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Industrial Attendance Record</h1>
        <p>Daily biometric/digital clock-in and clock-out logs for work session monitoring.</p>
    </div>
</div>

<!-- Attendance Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">📊</div>
        <div class="stat-details">
            <h3><?= $attendancePercentage ?>%</h3>
            <p>Overall Attendance</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-details">
            <h3><?= $presents ?></h3>
            <p>Total Days Present</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">⚠️</div>
        <div class="stat-details">
            <h3><?= $lates ?></h3>
            <p>Days Clocked Late</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📅</div>
        <div class="stat-details">
            <h3><?= $totalRecords ?></h3>
            <p>Total Recorded Shifts</p>
        </div>
    </div>
</div>

<!-- Clock In/Out Hero Panel -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Live Clock-In Station (<?= date('l, d M Y') ?>)</div>
        <span class="badge badge-<?= $todayAttendance ? strtolower($todayAttendance['status']) : 'incomplete' ?>">
            <?= $todayAttendance ? e($todayAttendance['status']) : 'Awaiting Clock-In' ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (!$placementId): ?>
            <div class="alert alert-warning">No active industrial placement found.</div>
        <?php elseif (!$todayAttendance): ?>
            <form action="/siwes360/api/attendance/clock_in.php" method="POST" style="max-width: 500px;">
                <input type="hidden" name="placement_id" value="<?= e($placementId) ?>">
                <div class="form-group">
                    <label for="notes">Daily Objective / Notes (Optional)</label>
                    <input type="text" id="notes" name="notes" placeholder="e.g. Continuing backend API security module">
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px;">
                    ⏱️ Clock In Now
                </button>
            </form>
        <?php elseif ($todayAttendance && empty($todayAttendance['clock_out'])): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="font-size: 18px; color: var(--primary-navy); margin-bottom: 4px;">Currently Clocked In</h3>
                    <p style="font-size: 14px; color: var(--text-muted);">
                        Clocked In At: <strong><?= e($todayAttendance['clock_in']) ?></strong> | Status: <strong><?= e($todayAttendance['status']) ?></strong>
                    </p>
                </div>
                <form action="/siwes360/api/attendance/clock_out.php" method="POST">
                    <input type="hidden" name="attendance_id" value="<?= e($todayAttendance['attendance_id']) ?>">
                    <button type="submit" class="btn btn-danger" style="padding: 12px 28px; font-size: 15px;">
                        ⏹️ Clock Out for Today
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-success" style="margin-bottom: 0;">
                ✅ Shift completed! Clock-In: <strong><?= e($todayAttendance['clock_in']) ?></strong> | Clock-Out: <strong><?= e($todayAttendance['clock_out']) ?></strong>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Attendance History -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Attendance History Logs</div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" style="display: flex; gap: 8px;">
                <input type="month" name="month" value="<?= e($filterMonth) ?>" style="padding: 6px 10px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <?php if ($filterMonth): ?>
                    <a href="/siwes360/student/attendance.php" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
            <input type="text" id="tableSearch" placeholder="Search records..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Status</th>
                        <th>Notes / Tasks</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No attendance records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><strong><?= date('D, d M Y', strtotime($row['attendance_date'])) ?></strong></td>
                                <td><?= e($row['clock_in']) ?></td>
                                <td><?= $row['clock_out'] ? e($row['clock_out']) : '<em style="color: #94a3b8;">Not clocked out</em>' ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($row['status']) ?>">
                                        <?= e($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= e($row['notes'] ?? '—') ?></td>
                                <td style="font-size: 12px; color: #94a3b8;"><?= e($row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
