<?php
/**
 * SIWES360 - Admin Create Host Organization
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/admin/organizations.php');
}

verify_csrf();

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$industry = trim($_POST['industry'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($name) || empty($address) || empty($city)) {
    setFlash('danger', 'Please provide organization name, address, and city.');
    redirect('/siwes360/admin/organizations.php');
}

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("
        INSERT INTO organizations (name, address, city, state, phone, email, industry, description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')
    ");
    $stmt->execute([$name, $address, $city, $state, $phone, $email, $industry, $description]);
    logAudit($_SESSION['user_id'], 'ORGANIZATION_CREATED', "Accredited organization {$name}");
    setFlash('success', "Organization {$name} accredited successfully.");
} catch (Exception $e) {
    error_log("Org create error: " . $e->getMessage());
    setFlash('danger', 'Unable to create organization.');
}

redirect('/siwes360/admin/organizations.php');
