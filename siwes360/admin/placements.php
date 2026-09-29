<?php
/**
 * SIWES360 - Admin Placements Management
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

$placements = $pdo->query("
    SELECT p.*, s.matric_no, u.full_name AS student_name,
           o.name AS organization_name, o.city,
           sup_u.full_name AS supervisor_name,
           ses.name AS session_name
    FROM placements p
    JOIN students s ON p.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN organizations o ON p.organization_id = o.organization_id
    JOIN supervisors sup ON p.supervisor_id = sup.supervisor_id
    JOIN users sup_u ON sup.user_id = sup_u.user_id
    JOIN it_sessions ses ON p.session_id = ses.session_id
    ORDER BY p.created_at DESC
")->fetchAll();

// Dropdown options for assignment modal
$studentsList = $pdo->query("SELECT s.student_id, s.matric_no, u.full_name FROM students s JOIN users u ON s.user_id = u.user_id ORDER BY u.full_name ASC")->fetchAll();
$orgsList = $pdo->query("SELECT organization_id, name FROM organizations ORDER BY name ASC")->fetchAll();
$supervisorsList = $pdo->query("SELECT sup.supervisor_id, u.full_name, o.name AS org_name FROM supervisors sup JOIN users u ON sup.user_id = u.user_id JOIN organizations o ON sup.organization_id = o.organization_id ORDER BY u.full_name ASC")->fetchAll();
$sessionsList = $pdo->query("SELECT session_id, name FROM it_sessions ORDER BY start_date DESC")->fetchAll();

$pageTitle = "Student Placements";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>All Industrial Placements</h1>
        <p>Assign trainees to verified host organizations and industry supervisors.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addPlacementModal')">
            📍 Assign New Placement
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Placement Records (<?= count($placements) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search placements..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Host Company</th>
                        <th>Role Assigned</th>
                        <th>Industry Mentor</th>
                        <th>Session</th>
                        <th>Dates</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($placements as $pl): ?>
                        <tr>
                            <td>
                                <strong><?= e($pl['student_name']) ?></strong>
                                <div style="font-size: 11px; color: #94a3b8;"><?= e($pl['matric_no']) ?></div>
                            </td>
                            <td><?= e($pl['organization_name']) ?></td>
                            <td><strong><?= e($pl['position']) ?></strong></td>
                            <td><?= e($pl['supervisor_name']) ?></td>
                            <td><?= e($pl['session_name']) ?></td>
                            <td style="font-size: 12px; white-space: nowrap;">
                                <?= date('d M Y', strtotime($pl['start_date'])) ?> – <?= date('d M Y', strtotime($pl['end_date'])) ?>
                            </td>
                            <td><span class="badge badge-<?= strtolower($pl['status']) ?>"><?= e($pl['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Assign Placement -->
<div class="modal-backdrop" id="addPlacementModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Assign Student Placement Linkage</h3>
            <button type="button" onclick="closeModal('addPlacementModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/admin/create_placement.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="pl_student">Select Student</label>
                    <select name="student_id" id="pl_student" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($studentsList as $st): ?>
                            <option value="<?= e($st['student_id']) ?>"><?= e($st['full_name']) ?> (<?= e($st['matric_no']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pl_org">Host Organization</label>
                    <select name="organization_id" id="pl_org" required>
                        <option value="">-- Choose Organization --</option>
                        <?php foreach ($orgsList as $o): ?>
                            <option value="<?= e($o['organization_id']) ?>"><?= e($o['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pl_sup">Assigned Supervisor / Mentor</label>
                    <select name="supervisor_id" id="pl_sup" required>
                        <option value="">-- Choose Supervisor --</option>
                        <?php foreach ($supervisorsList as $sp): ?>
                            <option value="<?= e($sp['supervisor_id']) ?>"><?= e($sp['full_name']) ?> (<?= e($sp['org_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="pl_session">Training Session</label>
                        <select name="session_id" id="pl_session" required>
                            <?php foreach ($sessionsList as $ses): ?>
                                <option value="<?= e($ses['session_id']) ?>"><?= e($ses['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pl_position">Internship Position</label>
                        <input type="text" id="pl_position" name="position" value="Junior Software Trainee" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="pl_start">Placement Start Date</label>
                        <input type="date" id="pl_start" name="start_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="pl_end">Placement End Date</label>
                        <input type="date" id="pl_end" name="end_date" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPlacementModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Establish Placement</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
