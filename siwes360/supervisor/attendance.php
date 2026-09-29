<?php
/**
 * SIWES360 - Supervisor Attendance Monitoring
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('supervisor');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get supervisor id
$supStmt = $pdo->prepare("SELECT supervisor_id FROM supervisors WHERE user_id = ?");
$supStmt->execute([$userId]);
$supervisorId = $supStmt->fetchColumn();

// Filter student
$filterStudent = (int)($_GET['student_id'] ?? 0);
$filterDate = $_GET['date'] ?? '';

$sql = "
    SELECT a.*, u.full_name AS student_name, s.matric_no, p.position
    FROM attendance a
    JOIN placements p ON a.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE p.supervisor_id = ?
";
$params = [$supervisorId];
if ($filterStudent > 0) {
    $sql .= " AND s.student_id = ?";
    $params[] = $filterStudent;
}
if (!empty($filterDate)) {
    $sql .= " AND a.attendance_date = ?";
    $params[] = $filterDate;
}
$sql .= " ORDER BY a.attendance_date DESC, a.clock_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceRecords = $stmt->fetchAll();

// Get list of assigned students for dropdown
$stListStmt = $pdo->prepare("
    SELECT s.student_id, u.full_name, s.matric_no
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE p.supervisor_id = ?
");
$stListStmt->execute([$supervisorId]);
$studentsList = $stListStmt->fetchAll();

$pageTitle = "Intern Attendance Monitoring";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Trainee Attendance Monitoring</h1>
        <p>Monitor real-time clock-in and clock-out logs across your assigned organizational interns.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Attendance Logs (<?= count($attendanceRecords) ?>)</div>
        <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <select name="student_id" style="padding: 6px 10px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                <option value="">-- All Supervised Students --</option>
                <?php foreach ($studentsList as $st): ?>
                    <option value="<?= e($st['student_id']) ?>" <?= $filterStudent === (int)$st['student_id'] ? 'selected' : '' ?>>
                        <?= e($st['full_name']) ?> (<?= e($st['matric_no']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date" value="<?= e($filterDate) ?>" style="padding: 6px 10px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($filterStudent || $filterDate): ?>
                <a href="/siwes360/supervisor/attendance.php" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Position</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Status</th>
                        <th>Daily Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceRecords)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No attendance logs found matching this filter.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendanceRecords as $row): ?>
                            <tr>
                                <td><strong><?= date('D, d M Y', strtotime($row['attendance_date'])) ?></strong></td>
                                <td>
                                    <strong><?= e($row['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($row['matric_no']) ?></div>
                                </td>
                                <td><?= e($row['position']) ?></td>
                                <td><span style="font-family: monospace; font-weight: 600;"><?= e($row['clock_in']) ?></span></td>
                                <td>
                                    <?php if ($row['clock_out']): ?>
                                        <span style="font-family: monospace; font-weight: 600;"><?= e($row['clock_out']) ?></span>
                                    <?php else: ?>
                                        <em style="color: #94a3b8;">Active Shift</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($row['status']) ?>">
                                        <?= e($row['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-muted); max-width: 250px;">
                                    <?= e($row['notes'] ?? '—') ?>
                                </td>
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
