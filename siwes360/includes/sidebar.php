<?php
/**
 * SIWES360 - Role-Specific Responsive Navigation Sidebar
 */
$role = $_SESSION['role'] ?? 'student';
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="logo-badge">
            <span class="logo-icon">🎓</span>
            <span class="logo-title">SIWES<span>360</span></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'student'): ?>
            <div class="nav-section-title">Student Portal</div>
            <a href="/siwes360/student/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="/siwes360/student/placement.php" class="nav-link <?= $currentScript === 'placement.php' ? 'active' : '' ?>">🏢 My Placement</a>
            <a href="/siwes360/student/attendance.php" class="nav-link <?= $currentScript === 'attendance.php' ? 'active' : '' ?>">⏱️ Attendance</a>
            <a href="/siwes360/student/activities.php" class="nav-link <?= $currentScript === 'activities.php' ? 'active' : '' ?>">📖 Daily Logbook</a>
            <a href="/siwes360/student/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">📑 Weekly Reports</a>
            <a href="/siwes360/student/evaluation.php" class="nav-link <?= $currentScript === 'evaluation.php' ? 'active' : '' ?>">⭐ Evaluation</a>
            <a href="/siwes360/student/notifications.php" class="nav-link <?= $currentScript === 'notifications.php' ? 'active' : '' ?>">🔔 Notifications</a>
            <a href="/siwes360/student/profile.php" class="nav-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">👤 My Profile</a>

        <?php elseif ($role === 'supervisor'): ?>
            <div class="nav-section-title">Supervisor Portal</div>
            <a href="/siwes360/supervisor/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="/siwes360/supervisor/students.php" class="nav-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">👨‍🎓 My Students</a>
            <a href="/siwes360/supervisor/attendance.php" class="nav-link <?= $currentScript === 'attendance.php' ? 'active' : '' ?>">⏱️ Attendance Records</a>
            <a href="/siwes360/supervisor/activities.php" class="nav-link <?= $currentScript === 'activities.php' ? 'active' : '' ?>">📖 Daily Activities</a>
            <a href="/siwes360/supervisor/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">📑 Weekly Reports</a>
            <a href="/siwes360/supervisor/evaluations.php" class="nav-link <?= $currentScript === 'evaluations.php' ? 'active' : '' ?>">⭐ Evaluations</a>
            <a href="/siwes360/supervisor/notifications.php" class="nav-link <?= $currentScript === 'notifications.php' ? 'active' : '' ?>">🔔 Notifications</a>

        <?php elseif ($role === 'coordinator'): ?>
            <div class="nav-section-title">School Coordinator</div>
            <a href="/siwes360/coordinator/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="/siwes360/coordinator/students.php" class="nav-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">👨‍🎓 Students</a>
            <a href="/siwes360/coordinator/placements.php" class="nav-link <?= $currentScript === 'placements.php' ? 'active' : '' ?>">🏢 Placements</a>
            <a href="/siwes360/coordinator/attendance.php" class="nav-link <?= $currentScript === 'attendance.php' ? 'active' : '' ?>">⏱️ Attendance Monitoring</a>
            <a href="/siwes360/coordinator/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">📑 Weekly Reports</a>
            <a href="/siwes360/coordinator/evaluations.php" class="nav-link <?= $currentScript === 'evaluations.php' ? 'active' : '' ?>">⭐ Evaluations</a>
            <a href="/siwes360/coordinator/notifications.php" class="nav-link <?= $currentScript === 'notifications.php' ? 'active' : '' ?>">🔔 Notifications</a>

        <?php elseif ($role === 'admin'): ?>
            <div class="nav-section-title">System Administration</div>
            <a href="/siwes360/admin/dashboard.php" class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="/siwes360/admin/users.php" class="nav-link <?= $currentScript === 'users.php' ? 'active' : '' ?>">👥 Users</a>
            <a href="/siwes360/admin/students.php" class="nav-link <?= $currentScript === 'students.php' ? 'active' : '' ?>">👨‍🎓 Students</a>
            <a href="/siwes360/admin/supervisors.php" class="nav-link <?= $currentScript === 'supervisors.php' ? 'active' : '' ?>">🧑‍💼 Supervisors</a>
            <a href="/siwes360/admin/coordinators.php" class="nav-link <?= $currentScript === 'coordinators.php' ? 'active' : '' ?>">🏫 Coordinators</a>
            <a href="/siwes360/admin/organizations.php" class="nav-link <?= $currentScript === 'organizations.php' ? 'active' : '' ?>">🏢 Organizations</a>
            <a href="/siwes360/admin/departments.php" class="nav-link <?= $currentScript === 'departments.php' ? 'active' : '' ?>">🏛️ Departments</a>
            <a href="/siwes360/admin/sessions.php" class="nav-link <?= $currentScript === 'sessions.php' ? 'active' : '' ?>">📅 IT Sessions</a>
            <a href="/siwes360/admin/placements.php" class="nav-link <?= $currentScript === 'placements.php' ? 'active' : '' ?>">📌 Placements</a>
            <a href="/siwes360/admin/attendance.php" class="nav-link <?= $currentScript === 'attendance.php' ? 'active' : '' ?>">⏱️ Attendance Log</a>
            <a href="/siwes360/admin/reports.php" class="nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">📑 Weekly Reports</a>
            <a href="/siwes360/admin/evaluations.php" class="nav-link <?= $currentScript === 'evaluations.php' ? 'active' : '' ?>">⭐ Evaluations</a>
            <a href="/siwes360/admin/audit_logs.php" class="nav-link <?= $currentScript === 'audit_logs.php' ? 'active' : '' ?>">🛡️ Audit Logs</a>
        <?php endif; ?>

        <div class="nav-section-title" style="margin-top: 20px;">Session</div>
        <a href="/siwes360/logout.php" class="nav-link" style="color: #f87171;">🚪 Logout</a>
    </nav>
</aside>
