<?php
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');
$pdo = getDBConnection();
$rows = $pdo->query("
    SELECT a.attendance_id, a.attendance_date, a.clock_in, a.clock_out, a.status,
           u.full_name AS student_name, s.matric_no, o.name AS organization
    FROM attendance a
    JOIN placements p ON a.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    ORDER BY a.attendance_date DESC, a.clock_in DESC
    LIMIT 300
")->fetchAll();
$pageTitle='Attendance Monitoring';
require_once __DIR__ . '/../includes/header.php'; require_once __DIR__ . '/../includes/sidebar.php'; require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="page-title-row"><div class="page-heading"><h1>Attendance Monitoring</h1><p>Central view of student daily clock-in and clock-out records.</p></div></div>
<div class="card"><div class="card-header"><div class="card-title">Recent Attendance Records</div><input id="tableSearch" class="table-search" placeholder="Search student or organization..."></div>
<div class="card-body" style="padding:0"><div class="table-responsive"><table class="data-table"><thead><tr><th>Date</th><th>Student</th><th>Organization</th><th>Clock In</th><th>Clock Out</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=e(date('d M Y',strtotime($r['attendance_date'])))?></td><td><strong><?=e($r['student_name'])?></strong><div class="muted-small"><?=e($r['matric_no'])?></div></td><td><?=e($r['organization'])?></td><td><?=e($r['clock_in']??'—')?></td><td><?=e($r['clock_out']??'—')?></td><td><span class="badge badge-<?=e($r['status'])?>"><?=e(statusLabel($r['status']))?></span></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty-state">No attendance records yet.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
