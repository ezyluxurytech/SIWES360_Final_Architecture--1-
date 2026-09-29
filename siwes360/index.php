<?php
/**
 * SIWES360 - Root Router
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: /siwes360/admin/dashboard.php");
            exit;
        case 'student':
            header("Location: /siwes360/student/dashboard.php");
            exit;
        case 'supervisor':
            header("Location: /siwes360/supervisor/dashboard.php");
            exit;
        case 'coordinator':
            header("Location: /siwes360/coordinator/dashboard.php");
            exit;
    }
}

header("Location: /siwes360/login.php");
exit;
