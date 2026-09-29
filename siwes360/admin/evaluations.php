<?php
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');
$pdo=getDBConnection();
$rows=$pdo->query("
 SELECT e.*, u.full_name AS evaluator_name, su.full_name AS student_name, s.matric_no,
        o.name AS organization
 FROM evaluations e
 JOIN users u ON e.evaluator_id=u.user_id
 JOIN placements p ON e.placement_id=p.placement_id
 JOIN students s ON p.student_id=s.student_id
 JOIN users su ON s.user_id=su.user_id
 JOIN organizations o ON p.organization_id=o.organization_id
 ORDER BY e.created_at DESC
")->fetchAll();
$pageTitle='Evaluation Records';
require_once __DIR__.'/../includes/header.php'; require_once __DIR__.'/../includes/sidebar.php'; require_once __DIR__.'/../includes/navbar.php';
?>
<div class="page-title-row"><div class="page-heading"><h1>Evaluation Records</h1><p>Review performance evaluations submitted by supervisors and coordinators.</p></div></div>
<div class="card"><div class="card-header"><div class="card-title">Evaluations (<?=count($rows)?>)</div><input id="tableSearch" class="table-search" placeholder="Search student or evaluator..."></div>
<div class="card-body" style="padding:0"><div class="table-responsive"><table class="data-table"><thead><tr><th>Student</th><th>Organization</th><th>Evaluator</th><th>Role</th><th>Overall</th><th>Date</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><strong><?=e($r['student_name'])?></strong><div class="muted-small"><?=e($r['matric_no'])?></div></td><td><?=e($r['organization'])?></td><td><?=e($r['evaluator_name'])?></td><td><?=e(ucfirst($r['evaluator_role']))?></td><td><span class="score-pill"><?=e($r['overall_score'])?>%</span></td><td><?=e(date('d M Y',strtotime($r['created_at'])))?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty-state">No evaluations have been submitted.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
