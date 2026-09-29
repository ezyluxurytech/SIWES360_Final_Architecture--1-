<?php
/**
 * SIWES360 - Supervisor Weekly Report Management & Approvals
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('supervisor');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get supervisor id
$supStmt = $pdo->prepare("SELECT supervisor_id FROM supervisors WHERE user_id = ?");
$supStmt->execute([$userId]);
$supervisorId = $supStmt->fetchColumn();

// Status filter
$filterStatus = $_GET['status'] ?? '';

$sql = "
    SELECT r.*, u.full_name AS student_name, s.matric_no, rev.decision, rev.comment AS supervisor_comment, rev.reviewed_at
    FROM weekly_reports r
    JOIN placements p ON r.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN report_reviews rev ON r.report_id = rev.report_id
    WHERE p.supervisor_id = ?
";
$params = [$supervisorId];
if (!empty($filterStatus)) {
    $sql .= " AND r.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY r.week_number DESC, r.submitted_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$pageTitle = "Weekly Reports Review";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Student Weekly Reports Review</h1>
        <p>Assess weekly internship milestone submissions, sign off completed weeks, or request revisions.</p>
    </div>
    <div class="page-actions" style="display: flex; gap: 8px;">
        <a href="/siwes360/supervisor/reports.php" class="btn btn-secondary btn-sm <?= empty($filterStatus) ? 'btn-primary' : '' ?>">All Reports</a>
        <a href="/siwes360/supervisor/reports.php?status=Submitted" class="btn btn-secondary btn-sm <?= $filterStatus === 'submitted' ? 'btn-primary' : '' ?>">Pending Review</a>
        <a href="/siwes360/supervisor/reports.php?status=Approved" class="btn btn-secondary btn-sm <?= $filterStatus === 'approved' ? 'btn-primary' : '' ?>">Approved</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Reports Management Table (<?= count($reports) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search by student or week..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Week</th>
                        <th>Duration</th>
                        <th>Activities Summary</th>
                        <th>Competencies</th>
                        <th>Status</th>
                        <th>Evaluation Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No weekly reports found matching this criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $rep): ?>
                            <tr>
                                <td>
                                    <strong><?= e($rep['student_name']) ?></strong>
                                    <div style="font-size: 11.5px; color: #94a3b8;"><?= e($rep['matric_no']) ?></div>
                                </td>
                                <td><strong>Week <?= e($rep['week_number']) ?></strong></td>
                                <td style="font-size: 12px; white-space: nowrap;">
                                    <?= date('M d', strtotime($rep['week_start'])) ?> - <?= date('M d, Y', strtotime($rep['week_end'])) ?>
                                </td>
                                <td style="max-width: 280px; font-size: 13px;"><?= nl2br(e($rep['activities'])) ?></td>
                                <td style="max-width: 220px; font-size: 12.5px;">
                                    <div><strong>Skills:</strong> <?= e($rep['skills_acquired']) ?></div>
                                    <div style="color: var(--text-muted); margin-top: 4px;"><strong>Solutions:</strong> <?= e($rep['solutions'] ?? 'None') ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '', $rep['status'])) ?>">
                                        <?= e($rep['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($rep['status'] === 'submitted' || $rep['status'] === 'under_review'): ?>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="openReviewModal(<?= e($rep['report_id']) ?>, '<?= e($rep['student_name']) ?>', <?= e($rep['week_number']) ?>)">
                                            ✍️ Evaluate Report
                                        </button>
                                    <?php else: ?>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <strong><?= e($rep['decision']) ?></strong>: <?= e($rep['supervisor_comment']) ?>
                                        </div>
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

<!-- Modal: Evaluate Report -->
<div class="modal-backdrop" id="reviewReportModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Review & Sign Off Report</h3>
            <button type="button" onclick="closeModal('reviewReportModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/reports/review.php" method="POST">
            <input type="hidden" name="report_id" id="modalReportId">
            <div class="modal-body">
                <p id="modalStudentNotice" style="font-size: 14px; font-weight: 600; color: var(--primary-navy); margin-bottom: 16px;"></p>

                <div class="form-group">
                    <label for="decision">Review Decision</label>
                    <select name="decision" id="decision" required>
                        <option value="approved">✅ Approve Weekly Report</option>
                        <option value="revision_requested">⚠️ Request Revision from Student</option>
                        <option value="rejected">❌ Reject Report</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comment">Supervisor Feedback & Technical Evaluation</label>
                    <textarea name="comment" id="comment" rows="4" required placeholder="Provide feedback on skills demonstrated, quality of report, or instructions for corrections..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('reviewReportModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Official Review</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(reportId, studentName, weekNumber) {
    document.getElementById('modalReportId').value = reportId;
    document.getElementById('modalStudentNotice').textContent = "Reviewing Week " + weekNumber + " report for " + studentName;
    openModal('reviewReportModal');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
