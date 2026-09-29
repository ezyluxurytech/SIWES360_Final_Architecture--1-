<?php
/**
 * SIWES360 - Account Registration Handler
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/register.php');
}

verify_csrf();

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
$allowedRoles = ['student', 'admin', 'supervisor', 'coordinator'];
$registerPath = in_array($role, $allowedRoles, true)
    ? '/siwes360/register.php?role=' . rawurlencode($role)
    : '/siwes360/register.php';

if ($fullName === '' || strlen($fullName) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 || !in_array($role, $allowedRoles, true)) {
    setFlash('danger', 'Enter a valid name, email address, and account type.');
    redirect($registerPath);
}
if (strlen($password) < 8 || $password !== $passwordConfirm) {
    setFlash('danger', 'Passwords must be at least 8 characters and match.');
    redirect($registerPath);
}

$pdo = getDBConnection();
$departmentId = (int)($_POST['department_id'] ?? 0);
$coordinatorDepartmentId = (int)($_POST['coordinator_department_id'] ?? 0);
$organizationId = (int)($_POST['organization_id'] ?? 0);
$matricNo = trim($_POST['matric_no'] ?? '');
$level = trim($_POST['level'] ?? '');
$position = trim($_POST['position'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($role === 'student') {
    if ($matricNo === '' || strlen($matricNo) > 50 || !in_array($level, ['100', '200', '300', '400', '500', '600'], true)) {
        setFlash('danger', 'Enter a valid matriculation number and academic level.');
        redirect($registerPath);
    }
    $checkDepartment = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_id = ? AND status = 'active'");
    $checkDepartment->execute([$departmentId]);
    if (!$checkDepartment->fetchColumn()) {
        setFlash('danger', 'Choose an active department.');
        redirect($registerPath);
    }
} elseif ($role === 'supervisor') {
    $checkOrganization = $pdo->prepare("SELECT COUNT(*) FROM organizations WHERE organization_id = ? AND status = 'approved'");
    $checkOrganization->execute([$organizationId]);
    if (!$checkOrganization->fetchColumn()) {
        setFlash('danger', 'Choose an approved host organization.');
        redirect($registerPath);
    }
    if (strlen($position) > 100) {
        setFlash('danger', 'Job title must be 100 characters or fewer.');
        redirect($registerPath);
    }
} elseif ($role === 'coordinator') {
    $checkDepartment = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_id = ? AND status = 'active'");
    $checkDepartment->execute([$coordinatorDepartmentId]);
    if (!$checkDepartment->fetchColumn()) {
        setFlash('danger', 'Choose an active department.');
        redirect($registerPath);
    }
}

try {
    $pdo->beginTransaction();

    $emailCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $emailCheck->execute([$email]);
    if ($emailCheck->fetchColumn()) {
        $pdo->rollBack();
        setFlash('danger', 'An account with this email address already exists.');
        redirect($registerPath);
    }

    $adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $hasAdmin = (bool)$adminCheck->fetchColumn();
    $status = ($role === 'admin' && $hasAdmin) ? 'inactive' : 'active';

    $createUser = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
    $createUser->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status]);
    $userId = (int)$pdo->lastInsertId();

    if ($role === 'student') {
        $createProfile = $pdo->prepare('INSERT INTO students (user_id, department_id, matric_no, level, phone) VALUES (?, ?, ?, ?, ?)');
        $createProfile->execute([$userId, $departmentId, $matricNo, $level, $phone !== '' ? $phone : null]);
    } elseif ($role === 'supervisor') {
        $createProfile = $pdo->prepare('INSERT INTO supervisors (user_id, organization_id, position, phone) VALUES (?, ?, ?, ?)');
        $createProfile->execute([$userId, $organizationId, $position !== '' ? $position : null, $phone !== '' ? $phone : null]);
    } elseif ($role === 'coordinator') {
        $createProfile = $pdo->prepare('INSERT INTO coordinators (user_id, department_id, phone) VALUES (?, ?, ?)');
        $createProfile->execute([$userId, $coordinatorDepartmentId, $phone !== '' ? $phone : null]);
    }

    $pdo->commit();
    if ($status === 'inactive') {
        setFlash('success', 'Administrator account created. An existing administrator must activate it before you can sign in.');
    } else {
        setFlash('success', 'Account created successfully. You can now sign in.');
    }
    redirect('/siwes360/login.php');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SIWES360 registration error: ' . $e->getMessage());
    setFlash('danger', 'Account could not be created. Check that the email and matriculation number are not already in use.');
    redirect($registerPath);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SIWES360 registration error: ' . $e->getMessage());
    setFlash('danger', 'Account could not be created. Please try again or contact the administrator.');
    redirect($registerPath);
}