<?php
/**
 * SIWES360 - Coordinator Weekly Reports & Evaluations
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('coordinator');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$coorStmt = $pdo->prepare("SELECT department_id FROM coordinators WHERE user_id = ?");
$coorStmt->execute([$userId]);
$deptId = $coorStmt->fetchColumn();

// Fetch reports
$stmt = $pdo->prepare("
    SELECT r.*, s.matric_no, u.full_name AS student_name, o.name AS org_name,
           rev.decision, rev.comment AS supervisor_comment
    FROM weekly_reports r
    JOIN placements p ON r.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    LEFT JOIN report_reviews rev ON r.report_id = rev.report_id
    WHERE s.department_id = ?
    ORDER BY r.week_number DESC
");
$stmt->execute([$deptId]);
$reports = $stmt->fetchAll();

$pageTitle = "Departmental Reports";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Departmental Weekly Reports</h1>
        <p>Institutional audit of weekly log submissions and organization supervisor reviews.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Reports Archive (<?= count($reports) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search reports..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Organization</th>
                        <th>Week</th>
                        <th>Activities</th>
                        <th>Status</th>
                        <th>Supervisor Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No reports logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($r['matric_no']) ?></div>
                                </td>
                                <td><?= e($r['org_name']) ?></td>
                                <td><strong>Week <?= e($r['week_number']) ?></strong></td>
                                <td style="max-width: 300px; font-size: 12.5px;"><?= nl2br(e(substr($r['activities'], 0, 160))) ?>...</td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '', $r['status'])) ?>">
                                        <?= e($r['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; max-width: 200px;">
                                    <?php if (!empty($r['decision'])): ?>
                                        <strong><?= e($r['decision']) ?></strong>: <?= e($r['supervisor_comment']) ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Pending Review</span>
                                    <?php endif; ?>
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
