<?php
/**
 * SIWES360 - Coordinator Students Directory
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('coordinator');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Coordinator's department
$coorStmt = $pdo->prepare("SELECT department_id FROM coordinators WHERE user_id = ?");
$coorStmt->execute([$userId]);
$deptId = $coorStmt->fetchColumn();

// Filter level
$filterLevel = $_GET['level'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT s.*, u.full_name, u.email, u.status AS user_status,
           p.position, p.status AS placement_status, o.name AS org_name,
           (SELECT COUNT(*) FROM attendance a WHERE a.placement_id = p.placement_id AND a.status = 'present') AS total_presents,
           (SELECT COUNT(*) FROM weekly_reports wr WHERE wr.placement_id = p.placement_id) AS total_reports
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN placements p ON s.student_id = p.student_id AND p.status IN ('active', 'approved')
    LEFT JOIN organizations o ON p.organization_id = o.organization_id
    WHERE s.department_id = ?
";
$params = [$deptId];
if (!empty($filterLevel)) {
    $sql .= " AND s.level = ?";
    $params[] = $filterLevel;
}
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR s.matric_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY s.matric_no ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = "Departmental Students";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Departmental Student Roster</h1>
        <p>Monitor SIWES enrollment, placement completion, and academic compliance.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Enrolled Students (<?= count($students) ?>)</div>
        <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <select name="level" style="padding: 6px 10px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                <option value="">-- All Levels --</option>
                <option value="300" <?= $filterLevel === '300' ? 'selected' : '' ?>>300 Level</option>
                <option value="400" <?= $filterLevel === '400' ? 'selected' : '' ?>>400 Level</option>
            </select>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name or matric..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($filterLevel || $search): ?>
                <a href="/siwes360/coordinator/students.php" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Matric No</th>
                        <th>Student Full Name</th>
                        <th>Level</th>
                        <th>Host Company</th>
                        <th>Position</th>
                        <th>Presents</th>
                        <th>Reports</th>
                        <th>Placement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">No students match the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><code><?= e($st['matric_no']) ?></code></td>
                                <td>
                                    <strong><?= e($st['full_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($st['email']) ?></div>
                                </td>
                                <td><?= e($st['level']) ?>L</td>
                                <td><?= e($st['org_name'] ?? 'Unassigned') ?></td>
                                <td><?= e($st['position'] ?? '—') ?></td>
                                <td><?= (int)$st['total_presents'] ?> days</td>
                                <td><?= (int)$st['total_reports'] ?> submitted</td>
                                <td>
                                    <span class="badge badge-<?= $st['placement_status'] ? 'success' : 'warning' ?>">
                                        <?= e($st['placement_status'] ?? 'pending') ?>
                                    </span>
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
