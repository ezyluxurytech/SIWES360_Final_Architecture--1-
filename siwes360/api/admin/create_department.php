<?php
/**
 * SIWES360 - Admin Create Department
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/admin/departments.php');
}

verify_csrf();

$name = trim($_POST['name'] ?? '');
$faculty = trim($_POST['faculty'] ?? '');

if (empty($name) || empty($faculty)) {
    setFlash('danger', 'Department name and faculty required.');
    redirect('/siwes360/admin/departments.php');
}

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("INSERT INTO departments (name, faculty) VALUES (?, ?)");
    $stmt->execute([$name, $faculty]);
    logAudit($_SESSION['user_id'], 'DEPARTMENT_CREATED', "Added academic department {$name}");
    setFlash('success', "Department {$name} added successfully.");
} catch (Exception $e) {
    setFlash('danger', 'Unable to create department.');
}

redirect('/siwes360/admin/departments.php');
