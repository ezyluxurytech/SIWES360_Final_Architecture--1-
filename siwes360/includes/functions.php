<?php
/**
 * SIWES360 - Global Helper Functions
 * Provides reusable sanitization, formatting, auditing, and authentication helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Escapes text for secure HTML output (XSS defense)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Validates and sanitizes standard string inputs
 */
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Inserts an immutable security record into audit_logs table
 */
function logAudit(?int $userId, string $action, string $description): bool {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $description, $ip]);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Dispatches an in-app notification to a user
 */
function createNotification(int $userId, string $title, string $message, string $type = 'info'): bool {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, ?, 0)");
        return $stmt->execute([$userId, $title, $message, $type]);
    } catch (Exception $e) {
        error_log("Notification dispatch error: " . $e->getMessage());
        return false;
    }
}

/**
 * CSRF protection for state-changing requests.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        setFlash('danger', 'Your session security token is invalid or expired. Please try again.');
        redirect('/siwes360/login.php');
    }
}

/**
 * Converts database status values into readable labels.
 */
function statusLabel(?string $status): string {
    if ($status === null || $status === '') return '—';
    return ucwords(str_replace('_', ' ', strtolower($status)));
}

/**
 * Redirect helper
 */
function redirect(string $path): void {
    header("Location: " . $path);
    exit;
}

/**
 * Sets a flash toast message in session
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieves and clears flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
