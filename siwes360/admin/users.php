<?php
/**
 * SIWES360 - Admin User Management
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

$filterRole = $_GET['role'] ?? '';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.*, 
           s.matric_no, s.level,
           sup.position AS supervisor_position,
           c.phone AS coordinator_phone
    FROM users u
    LEFT JOIN students s ON u.user_id = s.user_id
    LEFT JOIN supervisors sup ON u.user_id = sup.user_id
    LEFT JOIN coordinators c ON u.user_id = c.user_id
    WHERE 1=1
";
$params = [];
if (!empty($filterRole)) {
    $sql .= " AND u.role = ?";
    $params[] = $filterRole;
}
if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR s.matric_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get departments & organizations for modal dropdowns
$depts = $pdo->query("SELECT department_id, name FROM departments ORDER BY name ASC")->fetchAll();
$orgs = $pdo->query("SELECT organization_id, name FROM organizations ORDER BY name ASC")->fetchAll();

$pageTitle = "User Management";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>User Directory & Role Permissions</h1>
        <p>Provision credentials, assign system privileges, and control access across organizational tiers.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('createUserModal')">
            ➕ Register New User
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">All System Accounts (<?= count($users) ?>)</div>
        <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
            <select name="role" style="padding: 6px 10px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                <option value="">-- All Roles --</option>
                <option value="student" <?= $filterRole === 'student' ? 'selected' : '' ?>>Students</option>
                <option value="supervisor" <?= $filterRole === 'supervisor' ? 'selected' : '' ?>>Supervisors</option>
                <option value="coordinator" <?= $filterRole === 'coordinator' ? 'selected' : '' ?>>Coordinators</option>
                <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Administrators</option>
            </select>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name, email, matric..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if ($filterRole || $search): ?>
                <a href="/siwes360/admin/users.php" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Role Identifiers</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No users found matching query.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= e($u['full_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($u['email']) ?></div>
                                </td>
                                <td><span class="badge badge-secondary"><?= ucfirst(e($u['role'])) ?></span></td>
                                <td style="font-size: 12.5px;">
                                    <?php if ($u['role'] === 'student'): ?>
                                        <code><?= e($u['matric_no']) ?></code> (<?= e($u['level']) ?>L)
                                    <?php elseif ($u['role'] === 'supervisor'): ?>
                                        <?= e($u['supervisor_position'] ?? 'Supervisor') ?>
                                    <?php elseif ($u['role'] === 'coordinator'): ?>
                                        <?= e($u['coordinator_phone'] ?? 'Coordinator') ?>
                                    <?php else: ?>
                                        Central Admin
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>">
                                        <?= e($u['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; color: #94a3b8;"><?= e($u['last_login'] ?? 'Never') ?></td>
                                <td>
                                    <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
                                        <div style="display: flex; gap: 4px;">
                                            <form action="/siwes360/api/admin/toggle_user.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= e($u['user_id']) ?>">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <button type="submit" name="action" value="deactivate" class="btn btn-secondary btn-sm" style="font-size: 11px; color: #b45309;" title="Suspend Account">Suspend</button>
                                                <?php else: ?>
                                                    <button type="submit" name="action" value="activate" class="btn btn-secondary btn-sm" style="font-size: 11px; color: #15803d;" title="Activate Account">Activate</button>
                                                <?php endif; ?>
                                            </form>
                                            <form action="/siwes360/api/admin/toggle_user.php" method="POST" style="display: inline;" onsubmit="return confirm('Permanently delete this user?');">
                                                <input type="hidden" name="user_id" value="<?= e($u['user_id']) ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-secondary btn-sm" style="font-size: 11px; color: #b91c1c;" title="Delete">🗑️</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size: 11.5px; color: var(--accent-blue);">Current User</span>
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

<!-- Modal: Create New User -->
<div class="modal-backdrop" id="createUserModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Provision System User Account</h3>
            <button type="button" onclick="closeModal('createUserModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/admin/create_user.php" method="POST">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="u_fullname">Full Name</label>
                        <input type="text" id="u_fullname" name="full_name" required placeholder="e.g. Chukwuebuka Obi">
                    </div>
                    <div class="form-group">
                        <label for="u_email">Email Address</label>
                        <input type="email" id="u_email" name="email" required placeholder="name@institution.edu.ng">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="u_role">Account Role</label>
                        <select name="role" id="u_role" onchange="toggleRoleFields(this.value)" required>
                            <option value="student">Student (Trainee)</option>
                            <option value="supervisor">Industry Supervisor</option>
                            <option value="coordinator">School Coordinator</option>
                            <option value="admin">System Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="u_password">Default Password</label>
                        <input type="password" id="u_password" name="password" value="Password123!" required>
                    </div>
                </div>

                <!-- Dynamic Student Fields -->
                <div id="studentFields" style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label for="st_matric">Matriculation Number</label>
                            <input type="text" id="st_matric" name="matric_no" placeholder="SCI/2022/1044">
                        </div>
                        <div class="form-group">
                            <label for="st_level">Academic Level</label>
                            <select name="level" id="st_level">
                                <option value="300">300 Level</option>
                                <option value="400" selected>400 Level</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="st_dept">Department</label>
                        <select name="department_id" id="st_dept">
                            <?php foreach ($depts as $d): ?>
                                <option value="<?= e($d['department_id']) ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Dynamic Supervisor Fields -->
                <div id="supervisorFields" style="display: none; background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 12px;">
                    <div class="form-group">
                        <label for="sup_org">Host Organization</label>
                        <select name="organization_id" id="sup_org">
                            <?php foreach ($orgs as $o): ?>
                                <option value="<?= e($o['organization_id']) ?>"><?= e($o['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sup_pos">Corporate Position / Title</label>
                        <input type="text" id="sup_pos" name="position" placeholder="Lead Software Architect">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRoleFields(role) {
    document.getElementById('studentFields').style.display = (role === 'student') ? 'block' : 'none';
    document.getElementById('supervisorFields').style.display = (role === 'supervisor') ? 'block' : 'none';
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
