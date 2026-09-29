<?php
/**
 * SIWES360 - Supervisor Daily Activities Review
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('supervisor');

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}
$userId = $_SESSION['user_id'];

// Get supervisor id
$supStmt = $pdo->prepare("SELECT supervisor_id FROM supervisors WHERE user_id = ?");
$supStmt->execute([$userId]);
$supervisorId = $supStmt->fetchColumn();

// Handle Comment & Status Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'review_activity') {
    $actId = (int)($_POST['activity_id'] ?? 0);
    $status = $_POST['status'] ?? 'reviewed'; // 'reviewed' or 'rejected'
    $comment = trim($_POST['supervisor_comment'] ?? '');

    if ($actId > 0) {
        $allowed = $pdo->prepare("
            SELECT a.activity_id
            FROM daily_activities a
            JOIN placements p ON a.placement_id = p.placement_id
            WHERE a.activity_id = ? AND p.supervisor_id = ?
        ");
        $allowed->execute([$actId, $supervisorId]);
        if (!$allowed->fetchColumn()) {
            setFlash('danger', 'You can only review activities belonging to your assigned students.');
            redirect('/siwes360/supervisor/activities.php');
        }

        $upStmt = $pdo->prepare("UPDATE daily_activities SET status = ?, supervisor_comment = ? WHERE activity_id = ?");
        $upStmt->execute([$status, $comment, $actId]);
        logAudit($userId, 'ACTIVITY_REVIEWED', "Marked activity ID {$actId} as {$status}");
        setFlash('success', "Daily activity entry marked as {$status}.");
    }
}

// Fetch activities for all students placed under this supervisor
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name AS student_name, s.matric_no
    FROM daily_activities a
    JOIN placements p ON a.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE p.supervisor_id = ?
    ORDER BY a.activity_date DESC, a.created_at DESC
");
$stmt->execute([$supervisorId]);
$activities = $stmt->fetchAll();

$pageTitle = "Daily Activities Oversight";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Student Daily Activities Oversight</h1>
        <p>Review daily technical log entries and provide constructive mentorship commentary.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Daily Activity Submissions (<?= count($activities) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Filter by student or skill..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Student</th>
                        <th>Work Description</th>
                        <th>Skills & Tools</th>
                        <th>Challenges & Solutions</th>
                        <th>Status</th>
                        <th>Supervisor Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No daily activities logged by interns yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <strong><?= e($act['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($act['matric_no']) ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?= date('d M Y', strtotime($act['activity_date'])) ?></div>
                                </td>
                                <td style="max-width: 260px;"><?= nl2br(e($act['description'])) ?></td>
                                <td style="max-width: 200px; font-size: 12px;">
                                    <div style="color: var(--accent-blue); font-weight: 600;">Skills: <?= e($act['skills_learned']) ?></div>
                                    <div style="color: var(--text-muted); margin-top: 2px;">Tools: <?= e($act['tools_used']) ?></div>
                                </td>
                                <td style="max-width: 200px; font-size: 12px;">
                                    <div><strong>Challenge:</strong> <?= e($act['challenges'] ?? 'None') ?></div>
                                    <div style="color: var(--text-muted);"><strong>Lesson:</strong> <?= e($act['lessons_learned'] ?? 'None') ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($act['status']) ?>">
                                        <?= e($act['status']) ?>
                                    </span>
                                </td>
                                <td style="max-width: 220px;">
                                    <?php if (!empty($act['supervisor_comment'])): ?>
                                        <div style="font-size: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px 10px; border-radius: var(--radius-sm); color: #15803d; margin-bottom: 6px;">
                                            <strong>Remark:</strong> <?= e($act['supervisor_comment']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openCommentModal(<?= e($act['activity_id']) ?>, '<?= e($act['student_name']) ?>', '<?= date('d M Y', strtotime($act['activity_date'])) ?>')">
                                        💬 <?= empty($act['supervisor_comment']) ? 'Add Comment' : 'Edit Comment' ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Activity Comment -->
<div class="modal-backdrop" id="commentModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Supervisor Activity Feedback</h3>
            <button type="button" onclick="closeModal('commentModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/supervisor/activities.php" method="POST">
            <input type="hidden" name="action_type" value="review_activity">
            <input type="hidden" name="activity_id" id="actModalId">
            <div class="modal-body">
                <p id="actModalNotice" style="font-size: 13.5px; font-weight: 600; color: var(--primary-navy); margin-bottom: 14px;"></p>

                <div class="form-group">
                    <label for="act_status">Review Decision</label>
                    <select name="status" id="act_status" required>
                        <option value="reviewed">✅ Mark as Reviewed / Verified</option>
                        <option value="rejected">❌ Mark as Rejected</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="supervisor_comment">Supervisor Comments / Guidance</label>
                    <textarea name="supervisor_comment" id="supervisor_comment" rows="3" required placeholder="Acknowledge quality of work or give engineering guidance..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('commentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCommentModal(actId, studentName, dateStr) {
    document.getElementById('actModalId').value = actId;
    document.getElementById('actModalNotice').textContent = "Adding feedback for " + studentName + " on " + dateStr;
    openModal('commentModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
