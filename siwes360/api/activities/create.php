<?php
/**
 * SIWES360 - Create / Edit Daily Logbook Activity
 */
require_once __DIR__ . '/../../auth/permissions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/siwes360/student/activities.php');
}

verify_csrf();

$placementId = (int)($_POST['placement_id'] ?? 0);
$activityId = (int)($_POST['activity_id'] ?? 0);
$activityDate = trim($_POST['activity_date'] ?? date('Y-m-d'));
$description = trim($_POST['description'] ?? '');
$skillsLearned = trim($_POST['skills_learned'] ?? '');
$toolsUsed = trim($_POST['tools_used'] ?? '');
$challenges = trim($_POST['challenges'] ?? '');
$lessonsLearned = trim($_POST['lessons_learned'] ?? '');
$action = $_POST['submit_action'] ?? 'Submit'; // 'draft' or 'Submit'
$status = ($action === 'draft') ? 'draft' : 'submitted';

if ($placementId <= 0 || empty($description) || empty($skillsLearned) || empty($toolsUsed)) {
    setFlash('danger', 'Please complete all required activity fields (date, description, skills, tools).');
    redirect('/siwes360/student/activities.php');
}

$pdo = getDBConnection();

// Verify placement belongs to authenticated student
$stmt = $pdo->prepare("SELECT p.placement_id, p.supervisor_id FROM placements p JOIN students s ON p.student_id = s.student_id WHERE p.placement_id = ? AND s.user_id = ?");
$stmt->execute([$placementId, $_SESSION['user_id']]);
$placement = $stmt->fetch();
if (!$placement) {
    setFlash('danger', 'Unauthorized placement record.');
    redirect('/siwes360/student/activities.php');
}

try {
    if ($activityId > 0) {
        // Edit existing draft
        $upStmt = $pdo->prepare("
            UPDATE daily_activities 
            SET activity_date = ?, description = ?, skills_learned = ?, tools_used = ?, challenges = ?, lessons_learned = ?, status = ?
            WHERE activity_id = ? AND placement_id = ? AND status = 'draft'
        ");
        $upStmt->execute([$activityDate, $description, $skillsLearned, $toolsUsed, $challenges, $lessonsLearned, $status, $activityId, $placementId]);
        logAudit($_SESSION['user_id'], 'ACTIVITY_UPDATED', "Updated daily activity ID {$activityId} as {$status}");
        setFlash('success', "Daily activity entry updated ({$status}).");
    } else {
        // Insert new entry
        $insStmt = $pdo->prepare("
            INSERT INTO daily_activities (placement_id, activity_date, description, skills_learned, tools_used, challenges, lessons_learned, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->execute([$placementId, $activityDate, $description, $skillsLearned, $toolsUsed, $challenges, $lessonsLearned, $status]);
        
        // Notify supervisor if submitted
        if ($status === 'submitted') {
            $supStmt = $pdo->prepare("SELECT user_id FROM supervisors WHERE supervisor_id = ?");
            $supStmt->execute([$placement['supervisor_id']]);
            $supUserId = $supStmt->fetchColumn();
            if ($supUserId) {
                createNotification($supUserId, "New Daily Activity Submitted", "A daily log entry was submitted for review on {$activityDate}.", "info");
            }
        }

        logAudit($_SESSION['user_id'], 'ACTIVITY_CREATED', "Logged daily activity on {$activityDate} as {$status}");
        setFlash('success', "Daily activity logged successfully as {$status}.");
    }
} catch (Exception $e) {
    error_log("Activity save error: " . $e->getMessage());
    setFlash('danger', 'Unable to save activity. Please try again.');
}

redirect('/siwes360/student/activities.php');
