<?php
/**
 * SIWES360 - Authentication Login Portal
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

// If already logged in, route to authorized dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    redirect('/siwes360/' . $_SESSION['role'] . '/dashboard.php');
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>Login - SIWES360 Industrial Training Management</title>
    <link rel="stylesheet" href="/siwes360/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-badge">
                    <span class="logo-icon">🎓</span>
                    <span class="logo-title">SIWES<span>360</span></span>
                </div>
                <h2>Industrial Training Portal</h2>
                <p>Sign in to access your digital industrial logbook, placements, and monitoring dashboard.</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form action="/siwes360/auth/login_process.php" method="POST" class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-group">
                    <label for="email">Institutional Email</label>
                    <input type="email" id="email" name="email" required placeholder="e.g. ezekiel@student.test" autocomplete="email" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                        <button type="button" class="btn-toggle-pwd" id="togglePwd" aria-label="Toggle password visibility">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="auth-hints">
                <form action="/siwes360/register.php" method="GET" class="auth-form">
                    <div class="form-group">
                        <label for="signup_role">Create an account as</label>
                        <select name="role" id="signup_role" required>
                            <option value="student">Student</option>
                            <option value="admin">System Administrator</option>
                            <option value="supervisor">Industry Supervisor</option>
                            <option value="coordinator">School Coordinator</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-block">Continue to registration</button>
                </form>
            </div>
        </div>
    </div>

    <script src="/siwes360/assets/js/app.js"></script>
</body>
</html>
