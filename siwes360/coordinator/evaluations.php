<?php
/**
 * SIWES360 - Coordinator Evaluations Review
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('coordinator');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$coorStmt = $pdo->prepare("SELECT department_id FROM coordinators WHERE user_id = ?");
$coorStmt->execute([$userId]);
$deptId = $coorStmt->fetchColumn();

// Fetch evaluations for students in this department
$stmt = $pdo->prepare("
    SELECT ev.*, s.matric_no, u.full_name AS student_name, o.name AS org_name, evaluator_u.full_name AS evaluator_name
    FROM evaluations ev
    JOIN placements p ON ev.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    JOIN users evaluator_u ON ev.evaluator_id = evaluator_u.user_id
    WHERE s.department_id = ?
    ORDER BY ev.created_at DESC
");
$stmt->execute([$deptId]);
$evaluations = $stmt->fetchAll();

$pageTitle = "Departmental Evaluations";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Departmental Performance Evaluations</h1>
        <p>Official technical evaluation scores submitted by industry supervisors.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Completed Evaluations (<?= count($evaluations) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search evaluations..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Organization</th>
                        <th>Technical</th>
                        <th>Punctuality</th>
                        <th>Communication</th>
                        <th>Overall Score</th>
                        <th>Evaluator</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluations)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px;">No evaluations recorded for this department yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($evaluations as $ev): ?>
                            <tr>
                                <td>
                                    <strong><?= e($ev['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($ev['matric_no']) ?></div>
                                </td>
                                <td><?= e($ev['org_name']) ?></td>
                                <td><?= e($ev['technical_skills']) ?>%</td>
                                <td><?= e($ev['punctuality']) ?>%</td>
                                <td><?= e($ev['communication']) ?>%</td>
                                <td>
                                    <span class="badge badge-success" style="font-size: 13px; font-weight: 700;">
                                        <?= number_format($ev['overall_score'], 1) ?>%
                                    </span>
                                </td>
                                <td><?= e($ev['evaluator_name']) ?></td>
                                <td style="font-size: 12px; max-width: 250px;"><?= e($ev['comments']) ?></td>
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
