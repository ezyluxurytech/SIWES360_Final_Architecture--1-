<?php
/**
 * SIWES360 - Student Weekly Reports
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch placement
$stmt = $pdo->prepare("SELECT placement_id FROM placements p JOIN students s ON p.student_id = s.student_id WHERE s.user_id = ? AND p.status IN ('active', 'approved') LIMIT 1");
$stmt->execute([$userId]);
$placementId = $stmt->fetchColumn();

$reports = [];
if ($placementId) {
    $repStmt = $pdo->prepare("
        SELECT r.*, rev.decision, rev.comment AS review_comment, rev.reviewed_at, u.full_name AS supervisor_name
        FROM weekly_reports r
        LEFT JOIN report_reviews rev ON r.report_id = rev.report_id
        LEFT JOIN supervisors sup ON rev.supervisor_id = sup.supervisor_id
        LEFT JOIN users u ON sup.user_id = u.user_id
        WHERE r.placement_id = ?
        ORDER BY r.week_number DESC
    ");
    $repStmt->execute([$placementId]);
    $reports = $repStmt->fetchAll();
}

$pageTitle = "Weekly Reports";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Weekly Industrial Reports</h1>
        <p>Compile weekly progress reports, technical milestones, and receive official supervisor sign-offs.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addReportModal')">
            📑 Compose Weekly Report
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Submitted Reports (<?= count($reports) ?> weeks)</div>
        <input type="text" id="tableSearch" placeholder="Search reports..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Week #</th>
                        <th>Duration</th>
                        <th>Summary of Activities</th>
                        <th>Skills & Milestones</th>
                        <th>Status</th>
                        <th>Supervisor Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No weekly reports composed yet. Click "Compose Weekly Report" above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $rep): ?>
                            <tr>
                                <td><strong>Week <?= e($rep['week_number']) ?></strong></td>
                                <td style="white-space: nowrap; font-size: 12.5px;">
                                    <?= date('M d', strtotime($rep['week_start'])) ?> – <?= date('M d, Y', strtotime($rep['week_end'])) ?>
                                </td>
                                <td style="max-width: 280px;"><?= nl2br(e($rep['activities'])) ?></td>
                                <td style="max-width: 220px; font-size: 12.5px;">
                                    <div><strong>Skills:</strong> <?= e($rep['skills_acquired']) ?></div>
                                    <div style="color: var(--text-muted); margin-top: 4px;"><strong>Challenges:</strong> <?= e($rep['challenges'] ?? 'None') ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '', $rep['status'])) ?>">
                                        <?= e($rep['status']) ?>
                                    </span>
                                </td>
                                <td style="max-width: 220px; font-size: 12.5px;">
                                    <?php if (!empty($rep['review_comment'])): ?>
                                        <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 8px 10px; border-radius: var(--radius-sm);">
                                            <strong><?= e($rep['decision']) ?></strong>: <?= e($rep['review_comment']) ?>
                                            <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?= e($rep['reviewed_at']) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Awaiting review</span>
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

<!-- Modal: Compose Weekly Report -->
<div class="modal-backdrop" id="addReportModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Compose Weekly Industrial Report</h3>
            <button type="button" onclick="closeModal('addReportModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/reports/submit.php" method="POST">
            <input type="hidden" name="placement_id" value="<?= e($placementId) ?>">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="week_number">Week Number</label>
                        <input type="number" id="week_number" name="week_number" min="1" max="52" value="<?= count($reports) + 1 ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="week_start">Week Start Date</label>
                        <input type="date" id="week_start" name="week_start" required>
                    </div>
                    <div class="form-group">
                        <label for="week_end">Week End Date</label>
                        <input type="date" id="week_end" name="week_end" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="rep_activities">Comprehensive Summary of Weekly Activities</label>
                    <textarea id="rep_activities" name="activities" rows="3" required placeholder="Detail key technical assignments, architecture reviews, meetings..."></textarea>
                </div>

                <div class="form-group">
                    <label for="skills_acquired">Key Skills & Competencies Acquired</label>
                    <input type="text" id="skills_acquired" name="skills_acquired" required placeholder="e.g. REST API design, MySQL transaction rollback, automated unit testing">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="rep_challenges">Challenges Encountered</label>
                        <textarea id="rep_challenges" name="challenges" rows="2" placeholder="Obstacles or architectural bottlenecks"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="solutions">Solutions Applied</label>
                        <textarea id="solutions" name="solutions" rows="2" placeholder="How challenges were mitigated"></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observations">General Observations & Feedback</label>
                    <input type="text" id="observations" name="observations" placeholder="Industry standards, organizational workflows observed">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addReportModal')">Cancel</button>
                <button type="submit" name="submit_action" value="draft" class="btn btn-secondary">Save Draft</button>
                <button type="submit" name="submit_action" value="Submit" class="btn btn-primary">Submit Weekly Report</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
