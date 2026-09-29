<?php
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');
$pdo = getDBConnection();
$rows = $pdo->query("
    SELECT sp.supervisor_id, sp.position, sp.phone, u.full_name, u.email, u.status,
           o.name AS organization,
           (SELECT COUNT(*) FROM placements p WHERE p.supervisor_id = sp.supervisor_id) AS student_count
    FROM supervisors sp
    JOIN users u ON sp.user_id = u.user_id
    JOIN organizations o ON sp.organization_id = o.organization_id
    ORDER BY u.full_name ASC
")->fetchAll();
$pageTitle='Supervisor Management';
require_once __DIR__ . '/../includes/header.php'; require_once __DIR__ . '/../includes/sidebar.php'; require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-title-row"><div class="page-heading"><h1>Industry Supervisors</h1><p>Manage supervisors and see the students assigned to each industry mentor.</p></div><a class="btn btn-primary" href="/siwes360/admin/users.php?role=supervisor">Manage Accounts</a></div>
<div class="card"><div class="card-header"><div class="card-title">Supervisors (<?= count($rows) ?>)</div><input id="tableSearch" class="table-search" placeholder="Search supervisor or organization..."></div>
<div class="card-body" style="padding:0"><div class="table-responsive"><table class="data-table"><thead><tr><th>Supervisor</th><th>Organization</th><th>Position</th><th>Phone</th><th>Students</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?=e($r['full_name'])?></strong><div class="muted-small"><?=e($r['email'])?></div></td><td><?=e($r['organization'])?></td><td><?=e($r['position'])?></td><td><?=e($r['phone'])?></td><td><span class="badge badge-info"><?= (int)$r['student_count'] ?></span></td><td><span class="badge badge-<?=e($r['status'])?>"><?=e(statusLabel($r['status']))?></span></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty-state">No supervisors registered.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
