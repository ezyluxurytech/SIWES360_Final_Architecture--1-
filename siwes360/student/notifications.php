<?php
/**
 * SIWES360 - User Notifications View
 */
require_once __DIR__ . '/../auth/auth_check.php';

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$pageTitle = "My Notifications";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Notifications & Alerts</h1>
        <p>System announcements, supervisor review approvals, and attendance reminders.</p>
    </div>
    <div class="page-actions">
        <form action="/siwes360/api/notifications/mark_read.php" method="POST">
            <button type="submit" class="btn btn-secondary btn-sm">Mark All as Read</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notifications)): ?>
            <div style="padding: 36px; text-align: center; color: var(--text-muted);">
                No notifications found.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $notif): ?>
                    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; <?= !$notif['is_read'] ? 'background: #f0fdf4;' : '' ?>">
                        <div style="display: flex; gap: 14px; align-items: flex-start;">
                            <span style="font-size: 20px;">
                                <?= $notif['type'] === 'success' ? '✅' : ($notif['type'] === 'error' ? '❌' : ($notif['type'] === 'warning' ? '⚠️' : 'ℹ️')) ?>
                            </span>
                            <div>
                                <h4 style="font-size: 14.5px; font-weight: 700; color: var(--primary-navy); margin-bottom: 2px;">
                                    <?= e($notif['title']) ?>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="badge badge-success" style="font-size: 10px; margin-left: 6px;">New</span>
                                    <?php endif; ?>
                                </h4>
                                <p style="font-size: 13.5px; color: var(--text-main); margin-bottom: 4px;"><?= e($notif['message']) ?></p>
                                <span style="font-size: 11.5px; color: #94a3b8;"><?= e($notif['created_at']) ?></span>
                            </div>
                        </div>
                        <?php if (!$notif['is_read']): ?>
                            <form action="/siwes360/api/notifications/mark_read.php" method="POST">
                                <input type="hidden" name="notification_id" value="<?= e($notif['notification_id']) ?>">
                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 11.5px;">Mark Read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
