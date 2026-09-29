<?php
/**
 * SIWES360 - Canonical logout endpoint
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/functions.php';

$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    logAudit((int)$userId, 'USER_LOGOUT', 'User signed out of SIWES360.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

session_start();
setFlash('success', 'You have been logged out successfully.');
redirect('/siwes360/login.php');
