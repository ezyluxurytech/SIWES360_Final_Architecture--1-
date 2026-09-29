<?php
/**
 * SIWES360 - Authentication Guard
 * Protects pages from unauthenticated access.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    setFlash('warning', 'Please log in to access the system.');
    redirect('/siwes360/login.php');
}

// Check user status
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT status FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userStatus = $stmt->fetchColumn();

if ($userStatus !== 'active') {
    session_destroy();
    session_start();
    setFlash('danger', 'Your account has been suspended or deactivated. Contact the Administrator.');
    redirect('/siwes360/login.php');
}
