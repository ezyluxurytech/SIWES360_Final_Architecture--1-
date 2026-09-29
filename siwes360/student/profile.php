<?php
/**
 * SIWES360 - Student Profile Management
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('student');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!empty($phone) && !empty($address)) {
        $upStmt = $pdo->prepare("UPDATE students SET phone = ?, address = ? WHERE user_id = ?");
        $upStmt->execute([$phone, $address, $userId]);
        logAudit($userId, 'PROFILE_UPDATED', 'Updated student contact phone and physical address.');
        setFlash('success', 'Profile contact information updated successfully.');
    } else {
        setFlash('danger', 'Phone and address cannot be empty.');
    }
}

$stmt = $pdo->prepare("
    SELECT s.*, u.full_name, u.email, u.last_login, d.name AS department_name, d.faculty
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN departments d ON s.department_id = d.department_id
    WHERE s.user_id = ?
");
$stmt->execute([$userId]);
$student = $stmt->fetch();

$pageTitle = "My Student Profile";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Student Profile</h1>
        <p>Personal biodata and institutional training registration credentials.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Biodata Card -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Institutional Records (Read-Only)</div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <tr>
                    <td style="font-weight: 600; width: 140px;">Full Name</td>
                    <td><strong><?= e($student['full_name']) ?></strong></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Matric Number</td>
                    <td><code><?= e($student['matric_no']) ?></code></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Department</td>
                    <td><?= e($student['department_name']) ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Faculty</td>
                    <td><?= e($student['faculty']) ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Academic Level</td>
                    <td><?= e($student['level']) ?> Level</td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Institutional Email</td>
                    <td><?= e($student['email']) ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Gender</td>
                    <td><?= e($student['gender']) ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Last Active Login</td>
                    <td><?= e($student['last_login'] ?? 'Never') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Editable Contact Info -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Update Contact Details</div>
        </div>
        <div class="card-body">
            <form action="/siwes360/student/profile.php" method="POST">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?= e($student['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="address">Residential / Placement Address</label>
                    <textarea id="address" name="address" rows="3" required><?= e($student['address']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
