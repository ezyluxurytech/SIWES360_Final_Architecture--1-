<?php
/**
 * SIWES360 - Admin Departments Management
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

$departments = $pdo->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM students s WHERE s.department_id = d.department_id) AS total_students,
           (SELECT COUNT(*) FROM coordinators c WHERE c.department_id = d.department_id) AS total_coordinators
    FROM departments d
    ORDER BY d.faculty ASC, d.name ASC
")->fetchAll();

$pageTitle = "Academic Departments";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Academic Departments</h1>
        <p>Manage university faculties, academic departments, and institutional SIWES programs.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addDeptModal')">
            🏛️ Add Department
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Configured Academic Departments (<?= count($departments) ?>)</div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department Name</th>
                        <th>Faculty / School</th>
                        <th>Enrolled SIWES Trainees</th>
                        <th>Assigned Coordinators</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><strong><?= e($dept['name']) ?></strong></td>
                            <td><span class="badge badge-secondary"><?= e($dept['faculty']) ?></span></td>
                            <td><strong><?= (int)$dept['total_students'] ?></strong> students</td>
                            <td><?= (int)$dept['total_coordinators'] ?> coordinators</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Department -->
<div class="modal-backdrop" id="addDeptModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Add Academic Department</h3>
            <button type="button" onclick="closeModal('addDeptModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/admin/create_department.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="dept_name">Department Name</label>
                    <input type="text" id="dept_name" name="name" required placeholder="e.g. Electrical & Electronics Engineering">
                </div>
                <div class="form-group">
                    <label for="dept_fac">Faculty / School</label>
                    <input type="text" id="dept_fac" name="faculty" required placeholder="e.g. Faculty of Engineering & Technology">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addDeptModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Department</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
