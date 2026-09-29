<?php
/**
 * SIWES360 - Admin Organizations Management
 */
require_once __DIR__ . '/../auth/permissions.php';
requireRole('admin');

$pdo = getDBConnection();

$stmt = $pdo->query("
    SELECT o.*, 
           (SELECT COUNT(*) FROM placements p WHERE p.organization_id = o.organization_id) AS total_interns,
           (SELECT COUNT(*) FROM supervisors s WHERE s.organization_id = o.organization_id) AS total_supervisors
    FROM organizations o
    ORDER BY o.name ASC
");
$organizations = $stmt->fetchAll();

$pageTitle = "Accredited Organizations";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-title-row">
    <div class="page-heading">
        <h1>Accredited Host Organizations</h1>
        <p>Manage corporate industry partners, IT training centers, and placement locations.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('addOrgModal')">
            🏢 Register Organization
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Accredited Industry Partners (<?= count($organizations) ?>)</div>
        <input type="text" id="tableSearch" placeholder="Search companies..." style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Organization Name</th>
                        <th>Industry Sector</th>
                        <th>Location</th>
                        <th>Contact Email & Phone</th>
                        <th>Mentors</th>
                        <th>Active Interns</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                        <tr>
                            <td>
                                <strong><?= e($org['name']) ?></strong>
                                <div style="font-size: 11.5px; color: #94a3b8; max-width: 260px;"><?= e($org['description']) ?></div>
                            </td>
                            <td><span class="badge badge-secondary"><?= e($org['industry']) ?></span></td>
                            <td><?= e($org['city']) ?>, <?= e($org['state']) ?></td>
                            <td>
                                <div><?= e($org['email']) ?></div>
                                <div style="font-size: 11.5px; color: #94a3b8;"><?= e($org['phone']) ?></div>
                            </td>
                            <td><strong><?= (int)$org['total_supervisors'] ?></strong> mentors</td>
                            <td><span class="badge badge-success"><?= (int)$org['total_interns'] ?> interns</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Register Organization -->
<div class="modal-backdrop" id="addOrgModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-size: 16px; font-weight: 700; color: var(--primary-navy);">Accredit Host Organization</h3>
            <button type="button" onclick="closeModal('addOrgModal')" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/siwes360/api/admin/create_organization.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label for="org_name">Organization / Company Name</label>
                    <input type="text" id="org_name" name="name" required placeholder="e.g. Flutterwave Payment Solutions">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="org_ind">Industry Sector</label>
                        <input type="text" id="org_ind" name="industry" required placeholder="e.g. Fintech & Cloud Computing">
                    </div>
                    <div class="form-group">
                        <label for="org_city">City</label>
                        <input type="text" id="org_city" name="city" required placeholder="Lagos">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="org_state">State</label>
                        <input type="text" id="org_state" name="state" value="Lagos State" required>
                    </div>
                    <div class="form-group">
                        <label for="org_phone">Phone Number</label>
                        <input type="text" id="org_phone" name="phone" placeholder="+234 1 234 5678">
                    </div>
                </div>

                <div class="form-group">
                    <label for="org_email">Corporate Contact Email</label>
                    <input type="email" id="org_email" name="email" placeholder="siwes@company.com">
                </div>

                <div class="form-group">
                    <label for="org_addr">Physical Office Address</label>
                    <input type="text" id="org_addr" name="address" required placeholder="Plot 12, Commercial Avenue, Victoria Island">
                </div>

                <div class="form-group">
                    <label for="org_desc">Overview Description</label>
                    <textarea id="org_desc" name="description" rows="2" placeholder="Primary technologies and departments available for industrial attachment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addOrgModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Accredit Organization</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
