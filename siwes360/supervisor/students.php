<?php
/**
 * SIWES360 - Supervisor Assigned Students List
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('supervisor');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$supStmt = $pdo->prepare("SELECT supervisor_id FROM supervisors WHERE user_id = ?");
$supStmt->execute([$userId]);
$supervisorId = $supStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email, d.name AS department_name, d.faculty,
           p.placement_id, p.position, p.start_date, p.end_date, p.status AS placement_status,
           ses.name AS session_name
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN departments d ON s.department_id = d.department_id
    JOIN it_sessions ses ON p.session_id = ses.session_id
    WHERE p.supervisor_id = ?
    ORDER BY u.full_name ASC
");
$stmt->execute([$supervisorId]);
$students = $stmt->fetchAll();

$pageTitle = "Assigned Students";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Assigned Industrial Interns</h1>
        <p>Students under your direct operational mentorship and weekly evaluation.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Trainees Directory (<?= count($students) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search students..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Matriculation No</th>
                        <th>Department</th>
                        <th>Internship Position</th>
                        <th>Duration</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No students assigned under your supervisor account.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td>
                                    <strong><?= e($st['full_name']) ?></strong>
                                    <div style="font-size: 11.5px; color: #94a3b8;"><?= e($st['email']) ?></div>
                                </td>
                                <td><code><?= e($st['matric_no']) ?></code></td>
                                <td><?= e($st['department_name']) ?> (<?= e($st['level']) ?>L)</td>
                                <td><strong><?= e($st['position']) ?></strong></td>
                                <td style="font-size: 12px; white-space: nowrap;">
                                    <?= date('d M Y', strtotime($st['start_date'])) ?> – <?= date('d M Y', strtotime($st['end_date'])) ?>
                                </td>
                                <td><?= e($st['phone']) ?></td>
                                <td style="white-space: nowrap;">
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

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
