<?php
/**
 * SIWES360 - Admin Create IT Session
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/admin/sessions.php');
}

verify_csrf();

$name = trim($_POST['name'] ?? '');
$startDate = trim($_POST['start_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');
$status = trim($_POST['status'] ?? 'active');

if (empty($name) || empty($startDate) || empty($endDate)) {
    setFlash('danger', 'Please complete all session timeline fields.');
    redirect('/siwes360/admin/sessions.php');
}

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("
        INSERT INTO it_sessions (name, start_date, end_date, status)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$name, $startDate, $endDate, $status]);
    logAudit($_SESSION['user_id'], 'SESSION_CREATED', "Created SIWES session {$name}");
    setFlash('success', "SIWES session {$name} scheduled successfully.");
} catch (Exception $e) {
    setFlash('danger', 'Unable to schedule session.');
}

redirect('/siwes360/admin/sessions.php');
