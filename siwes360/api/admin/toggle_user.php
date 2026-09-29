<?php
/**
 * SIWES360 - Admin Toggle User Status / Delete
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/admin/users.php');
}

verify_csrf();

$targetUserId = (int)($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? ''; // 'activate', 'deactivate', 'delete'

if ($targetUserId <= 0 || $targetUserId === (int)$_SESSION['user_id']) {
    setFlash('danger', 'Invalid user operation.');
    redirect('/siwes360/admin/users.php');
}

$pdo = getDBConnection();

try {
    if ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        logAudit($_SESSION['user_id'], 'USER_ACTIVATED', "Activated user ID {$targetUserId}");
        setFlash('success', 'User account activated successfully.');
    } elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        logAudit($_SESSION['user_id'], 'USER_SUSPENDED', "Suspended user ID {$targetUserId}");
        setFlash('warning', 'User account suspended.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        logAudit($_SESSION['user_id'], 'USER_DELETED', "Permanently deleted user ID {$targetUserId}");
        setFlash('info', 'User account deleted.');
    }
} catch (Exception $e) {
    error_log("User toggle error: " . $e->getMessage());
    setFlash('danger', 'Unable to perform action. Check integrity constraints.');
}

redirect('/siwes360/admin/users.php');
