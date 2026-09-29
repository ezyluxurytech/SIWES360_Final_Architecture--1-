<?php
/**
 * SIWES360 - School Coordinator Dashboard
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('coordinator');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get coordinator and department
$coorStmt = $pdo->prepare("
    SELECT c.*, u.full_name, u.email, d.department_id, d.name AS department_name, d.faculty
    FROM coordinators c
    JOIN users u ON c.user_id = u.user_id
    JOIN departments d ON c.department_id = d.department_id
    WHERE c.user_id = ?
");
$coorStmt->execute([$userId]);
$coordinator = $coorStmt->fetch();
$deptId = $coordinator['department_id'] ?? 1;

// Department statistics
$stCountStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE department_id = ?");
$stCountStmt->execute([$deptId]);
$totalDeptStudents = (int)$stCountStmt->fetchColumn();

// Active placements
$plCountStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    WHERE s.department_id = ? AND p.status = 'active'
");
$plCountStmt->execute([$deptId]);
$activePlacements = (int)$plCountStmt->fetchColumn();

// Weekly reports submitted across department
$repCountStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM weekly_reports r
    JOIN placements p ON r.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    WHERE s.department_id = ?
");
$repCountStmt->execute([$deptId]);
$totalReports = (int)$repCountStmt->fetchColumn();

// Evaluations completed
$evalCountStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM evaluations e
    JOIN placements p ON e.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    WHERE s.department_id = ?
");
$evalCountStmt->execute([$deptId]);
$totalEvaluations = (int)$evalCountStmt->fetchColumn();

// Recent students under department
$studentsStmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email, p.position, p.status AS placement_status, o.name AS org_name, sup_u.full_name AS supervisor_name
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN placements p ON s.student_id = p.student_id AND p.status IN ('active', 'approved')
    LEFT JOIN organizations o ON p.organization_id = o.organization_id
    LEFT JOIN supervisors sup ON p.supervisor_id = sup.supervisor_id
    LEFT JOIN users sup_u ON sup.user_id = sup_u.user_id
    WHERE s.department_id = ?
    ORDER BY s.created_at DESC
    LIMIT 6
");
$studentsStmt->execute([$deptId]);
$deptStudents = $studentsStmt->fetchAll();

$pageTitle = "Coordinator Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Institutional Coordinator Portal</h1>
        <p>Coordinator: <strong><?= e($coordinator['full_name']) ?></strong> | Department of <?= e($coordinator['department_name']) ?> (<?= e($coordinator['faculty']) ?>)</p>
    </div>
    <div class="page-actions" style="display: flex; gap: 8px;">
        <a href="/siwes360/coordinator/students.php" class="btn btn-primary">👨‍🎓 Student Roster</a>
        <a href="/siwes360/coordinator/placements.php" class="btn btn-secondary">🏢 Placements Oversight</a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👨‍🎓</div>
        <div class="stat-details">
            <h3><?= $totalDeptStudents ?></h3>
            <p>Departmental Students</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🏢</div>
        <div class="stat-details">
            <h3><?= $activePlacements ?></h3>
            <p>Active Verified Placements</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📑</div>
        <div class="stat-details">
            <h3><?= $totalReports ?></h3>
            <p>Logged Weekly Reports</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">⭐</div>
        <div class="stat-details">
            <h3><?= $totalEvaluations ?></h3>
            <p>Submitted Evaluations</p>
        </div>
    </div>
</div>

<!-- Departmental Students Quick Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Department SIWES Trainees (Computer Science)</div>
        <a href="/siwes360/coordinator/students.php" class="btn btn-secondary btn-sm">Full Directory</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Matric No</th>
                        <th>Host Organization</th>
                        <th>Position</th>
                        <th>Supervisor</th>
                        <th>Placement</th>
                        <th>Quick Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deptStudents)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No students enrolled in this department yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deptStudents as $st): ?>
                            <tr>
                                <td>
                                    <strong><?= e($st['full_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($st['email']) ?></div>
                                </td>
                                <td><code><?= e($st['matric_no']) ?></code></td>
                                <td><?= e($st['org_name'] ?? 'Not Assigned') ?></td>
                                <td><?= e($st['position'] ?? '—') ?></td>
                                <td><?= e($st['supervisor_name'] ?? '—') ?></td>
                                <td>
                                    <span class="badge badge-<?= $st['placement_status'] ? 'success' : 'warning' ?>">
                                        <?= e($st['placement_status'] ?? 'pending') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/siwes360/coordinator/attendance.php?student_id=<?= e($st['student_id']) ?>" class="btn btn-secondary btn-sm">Attendance</a>
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
