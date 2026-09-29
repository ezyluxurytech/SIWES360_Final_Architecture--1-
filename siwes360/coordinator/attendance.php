<?php
/**
 * SIWES360 - Coordinator Attendance Oversight
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('coordinator');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$coorStmt = $pdo->prepare("SELECT department_id FROM coordinators WHERE user_id = ?");
$coorStmt->execute([$userId]);
$deptId = $coorStmt->fetchColumn();

$filterDate = $_GET['date'] ?? '';

$sql = "
    SELECT a.*, u.full_name AS student_name, s.matric_no, o.name AS org_name
    FROM attendance a
    JOIN placements p ON a.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    WHERE s.department_id = ?
";
$params = [$deptId];
if (!empty($filterDate)) {
    $sql .= " AND a.attendance_date = ?";
    $params[] = $filterDate;
}
$sql .= " ORDER BY a.attendance_date DESC, a.clock_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendanceLogs = $stmt->fetchAll();

$pageTitle = "Departmental Attendance Oversight";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Departmental Attendance Oversight</h1>
        <p>Real-time institutional attendance records across external internship organizations.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Attendance Entries (<?= count($attendanceLogs) ?>)</div>
        <form method="GET" style="display: flex; gap: 8px;">
            <input type="date" name="date" value="<?= e($filterDate) ?>" style="padding: 6px 10px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <button type="submit" class="btn btn-secondary btn-sm">Filter Date</button>
            <?php if ($filterDate): ?>
                <a href="/siwes360/coordinator/attendance.php" class="btn btn-secondary btn-sm">Reset</a>
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
                        <th>Host Organization</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceLogs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No attendance logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendanceLogs as $row): ?>
                            <tr>
                                <td><strong><?= date('D, d M Y', strtotime($row['attendance_date'])) ?></strong></td>
                                <td>
                                    <strong><?= e($row['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($row['matric_no']) ?></div>
                                </td>
                                <td><?= e($row['org_name']) ?></td>
                                <td><code><?= e($row['clock_in']) ?></code></td>
                                <td><?= $row['clock_out'] ? '<code>' . e($row['clock_out']) . '</code>' : '<em style="color:#94a3b8;">Active</em>' ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($row['status']) ?>">
                                        <?= e($row['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; color: var(--text-muted); max-width: 250px;"><?= e($row['notes'] ?? '—') ?></td>
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
