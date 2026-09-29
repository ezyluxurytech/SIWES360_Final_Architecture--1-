<?php
/**
 * SIWES360 - Mark Notification Read Endpoint
 */
require_once __DIR__ . '/../../auth/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $notifId = (int)($_POST['notification_id'] ?? 0);
    $userId = (int)$_SESSION['user_id'];

    $pdo = getDBConnection();
    if ($notifId > 0) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->execute([$notifId, $userId]);
    } else {
        // Mark all as read
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/siwes360/index.php';
header("Location: " . $redirectUrl);
exit;
