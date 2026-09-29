<?php
/**
 * SIWES360 - Login Processing Handler
 * Uses password_verify() and session security
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/login.php');
}

verify_csrf();

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    setFlash('danger', 'Please provide both email address and password.');
    redirect('/siwes360/login.php');
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    logAudit(null, 'FAILED_LOGIN', "Failed login attempt for email: $email");
    setFlash('danger', 'Invalid email or password.');
    redirect('/siwes360/login.php');
}

if ($user['status'] !== 'active') {
    logAudit($user['user_id'], 'SUSPENDED_LOGIN_ATTEMPT', 'Suspended account attempted login.');
    $message = $user['role'] === 'admin' && $user['status'] === 'inactive'
        ? 'Your administrator account is awaiting activation. Please contact an existing administrator.'
        : 'Your account is not active. Please contact the administrator.';
    setFlash('danger', $message);
    redirect('/siwes360/login.php');
}

// Session regeneration to prevent session fixation
session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// Update last login
$updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$updateStmt->execute([$user['user_id']]);

logAudit($user['user_id'], 'USER_LOGIN', "User logged in successfully with role: {$user['role']}");

// Role redirect
switch ($user['role']) {
    case 'admin':
        redirect('/siwes360/admin/dashboard.php');
        break;
    case 'student':
        redirect('/siwes360/student/dashboard.php');
        break;
    case 'supervisor':
        redirect('/siwes360/supervisor/dashboard.php');
        break;
    case 'coordinator':
        redirect('/siwes360/coordinator/dashboard.php');
        break;
    default:
        redirect('/siwes360/login.php');
        break;
}
