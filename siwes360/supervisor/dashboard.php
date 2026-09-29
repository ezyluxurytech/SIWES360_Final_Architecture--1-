<?php
/**
 * SIWES360 - Organization Supervisor Dashboard
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('supervisor');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get supervisor info
$supStmt = $pdo->prepare("
    SELECT sup.*, u.full_name, u.email, o.name AS organization_name, o.industry
    FROM supervisors sup
    JOIN users u ON sup.user_id = u.user_id
    JOIN organizations o ON sup.organization_id = o.organization_id
    WHERE sup.user_id = ?
");
$supStmt->execute([$userId]);
$supervisor = $supStmt->fetch();
$supervisorId = $supervisor['supervisor_id'] ?? 0;

// Assigned students
$studentsStmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email, d.name AS department_name, p.placement_id, p.position, p.status AS placement_status
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN departments d ON s.department_id = d.department_id
    WHERE p.supervisor_id = ?
");
$studentsStmt->execute([$supervisorId]);
$assignedStudents = $studentsStmt->fetchAll();
$totalStudents = count($assignedStudents);

// Pending weekly reports
$pendingRepStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM weekly_reports r
    JOIN placements p ON r.placement_id = p.placement_id
    WHERE p.supervisor_id = ? AND r.status = 'submitted'
");
$pendingRepStmt->execute([$supervisorId]);
$pendingReports = (int)$pendingRepStmt->fetchColumn();

// Pending activities
$pendingActStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM daily_activities a
    JOIN placements p ON a.placement_id = p.placement_id
    WHERE p.supervisor_id = ? AND a.status = 'submitted'
");
$pendingActStmt->execute([$supervisorId]);
$pendingActivities = (int)$pendingActStmt->fetchColumn();

// Completed evaluations
$evalDoneStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM evaluations e
    JOIN placements p ON e.placement_id = p.placement_id
    WHERE p.supervisor_id = ?
");
$evalDoneStmt->execute([$supervisorId]);
$completedEvals = (int)$evalDoneStmt->fetchColumn();

// Recent submitted weekly reports needing review
$recentReportsStmt = $pdo->prepare("
    SELECT r.*, u.full_name AS student_name, s.matric_no
    FROM weekly_reports r
    JOIN placements p ON r.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE p.supervisor_id = ?
    ORDER BY r.submitted_at DESC, r.created_at DESC
    LIMIT 5
");
$recentReportsStmt->execute([$supervisorId]);
$recentReports = $recentReportsStmt->fetchAll();

$pageTitle = "Supervisor Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Welcome, <?= e($supervisor['full_name']) ?>!</h1>
        <p><?= e($supervisor['position']) ?> · <strong><?= e($supervisor['organization_name']) ?></strong></p>
    </div>
    <div class="page-actions" style="display: flex; gap: 8px;">
        <a href="/siwes360/supervisor/reports.php" class="btn btn-primary">📑 Review Reports (<?= $pendingReports ?>)</a>
        <a href="/siwes360/supervisor/evaluations.php" class="btn btn-secondary">⭐ Submit Evaluation</a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👨‍🎓</div>
        <div class="stat-details">
            <h3><?= $totalStudents ?></h3>
            <p>Assigned Interns</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">⏳</div>
        <div class="stat-details">
            <h3><?= $pendingReports ?></h3>
            <p>Reports Awaiting Review</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📖</div>
        <div class="stat-details">
            <h3><?= $pendingActivities ?></h3>
            <p>Unreviewed Log Entries</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">⭐</div>
        <div class="stat-details">
            <h3><?= $completedEvals ?></h3>
            <p>Evaluations Completed</p>
        </div>
    </div>
</div>

<!-- Assigned Students List -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Supervised Students Under Your Placement</div>
        <a href="/siwes360/supervisor/students.php" class="btn btn-secondary btn-sm">View All Profiles</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Matric No</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignedStudents)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No students currently assigned under your supervision.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignedStudents as $st): ?>
                            <tr>
                                <td><strong><?= e($st['full_name']) ?></strong></td>
                                <td><code><?= e($st['matric_no']) ?></code></td>
                                <td><?= e($st['department_name']) ?> (<?= e($st['level']) ?>L)</td>
                                <td><?= e($st['position']) ?></td>
                                <td><?= e($st['phone']) ?></td>
                                <td><span class="badge badge-success"><?= e($st['placement_status']) ?></span></td>
                                <td>
                                    <a href="/siwes360/supervisor/attendance.php?student_id=<?= e($st['student_id']) ?>" class="btn btn-secondary btn-sm">Attendance</a>
                                    <a href="/siwes360/supervisor/evaluations.php?placement_id=<?= e($st['placement_id']) ?>" class="btn btn-primary btn-sm">Evaluate</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Weekly Reports Queue -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Weekly Industrial Reports Queue</div>
        <a href="/siwes360/supervisor/reports.php" class="btn btn-secondary btn-sm">Full Report Hub</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Week</th>
                        <th>Dates</th>
                        <th>Key Technical Highlights</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentReports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No weekly reports submitted yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentReports as $rep): ?>
                            <tr>
                                <td>
                                    <strong><?= e($rep['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($rep['matric_no']) ?></div>
                                </td>
                                <td>Week <?= e($rep['week_number']) ?></td>
                                <td style="font-size: 12px; white-space: nowrap;"><?= date('M d', strtotime($rep['week_start'])) ?> - <?= date('M d', strtotime($rep['week_end'])) ?></td>
                                <td style="max-width: 320px; font-size: 12.5px;"><?= e(substr($rep['activities'], 0, 120)) ?>...</td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '', $rep['status'])) ?>">
                                        <?= e($rep['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/siwes360/supervisor/reports.php?review_id=<?= e($rep['report_id']) ?>" class="btn btn-primary btn-sm">
                                        <?= $rep['status'] === 'submitted' ? '✍️ Review Now' : 'View Details' ?>
                                    </a>
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
