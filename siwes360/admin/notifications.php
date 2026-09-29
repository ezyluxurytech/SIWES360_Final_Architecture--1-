<?php
/**
 * SIWES360 - Admin Broadcast & Notifications
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Broadcast Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'broadcast') {
    $targetRole = $_POST['target_role'] ?? 'all';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($title) && !empty($message)) {
        if ($targetRole === 'all') {
            $users = $pdo->query("SELECT user_id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE role = ? AND status = 'active'");
            $stmt->execute([$targetRole]);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        foreach ($users as $uid) {
            createNotification($uid, $title, $message, "info");
        }

        logAudit($userId, 'BROADCAST_SENT', "Broadcast sent to {$targetRole}: {$title}");
        setFlash('success', "Broadcast announcement dispatched to " . count($users) . " users.");
    }
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$pageTitle = "System Notifications";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Notifications & System Broadcasts</h1>
        <p>Send institutional alerts to students, coordinators, and corporate mentors.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('broadcastModal')">
            📢 Send Broadcast Alert
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">My Administrative Alerts</div>
        <form action="/siwes360/api/notifications/mark_read.php" method="POST">
            <button type="submit" class="btn btn-secondary btn-sm">Mark All Read</button>
        </form>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notifications)): ?>
            <div style="padding: 36px; text-align: center; color: var(--text-muted);">
                No notifications received.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $notif): ?>
                    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; <?= !$notif['is_read'] ? 'background: #f0fdf4;' : '' ?>">
                        <div style="display: flex; gap: 14px; align-items: flex-start;">
                            <span style="font-size: 20px;">
                                <?= $notif['type'] === 'success' ? '✅' : '📢' ?>
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
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Broadcast Announcement -->
<div class="modal-backdrop" id="broadcastModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Dispatch System Broadcast</h3>
            <button type="button" onclick="closeModal('broadcastModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/admin/notifications.php" method="POST">
            <input type="hidden" name="action" value="broadcast">
            <div class="modal-body">
                <div class="form-group">
                    <label for="b_target">Target Audience</label>
                    <select name="target_role" id="b_target" required>
                        <option value="all">All Registered Users</option>
                        <option value="student">Students Only</option>
                        <option value="supervisor">Supervisors Only</option>
                        <option value="coordinator">Coordinators Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="b_title">Alert Subject / Title</label>
                    <input type="text" id="b_title" name="title" required placeholder="e.g. Mid-Term SIWES Assessment Submission Deadline">
                </div>
                <div class="form-group">
                    <label for="b_msg">Broadcast Message</label>
                    <textarea id="b_msg" name="message" rows="4" required placeholder="Important guidelines or university deadlines..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('broadcastModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Dispatch Broadcast</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
