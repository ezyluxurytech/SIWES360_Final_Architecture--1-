<?php
/**
 * SIWES360 - Central System Administrator Dashboard
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

// System-wide metric tallies
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalSupervisors = (int)$pdo->query("SELECT COUNT(*) FROM supervisors")->fetchColumn();
$totalOrgs = (int)$pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
$totalPlacements = (int)$pdo->query("SELECT COUNT(*) FROM placements WHERE status = 'active'")->fetchColumn();
$totalAttendance = (int)$pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$totalReports = (int)$pdo->query("SELECT COUNT(*) FROM weekly_reports")->fetchColumn();
$totalEvaluations = (int)$pdo->query("SELECT COUNT(*) FROM evaluations")->fetchColumn();

// Recent audit logs
$recentLogs = $pdo->query("
    SELECT al.*, u.full_name, u.role
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 6
")->fetchAll();

// Recent user registrations
$recentUsers = $pdo->query("
    SELECT user_id, full_name, email, role, status, created_at, last_login
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = "System Administration";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>SIWES360 System Administration</h1>
        <p>Enterprise command center for multi-tier industrial training governance and audit telemetry.</p>
    </div>
    <div class="page-actions" style="display: flex; gap: 8px;">
        <a href="/siwes360/admin/users.php" class="btn btn-primary">👥 Manage Users</a>
        <a href="/siwes360/admin/organizations.php" class="btn btn-secondary">🏢 Organizations</a>
    </div>
</div>

<!-- 8 High-Level Metrics Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div class="stat-details">
            <h3><?= $totalUsers ?></h3>
            <p>System User Accounts</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">👨‍🎓</div>
        <div class="stat-details">
            <h3><?= $totalStudents ?></h3>
            <p>Enrolled Trainees</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">🏢</div>
        <div class="stat-details">
            <h3><?= $totalOrgs ?></h3>
            <p>Approved Organizations</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">📍</div>
        <div class="stat-details">
            <h3><?= $totalPlacements ?></h3>
            <p>Active Placements</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">🕒</div>
        <div class="stat-details">
            <h3><?= $totalAttendance ?></h3>
            <p>Clock Logs Recorded</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">📑</div>
        <div class="stat-details">
            <h3><?= $totalReports ?></h3>
            <p>Weekly Reports</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">⭐</div>
        <div class="stat-details">
            <h3><?= $totalEvaluations ?></h3>
            <p>Completed Evaluations</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber">👔</div>
        <div class="stat-details">
            <h3><?= $totalSupervisors ?></h3>
            <p>Industry Mentors</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Recent System Security Logs -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Security & Audit Telemetry</div>
            <a href="/siwes360/admin/audit_logs.php" class="btn btn-secondary btn-sm">All Logs</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>User</th>
                            <th>IP & Details</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $l): ?>
                            <tr>
                                <td><code><?= e($l['action']) ?></code></td>
                                <td><?= e($l['full_name'] ?? 'System') ?></td>
                                <td style="max-width: 180px;"><?= e($l['description']) ?></td>
                                <td style="white-space: nowrap; color: #94a3b8;"><?= date('H:i:s M d', strtotime($l['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent User Registrations</div>
            <a href="/siwes360/admin/users.php" class="btn btn-secondary btn-sm">User Directory</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= e($u['full_name']) ?></strong>
                                    <div style="font-size: 11px; color: #94a3b8;"><?= e($u['email']) ?></div>
                                </td>
                                <td><span class="badge badge-secondary"><?= ucfirst(e($u['role'])) ?></span></td>
                                <td><span class="badge badge-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>"><?= e($u['status']) ?></span></td>
                                <td style="color: #94a3b8;"><?= e($u['last_login'] ?? 'Never') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
