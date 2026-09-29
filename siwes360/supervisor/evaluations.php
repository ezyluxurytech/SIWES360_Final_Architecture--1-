<?php
/**
 * SIWES360 - Supervisor Evaluations Page
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('supervisor');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Get supervisor id
$supStmt = $pdo->prepare("SELECT supervisor_id FROM supervisors WHERE user_id = ?");
$supStmt->execute([$userId]);
$supervisorId = $supStmt->fetchColumn();

// Get eligible placements under this supervisor
$placementsStmt = $pdo->prepare("
    SELECT p.placement_id, p.position, s.student_id, u.full_name AS student_name, s.matric_no
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE p.supervisor_id = ?
");
$placementsStmt->execute([$supervisorId]);
$placements = $placementsStmt->fetchAll();

// Get submitted evaluations
$evalsStmt = $pdo->prepare("
    SELECT ev.*, u.full_name AS student_name, s.matric_no, p.position
    FROM evaluations ev
    JOIN placements p ON ev.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    WHERE p.supervisor_id = ?
    ORDER BY ev.created_at DESC
");
$evalsStmt->execute([$supervisorId]);
$evaluations = $evalsStmt->fetchAll();

$pageTitle = "Intern Evaluations";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Industrial Student Evaluations</h1>
        <p>Assess trainee technical competency, punctuality, and professionalism based on SIWES guidelines.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('evaluateModal')">
            ⭐ New Student Assessment
        </button>
    </div>
</div>

<!-- Evaluations Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Completed Evaluations (<?= count($evaluations) ?> records)</div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Position</th>
                        <th>Technical</th>
                        <th>Punctuality</th>
                        <th>Communication</th>
                        <th>Teamwork</th>
                        <th>Problem Solving</th>
                        <th>Conduct</th>
                        <th>Overall Score</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($evaluations)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 24px;">No evaluations recorded yet. Click "New Student Assessment" above.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($evaluations as $ev): ?>
                            <tr>
                                <td>
                                    <strong><?= e($ev['student_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($ev['matric_no']) ?></div>
                                </td>
                                <td><?= e($ev['position']) ?></td>
                                <td><?= e($ev['technical_skills']) ?>%</td>
                                <td><?= e($ev['punctuality']) ?>%</td>
                                <td><?= e($ev['communication']) ?>%</td>
                                <td><?= e($ev['teamwork']) ?>%</td>
                                <td><?= e($ev['problem_solving']) ?>%</td>
                                <td><?= e($ev['professionalism']) ?>%</td>
                                <td>
                                    <span class="badge badge-success" style="font-size: 13px; font-weight: 700;">
                                        <?= number_format($ev['overall_score'], 1) ?>%
                                    </span>
                                </td>
                                <td style="font-size: 12px; color: #94a3b8;"><?= date('d M Y', strtotime($ev['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Student Evaluation -->
<div class="modal-backdrop" id="evaluateModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Industrial Performance Evaluation</h3>
            <button type="button" onclick="closeModal('evaluateModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/evaluations/submit.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="placement_id">Select Intern to Evaluate</label>
                    <select name="placement_id" id="placement_id" required>
                        <option value="">-- Choose Assigned Student --</option>
                        <?php foreach ($placements as $p): ?>
                            <option value="<?= e($p['placement_id']) ?>"><?= e($p['student_name']) ?> (<?= e($p['matric_no']) ?>) - <?= e($p['position']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="technical_skills">1. Technical Skills (0-100)</label>
                        <input type="number" id="technical_skills" name="technical_skills" min="0" max="100" value="85" required>
                    </div>
                    <div class="form-group">
                        <label for="punctuality">2. Punctuality (0-100)</label>
                        <input type="number" id="punctuality" name="punctuality" min="0" max="100" value="90" required>
                    </div>
                    <div class="form-group">
                        <label for="communication">3. Communication (0-100)</label>
                        <input type="number" id="communication" name="communication" min="0" max="100" value="88" required>
                    </div>
                    <div class="form-group">
                        <label for="teamwork">4. Teamwork (0-100)</label>
                        <input type="number" id="teamwork" name="teamwork" min="0" max="100" value="92" required>
                    </div>
                    <div class="form-group">
                        <label for="problem_solving">5. Problem Solving (0-100)</label>
                        <input type="number" id="problem_solving" name="problem_solving" min="0" max="100" value="87" required>
                    </div>
                    <div class="form-group">
                        <label for="professionalism">6. Professionalism (0-100)</label>
                        <input type="number" id="professionalism" name="professionalism" min="0" max="100" value="95" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="eval_comments">Comprehensive Qualitative Remarks</label>
                    <textarea name="comments" id="eval_comments" rows="3" required placeholder="Detail technical adaptability, attitude towards work, and general recommendations..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('evaluateModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Final Evaluation</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
