<?php
/**
 * SIWES360 - Admin IT Sessions Management
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

$sessions = $pdo->query("
    SELECT ses.*, 
           (SELECT COUNT(*) FROM placements p WHERE p.session_id = ses.session_id) AS total_placements
    FROM it_sessions ses
    ORDER BY ses.start_date DESC
")->fetchAll();

$pageTitle = "Training Sessions";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>SIWES Academic Training Sessions</h1>
        <p>Define academic session calendars, cohort start and completion timeframes.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addSessionModal')">
            📅 New Training Session
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Configured Cohort Sessions (<?= count($sessions) ?>)</div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Session Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Total Placements</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><strong><?= e($s['name']) ?></strong></td>
                            <td><?= date('d M Y', strtotime($s['start_date'])) ?></td>
                            <td><?= date('d M Y', strtotime($s['end_date'])) ?></td>
                            <td><span class="badge badge-<?= $s['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e($s['status']) ?></span></td>
                            <td><strong><?= (int)$s['total_placements'] ?></strong> assigned students</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Session -->
<div class="modal-backdrop" id="addSessionModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Configure SIWES Training Session</h3>
            <button type="button" onclick="closeModal('addSessionModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/admin/create_session.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="ses_name">Cohort / Program Name</label>
                    <input type="text" id="ses_name" name="name" required placeholder="e.g. 2026/2027 6-Month SIWES Batch B">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="ses_start">Start Date</label>
                        <input type="date" id="ses_start" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="ses_end">End Date</label>
                        <input type="date" id="ses_end" name="end_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ses_status">Session Status</label>
                    <select name="status" id="ses_status">
                        <option value="active">Active</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSessionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule Session</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
