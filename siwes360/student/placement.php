<?php
/**
 * SIWES360 - Student Placement Details
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT p.*, o.name AS organization_name, o.address AS org_address, o.city AS org_city, o.state AS org_state,
           o.phone AS org_phone, o.email AS org_email, o.industry AS org_industry, o.description AS org_desc,
           sup_u.full_name AS supervisor_name, sup.position AS supervisor_position, sup.phone AS supervisor_phone, sup_u.email AS supervisor_email,
           ses.name AS session_name, ses.start_date AS session_start, ses.end_date AS session_end
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN organizations o ON p.organization_id = o.organization_id
    JOIN supervisors sup ON p.supervisor_id = sup.supervisor_id
    JOIN users sup_u ON sup.user_id = sup_u.user_id
    JOIN it_sessions ses ON p.session_id = ses.session_id
    WHERE s.user_id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$placement = $stmt->fetch();

$pageTitle = "My Industrial Placement";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Industrial Placement Record</h1>
        <p>Verified university SIWES internship assignment and organization supervisor linkage.</p>
    </div>
</div>

<?php if (!$placement): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 48px;">
            <p style="color: var(--text-muted);">No active placement record assigned. Contact your school coordinator.</p>
        </div>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Organization Profile -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Host Organization Information</div>
                <span class="badge badge-success"><?= e($placement['status']) ?></span>
            </div>
            <div class="card-body">
                <h3 style="font-size: 18px; color: var(--primary-navy); margin-bottom: 6px;"><?= e($placement['organization_name']) ?></h3>
                <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 16px;"><?= e($placement['org_industry']) ?></p>
                <p style="font-size: 13.5px; margin-bottom: 16px;"><?= e($placement['org_desc']) ?></p>

                <table class="data-table" style="font-size: 13px;">
                    <tr>
                        <td style="font-weight: 600; width: 120px;">Address</td>
                        <td><?= e($placement['org_address']) ?>, <?= e($placement['org_city']) ?>, <?= e($placement['org_state']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Office Phone</td>
                        <td><?= e($placement['org_phone']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Contact Email</td>
                        <td><?= e($placement['org_email']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Supervisor & Placement Terms -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Assigned Industry Supervisor</div>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #e0e7ff; color: var(--accent-blue); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px;">
                        <?= strtoupper(substr($placement['supervisor_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h4 style="font-size: 16px; color: var(--primary-navy);"><?= e($placement['supervisor_name']) ?></h4>
                        <span style="font-size: 13px; color: var(--text-muted);"><?= e($placement['supervisor_position']) ?></span>
                    </div>
                </div>

                <table class="data-table" style="font-size: 13px;">
                    <tr>
                        <td style="font-weight: 600; width: 140px;">Official Email</td>
                        <td><?= e($placement['supervisor_email']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Supervisor Phone</td>
                        <td><?= e($placement['supervisor_phone']) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Assigned Position</td>
                        <td><strong><?= e($placement['position']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Duration</td>
                        <td><?= date('d M Y', strtotime($placement['start_date'])) ?> – <?= date('d M Y', strtotime($placement['end_date'])) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">SIWES Session</td>
                        <td><?= e($placement['session_name']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
