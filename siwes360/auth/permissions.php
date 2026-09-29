<?php
/**
 * SIWES360 - Role-Based Access Control (RBAC) Guard
 */

require_once __DIR__ . '/auth_check.php';

function requireRole(string ...$allowedRoles): void {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
        logAudit($_SESSION['user_id'] ?? null, 'UNAUTHORIZED_ACCESS_ATTEMPT', 'Attempted to access restricted role route: ' . $_SERVER['REQUEST_URI']);
        setFlash('danger', 'Access denied. You do not possess clearance for this section.');
        
        // Redirect to their respective authorized home
        switch ($_SESSION['role'] ?? '') {
            case 'admin':
                redirect('/siwes360/admin/dashboard.php');
                break;
            case 'student':
                redirect('/siwes360/student/dashboard.php');
                break;
            case 'supervisor':
                redirect('/siwes360/supervisor/dashboard.php');
                break;
            case 'coordinator':
                redirect('/siwes360/coordinator/dashboard.php');
                break;
            default:
                redirect('/siwes360/login.php');
                break;
        }
    }
}
