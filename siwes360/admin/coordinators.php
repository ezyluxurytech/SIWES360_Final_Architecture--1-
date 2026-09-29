<?php
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');
$pdo = getDBConnection();
$rows = $pdo->query("
    SELECT c.coordinator_id, c.phone, u.full_name, u.email, u.status,
           d.name AS department, d.faculty,
           (SELECT COUNT(*) FROM students s WHERE s.department_id = c.department_id) AS student_count
    FROM coordinators c
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN departments d ON c.department_id = d.department_id
    ORDER BY u.full_name ASC
")->fetchAll();
$pageTitle='Coordinator Management';
require_once __DIR__ . '/../includes/header.php'; require_once __DIR__ . '/../includes/sidebar.php'; require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-title-row"><div class="page-heading"><h1>School Coordinators</h1><p>Manage departmental coordinators responsible for monitoring SIWES students.</p></div><a class="btn btn-primary" href="/siwes360/admin/users.php?role=coordinator">Manage Accounts</a></div>
<div class="card"><div class="card-header"><div class="card-title">Coordinators (<?=count($rows)?>)</div><input id="tableSearch" class="table-search" placeholder="Search coordinator or department..."></div>
<div class="card-body" style="padding:0"><div class="table-responsive"><table class="data-table"><thead><tr><th>Coordinator</th><th>Department</th><th>Faculty</th><th>Phone</th><th>Students</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?=e($r['full_name'])?></strong><div class="muted-small"><?=e($r['email'])?></div></td><td><?=e($r['department']??'—')?></td><td><?=e($r['faculty']??'—')?></td><td><?=e($r['phone'])?></td><td><span class="badge badge-info"><?= (int)$r['student_count'] ?></span></td><td><span class="badge badge-<?=e($r['status'])?>"><?=e(statusLabel($r['status']))?></span></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty-state">No coordinators registered.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
