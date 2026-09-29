<?php
/**
 * SIWES360 - Coordinator Placements Monitoring
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('coordinator');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get coordinator department
$coorStmt = $pdo->prepare("SELECT department_id FROM coordinators WHERE user_id = ?");
$coorStmt->execute([$userId]);
$deptId = $coorStmt->fetchColumn();

// Fetch placements under department
$stmt = $pdo->prepare("
    SELECT p.*, s.matric_no, s.level, u.full_name AS student_name,
           o.name AS organization_name, o.city, o.state,
           sup_u.full_name AS supervisor_name, sup.position AS supervisor_position,
           ses.name AS session_name
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    JOIN supervisors sup ON p.supervisor_id = sup.supervisor_id
    JOIN users sup_u ON sup.user_id = sup_u.user_id
    JOIN it_sessions ses ON p.session_id = ses.session_id
    WHERE s.department_id = ?
    ORDER BY p.start_date DESC
");
$stmt->execute([$deptId]);
$placements = $stmt->fetchAll();

$pageTitle = "Placements Monitoring";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Student Placements Oversight</h1>
        <p>Verified institutional linkages between department undergraduates and accredited host organizations.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">All Placements (<?= count($placements) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search placements..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Organization</th>
                        <th>Position</th>
                        <th>Industry Supervisor</th>
                        <th>Session</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($placements)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No placements found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($placements as $pl): ?>
                            <tr>
                                <td>
                                    <strong><?= e($pl['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($pl['matric_no']) ?> (<?= e($pl['level']) ?>L)</div>
                                </td>
                                <td>
                                    <strong><?= e($pl['organization_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($pl['city']) ?>, <?= e($pl['state']) ?></div>
                                </td>
                                <td><?= e($pl['position']) ?></td>
                                <td>
                                    <?= e($pl['supervisor_name']) ?>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($pl['supervisor_position']) ?></div>
                                </td>
                                <td><?= e($pl['session_name']) ?></td>
                                <td style="font-size: 12px; white-space: nowrap;">
                                    <?= date('d M Y', strtotime($pl['start_date'])) ?> – <?= date('d M Y', strtotime($pl['end_date'])) ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($pl['status']) ?>">
                                        <?= e($pl['status']) ?>
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
