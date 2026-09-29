<?php
/**
 * SIWES360 - Admin Audit & Security Logs
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

$logs = $pdo->query("
    SELECT al.*, u.full_name, u.email, u.role
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 100
")->fetchAll();

$pageTitle = "System Audit Logs";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>System Audit & Security Logs</h1>
        <p>Immutable forensic record of all user authentication, status toggles, and record creations.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Security Activity Log (Last 100 Events)</div>
        <input type="text" id="tableSearch" placeholder="Filter audit trail..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Event Action</th>
                        <th>User Responsible</th>
                        <th>Role</th>
                        <th>IP Address</th>
                        <th>Event Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td>#<?= e($l['log_id']) ?></td>
                            <td><code><?= e($l['action']) ?></code></td>
                            <td>
                                <strong><?= e($l['full_name'] ?? 'System / Anonymous') ?></strong>
                                <?php if ($l['email']): ?>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($l['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-secondary"><?= ucfirst(e($l['role'] ?? 'Guest')) ?></span></td>
                            <td><code><?= e($l['ip_address'] ?? '127.0.0.1') ?></code></td>
                            <td style="max-width: 320px; font-size: 12.5px;"><?= e($l['description']) ?></td>
                            <td style="white-space: nowrap; font-size: 12px; color: #94a3b8;"><?= date('d M Y, H:i:s', strtotime($l['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
