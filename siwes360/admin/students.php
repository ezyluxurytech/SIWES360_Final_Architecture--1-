<?php
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');
$pdo = getDBConnection();
$students = $pdo->query("
    SELECT s.student_id, s.matric_no, s.level, s.phone, s.gender,
           u.full_name, u.email, u.status,
           d.name AS department,
           p.status AS placement_status, o.name AS organization
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN placements p ON p.placement_id = (
        SELECT p2.placement_id FROM placements p2
        WHERE p2.student_id = s.student_id
        ORDER BY p2.created_at DESC LIMIT 1
    )
    LEFT JOIN organizations o ON p.organization_id = o.organization_id
    ORDER BY u.full_name ASC
")->fetchAll();
$pageTitle = 'Student Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-title-row">
  <div class="page-heading"><h1>Student Management</h1><p>View registered students, academic details and current SIWES placement status.</p></div>
  <a class="btn btn-primary" href="/siwes360/admin/users.php?role=student">Manage Student Accounts</a>
</div>
<div class="card">
  <div class="card-header"><div class="card-title">Registered Students (<?= count($students) ?>)</div><input id="tableSearch" class="table-search" placeholder="Search student, matric or department..."></div>
  <div class="card-body" style="padding:0"><div class="table-responsive"><table class="data-table">
    <thead><tr><th>Student</th><th>Matric / Level</th><th>Department</th><th>Phone</th><th>Account</th><th>Placement</th></tr></thead>
    <tbody>
    <?php foreach ($students as $s): ?>
      <tr>
        <td><strong><?= e($s['full_name']) ?></strong><div class="muted-small"><?= e($s['email']) ?></div></td>
        <td><code><?= e($s['matric_no']) ?></code><div class="muted-small"><?= e($s['level']) ?> Level</div></td>
        <td><?= e($s['department']) ?></td><td><?= e($s['phone']) ?></td>
        <td><span class="badge badge-<?= e($s['status']) ?>"><?= e(statusLabel($s['status'])) ?></span></td>
        <td><?= $s['organization'] ? '<strong>'.e($s['organization']).'</strong><div class="muted-small">'.e(statusLabel($s['placement_status'])).'</div>' : '<span class="muted-small">Not assigned</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$students): ?><tr><td colspan="6" class="empty-state">No students have been registered yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
