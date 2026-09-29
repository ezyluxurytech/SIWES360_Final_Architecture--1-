<?php
/**
 * SIWES360 - Admin Create User & Role Profile
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/admin/users.php');
}

verify_csrf();

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'student');
$password = trim($_POST['password'] ?? 'Password123!');

$allowedRoles = ['student','supervisor','coordinator','admin'];
if (empty($fullName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowedRoles, true)) {
    setFlash('danger', 'Please provide a valid full name and email address.');
    redirect('/siwes360/admin/users.php');
}

$pdo = getDBConnection();

// Check if email already exists
$chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$chk->execute([$email]);
if ($chk->fetchColumn() > 0) {
    setFlash('danger', 'A user with this email address already exists.');
    redirect('/siwes360/admin/users.php');
}

try {
    $pdo->beginTransaction();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $insUser = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
    $insUser->execute([$fullName, $email, $hashedPassword, $role]);
    $newUserId = (int)$pdo->lastInsertId();

    if ($role === 'student') {
        $matricNo = trim($_POST['matric_no'] ?? ('MAT/' . rand(10000, 99999)));
        $deptId = (int)($_POST['department_id'] ?? 1);
        $level = trim($_POST['level'] ?? '400');
        $phone = trim($_POST['phone'] ?? '08000000000');
        $address = trim($_POST['address'] ?? 'University Campus');
        $gender = trim($_POST['gender'] ?? 'male');

        $insStudent = $pdo->prepare("
            INSERT INTO students (user_id, department_id, matric_no, level, phone, address, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insStudent->execute([$newUserId, $deptId, $matricNo, $level, $phone, $address, $gender]);
    } elseif ($role === 'supervisor') {
        $orgId = (int)($_POST['organization_id'] ?? 1);
        $position = trim($_POST['position'] ?? 'Senior Engineer');
        $phone = trim($_POST['phone'] ?? '08011112222');

        $insSup = $pdo->prepare("
            INSERT INTO supervisors (user_id, organization_id, position, phone)
            VALUES (?, ?, ?, ?)
        ");
        $insSup->execute([$newUserId, $orgId, $position, $phone]);
    } elseif ($role === 'coordinator') {
        $deptId = (int)($_POST['department_id'] ?? 1);
        $phone = trim($_POST['phone'] ?? '08033334444');

        $insCoor = $pdo->prepare("
            INSERT INTO coordinators (user_id, department_id, phone)
            VALUES (?, ?, ?)
        ");
        $insCoor->execute([$newUserId, $deptId, $phone]);
    }

    createNotification($newUserId, "Welcome to SIWES360", "Your user account has been registered as {$role}. Please update your profile.", "success");
    logAudit($_SESSION['user_id'], 'USER_CREATED', "Admin created {$role} account: {$email} (ID: {$newUserId})");

    $pdo->commit();
    setFlash('success', "User account {$fullName} ({$role}) registered successfully.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Admin create user error: " . $e->getMessage());
    setFlash('danger', 'Unable to create user: ' . $e->getMessage());
}

redirect('/siwes360/admin/users.php');
