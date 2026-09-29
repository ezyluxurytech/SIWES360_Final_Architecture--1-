<?php
/**
 * SIWES360 - Daily Digital Logbook
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch placement
$stmt = $pdo->prepare("SELECT placement_id FROM placements p JOIN students s ON p.student_id = s.student_id WHERE s.user_id = ? AND p.status IN ('active', 'approved') LIMIT 1");
$stmt->execute([$userId]);
$placementId = $stmt->fetchColumn();

$activities = [];
if ($placementId) {
    $actStmt = $pdo->prepare("SELECT * FROM daily_activities WHERE placement_id = ? ORDER BY activity_date DESC, created_at DESC");
    $actStmt->execute([$placementId]);
    $activities = $actStmt->fetchAll();
}

$pageTitle = "Daily Digital Logbook";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Daily Digital Logbook</h1>
        <p>Document work tasks, technologies practiced, technical challenges, and solutions encountered.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addActivityModal')">
            ✍️ Log Today's Activity
        </button>
    </div>
</div>

<!-- Activities List -->
<div class="card">
    <div class="card-header">
        <div class="card-title">All Logbook Entries (<?= count($activities) ?> records)</div>
        <input type="text" id="tableSearch" placeholder="Filter activities..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Work Description</th>
                        <th>Skills & Tools</th>
                        <th>Challenges & Lessons</th>
                        <th>Status</th>
                        <th>Supervisor Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No daily activities recorded yet. Click "Log Today's Activity" above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                            <tr>
                                <td style="white-space: nowrap;">
                                    <strong><?= date('D, d M Y', strtotime($act['activity_date'])) ?></strong>
                                </td>
                                <td style="max-width: 280px;"><?= nl2br(e($act['description'])) ?></td>
                                <td style="max-width: 220px;">
                                    <div style="font-size: 12.5px; font-weight: 600; color: var(--accent-blue);">Skills: <?= e($act['skills_learned']) ?></div>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Tools: <?= e($act['tools_used']) ?></div>
                                </td>
                                <td style="max-width: 220px; font-size: 12.5px;">
                                    <div><strong>Challenge:</strong> <?= e($act['challenges'] ?? 'None') ?></div>
                                    <div style="color: var(--text-muted); margin-top: 2px;"><strong>Lesson:</strong> <?= e($act['lessons_learned'] ?? 'None') ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($act['status']) ?>">
                                        <?= e($act['status']) ?>
                                    </span>
                                </td>
                                <td style="max-width: 200px; font-size: 12.5px;">
                                    <?php if (!empty($act['supervisor_comment'])): ?>
                                        <div style="background: #f0fdf4; border-left: 3px solid var(--success); padding: 6px 10px; border-radius: var(--radius-sm); color: #166534;">
                                            <?= e($act['supervisor_comment']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Pending supervisor review</span>
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

<!-- Modal: Add Daily Activity -->
<div class="modal-backdrop" id="addActivityModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Log Daily Industrial Activity</h3>
            <button type="button" onclick="closeModal('addActivityModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/activities/create.php" method="POST">
            <input type="hidden" name="placement_id" value="<?= e($placementId) ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label for="activity_date">Activity Date</label>
                    <input type="date" id="activity_date" name="activity_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Detailed Description of Work Done</label>
                    <textarea id="description" name="description" rows="3" required placeholder="Describe technical assignments, modules worked on, or maintenance performed..."></textarea>
                </div>

                <div class="form-group">
                    <label for="skills_learned">Technical Skills Acquired</label>
                    <input type="text" id="skills_learned" name="skills_learned" required placeholder="e.g. Prepared statements, relational normalisation, responsive CSS">
                </div>

                <div class="form-group">
                    <label for="tools_used">Tools & Equipment Used</label>
                    <input type="text" id="tools_used" name="tools_used" required placeholder="e.g. PHP 8, MySQL, VS Code, Git, Chrome DevTools">
                </div>

                <div class="form-group">
                    <label for="challenges">Technical Challenges Faced</label>
                    <input type="text" id="challenges" name="challenges" placeholder="e.g. Handling foreign key constraint cascading">
                </div>

                <div class="form-group">
                    <label for="lessons_learned">Key Lessons Learned</label>
                    <input type="text" id="lessons_learned" name="lessons_learned" placeholder="e.g. Always structure SQL queries with strict parameters">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addActivityModal')">Cancel</button>
                <button type="submit" name="submit_action" value="draft" class="btn btn-secondary">Save as Draft</button>
                <button type="submit" name="submit_action" value="Submit" class="btn btn-primary">Submit for Review</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
