<?php
/**
 * SIWES360 - Public Account Registration
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirect('/siwes360/' . $_SESSION['role'] . '/dashboard.php');
}

$pdo = getDBConnection();
$departments = $pdo->query("SELECT department_id, name FROM departments WHERE status = 'active' ORDER BY name")->fetchAll();
$organizations = $pdo->query("SELECT organization_id, name FROM organizations WHERE status = 'approved' ORDER BY name")->fetchAll();
$hasAdmin = (bool)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$requestedRole = $_GET['role'] ?? 'student';
if (!in_array($requestedRole, ['student', 'admin', 'supervisor', 'coordinator'], true)) {
    $requestedRole = 'student';
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Create Account - SIWES360</title>
    <link rel="stylesheet" href="/siwes360/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card" style="max-width: 560px;">
            <div class="auth-header">
                <div class="logo-badge">
                    <span class="logo-icon">🎓</span>
                    <span class="logo-title">SIWES<span>360</span></span>
                </div>
                <h2>Create your account</h2>
                <p>Register to access the SIWES360 training portal.</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <form action="/siwes360/auth/register_process.php" method="POST" class="auth-form" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" maxlength="150" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" maxlength="150" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="role">Account type</label>
                    <select name="role" id="role" required>
                        <option value="student" <?= $requestedRole === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="admin" <?= $requestedRole === 'admin' ? 'selected' : '' ?>>System Administrator<?= $hasAdmin ? ' (requires approval)' : '' ?></option>
                        <option value="supervisor" <?= $requestedRole === 'supervisor' ? 'selected' : '' ?>>Industry Supervisor</option>
                        <option value="coordinator" <?= $requestedRole === 'coordinator' ? 'selected' : '' ?>>School Coordinator</option>
                    </select>
                </div>

                <div id="studentFields">
                    <div class="form-group">
                        <label for="matric_no">Matriculation number</label>
                        <input type="text" id="matric_no" name="matric_no" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select id="department_id" name="department_id" required>
                            <option value="">Choose your department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= e((string)$department['department_id']) ?>"><?= e($department['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$departments): ?>
                            <small>No active departments are available. Contact the administrator.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="level">Academic level</label>
                        <select id="level" name="level" required>
                            <option value="">Choose your level</option>
                            <?php foreach (['100', '200', '300', '400', '500', '600'] as $level): ?>
                                <option value="<?= $level ?>"><?= $level ?> Level</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="supervisorFields" hidden>
                    <div class="form-group">
                        <label for="organization_id">Host organization</label>
                        <select id="organization_id" name="organization_id" disabled>
                            <option value="">Choose your organization</option>
                            <?php foreach ($organizations as $organization): ?>
                                <option value="<?= e((string)$organization['organization_id']) ?>"><?= e($organization['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$organizations): ?>
                            <small>No approved organizations are available. Contact the administrator.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="position">Job title</label>
                        <input type="text" id="position" name="position" maxlength="100" disabled>
                    </div>
                </div>

                <div id="coordinatorFields" hidden>
                    <div class="form-group">
                        <label for="coordinator_department_id">Department</label>
                        <select id="coordinator_department_id" name="coordinator_department_id" disabled>
                            <option value="">Choose your department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= e((string)$department['department_id']) ?>"><?= e($department['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$departments): ?>
                            <small>No active departments are available. Contact the administrator.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="contactFields">
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create account</button>
            </form>

            <div class="auth-hints">
                <?php if ($hasAdmin): ?>
                    <p>For security, new administrator accounts require activation by an existing administrator before they can sign in.</p>
                <?php else: ?>
                    <p>The first administrator account is activated automatically. Later administrator accounts must be activated by an existing administrator.</p>
                <?php endif; ?>
                <p>Already registered? <a href="/siwes360/login.php">Sign in</a>.</p>
            </div>
        </div>
    </div>

    <script>
    const roleSelect = document.getElementById('role');
    const studentFields = document.getElementById('studentFields');
    const supervisorFields = document.getElementById('supervisorFields');
    const coordinatorFields = document.getElementById('coordinatorFields');
    const contactFields = document.getElementById('contactFields');

    function updateRoleFields() {
        const role = roleSelect.value;
        studentFields.hidden = role !== 'student';
        supervisorFields.hidden = role !== 'supervisor';
        coordinatorFields.hidden = role !== 'coordinator';
        contactFields.hidden = role === 'admin';

        studentFields.querySelectorAll('input, select').forEach((field) => {
            field.disabled = role !== 'student';
        });
        supervisorFields.querySelectorAll('input, select').forEach((field) => {
            field.disabled = role !== 'supervisor';
        });
        coordinatorFields.querySelectorAll('input, select').forEach((field) => {
            field.disabled = role !== 'coordinator';
        });
        document.getElementById('phone').disabled = role === 'admin';
    }

    roleSelect.addEventListener('change', updateRoleFields);
    updateRoleFields();
    </script>
</body>
</html>