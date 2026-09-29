<?php
/**
 * SIWES360 - Admin Comprehensive Reports & Analytics
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

// Analytics breakdown
$deptStats = $pdo->query("
    SELECT d.name AS department_name, COUNT(s.student_id) AS total_students,
           COUNT(p.placement_id) AS total_placed
    FROM departments d
    LEFT JOIN students s ON d.department_id = s.department_id
    LEFT JOIN placements p ON s.student_id = p.student_id
    GROUP BY d.department_id
")->fetchAll();

$evalStats = $pdo->query("
    SELECT AVG(technical_skills) AS avg_tech, AVG(punctuality) AS avg_punct,
           AVG(communication) AS avg_comm, AVG(teamwork) AS avg_team,
           AVG(problem_solving) AS avg_prob, AVG(overall_score) AS avg_overall
    FROM evaluations
")->fetch();

$recentWeeklyReports = $pdo->query("
    SELECT wr.*, s.matric_no, u.full_name AS student_name, o.name AS org_name
    FROM weekly_reports wr
    JOIN placements p ON wr.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    ORDER BY wr.submitted_at DESC
    LIMIT 20
")->fetchAll();

$pageTitle = "System Reports";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Institutional SIWES Reports & Metrics</h1>
        <p>Comprehensive analytical telemetry on placement coverage, attendance rates, and trainee performance.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Departmental Placement Coverage -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Departmental Placement Distribution</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Enrolled Students</th>
                        <th>Placed Trainees</th>
                        <th>Placement Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deptStats as $ds): 
                        $rate = $ds['total_students'] > 0 ? round(($ds['total_placed'] / $ds['total_students']) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?= e($ds['department_name']) ?></strong></td>
                            <td><?= e($ds['total_students']) ?></td>
                            <td><?= e($ds['total_placed']) ?></td>
                            <td>
                                <span class="badge badge-<?= $rate >= 75 ? 'success' : 'warning' ?>">
                                    <?= $rate ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Institutional Quality Average Scores -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Institutional Trainee Evaluation Averages</div>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Overall Institution Average</div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--primary-navy); margin-top: 4px;"><?= number_format((float)($evalStats['avg_overall'] ?? 0), 1) ?>%</div>
                </div>
                <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Technical Competence</div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--accent-blue); margin-top: 4px;"><?= number_format((float)($evalStats['avg_tech'] ?? 0), 1) ?>%</div>
                </div>
                <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Average Punctuality</div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--success); margin-top: 4px;"><?= number_format((float)($evalStats['avg_punct'] ?? 0), 1) ?>%</div>
                </div>
                <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Workplace Teamwork</div>
                    <div style="font-size: 28px; font-weight: 800; color: #7c3aed; margin-top: 4px;"><?= number_format((float)($evalStats['avg_team'] ?? 0), 1) ?>%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Weekly Reports Across All Placements -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Cross-Organizational Weekly Reports Stream</div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Organization</th>
                        <th>Week</th>
                        <th>Duration</th>
                        <th>Activities Snippet</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentWeeklyReports as $r): ?>
                        <tr>
                            <td>
                                <strong><?= e($r['student_name']) ?></strong>
                                <div style="font-size: 11px; color: #94a3b8;"><?= e($r['matric_no']) ?></div>
                            </td>
                            <td><?= e($r['org_name']) ?></td>
                            <td>Week <?= e($r['week_number']) ?></td>
                            <td style="font-size: 12px;"><?= date('M d', strtotime($r['week_start'])) ?> – <?= date('M d, Y', strtotime($r['week_end'])) ?></td>
                            <td style="max-width: 320px; font-size: 12.5px;"><?= e(substr($r['activities'], 0, 140)) ?>...</td>
                            <td><span class="badge badge-<?= strtolower(str_replace(' ', '', $r['status'])) ?>"><?= e($r['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
