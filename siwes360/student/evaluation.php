<?php
/**
 * SIWES360 - Student Evaluation Results
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Fetch placement and evaluation
$stmt = $pdo->prepare("
    SELECT ev.*, u.full_name AS evaluator_name, sup.position AS evaluator_position, o.name AS organization_name
    FROM evaluations ev
    JOIN placements p ON ev.placement_id = p.placement_id
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON ev.evaluator_id = u.user_id
    LEFT JOIN supervisors sup ON u.user_id = sup.user_id
    LEFT JOIN organizations o ON p.organization_id = o.organization_id
    WHERE s.user_id = ?
    ORDER BY ev.created_at DESC
");
$stmt->execute([$userId]);
$evaluations = $stmt->fetchAll();

$pageTitle = "Performance Evaluation";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Industrial Performance Evaluation</h1>
        <p>Official technical competence assessments submitted by your industry supervisors and coordinators.</p>
    </div>
</div>

<?php if (empty($evaluations)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 48px 24px;">
            <span style="font-size: 48px;">📋</span>
            <h3 style="margin-top: 12px; color: var(--primary-navy);">No Evaluations Submitted Yet</h3>
            <p style="color: var(--text-muted); max-width: 450px; margin: 8px auto 0;">
                Your supervisor will assess your performance across 6 core criteria (technical competency, punctuality, communication, teamwork, problem solving, and professionalism).
            </p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($evaluations as $eval): ?>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <div>
                    <div class="card-title">Official Assessment by <?= e($eval['evaluator_name']) ?></div>
                    <span style="font-size: 12.5px; color: var(--text-muted);"><?= e($eval['evaluator_position'] ?? 'Supervisor') ?> · <?= e($eval['organization_name']) ?> · Submitted <?= date('d M Y', strtotime($eval['created_at'])) ?></span>
                </div>
                <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: var(--radius-md); padding: 8px 16px; text-align: center;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #1d4ed8;">Overall Grade</div>
                    <div style="font-size: 24px; font-weight: 800; color: #1e3a8a;"><?= number_format($eval['overall_score'], 1) ?>%</div>
                </div>
            </div>

            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px;">
                    <?php 
                    $metrics = [
                        ['Technical Skills', $eval['technical_skills']],
                        ['Punctuality & Attendance', $eval['punctuality']],
                        ['Communication & Reporting', $eval['communication']],
                        ['Teamwork & Collaboration', $eval['teamwork']],
                        ['Problem Solving & Adaptability', $eval['problem_solving']],
                        ['Professionalism & Conduct', $eval['professionalism']],
                    ];
                    foreach ($metrics as [$label, $score]): 
                    ?>
                        <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13.5px;">
                                <span style="font-weight: 600;"><?= e($label) ?></span>
                                <span style="font-weight: 700; color: var(--accent-blue);"><?= e($score) ?>/100</span>
                            </div>
                            <div style="height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                <div style="width: <?= min(100, max(0, (int)$score)) ?>%; height: 100%; background: var(--accent-blue); border-radius: 999px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="background: #faf5ff; border-left: 4px solid #9333ea; padding: 14px 18px; border-radius: var(--radius-sm);">
                    <div style="font-size: 12.5px; font-weight: 700; color: #6b21a8; text-transform: uppercase; margin-bottom: 4px;">Supervisor's Qualitative Remarks</div>
                    <p style="font-size: 14px; color: #3b0764;"><?= nl2br(e($eval['comments'] ?? 'No written remarks provided.')) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
