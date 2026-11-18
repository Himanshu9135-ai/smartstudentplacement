<?php
/**
 * Admin: Placement Drives Management Directory
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

// Handle Delete Drive (POST only + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . BASE_URL . 'admin/drives.php');
        exit;
    }
    $driveId = (int)$_POST['id'];
    try {
        $stmtDel = $pdo->prepare("DELETE FROM placement_drives WHERE id = :id");
        $stmtDel->execute([':id' => $driveId]);
        setFlash('info', 'Placement drive removed successfully.');
    } catch (PDOException $e) {
        error_log("Delete drive error: " . $e->getMessage());
        setFlash('danger', 'Failed to remove placement drive. Child applications or records may be linked.');
    }
    header('Location: ' . BASE_URL . 'admin/drives.php');
    exit;
}

// Fetch Drives
$drives = $pdo->query("
    SELECT d.*, c.name as company_name, c.location as company_location,
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id) as total_applications,
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id AND status = 'Shortlisted') as shortlisted_count,
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id AND status = 'Selected') as selected_count
    FROM placement_drives d
    JOIN companies c ON d.company_id = c.id
    ORDER BY d.deadline ASC
")->fetchAll();

$pageTitle = 'Manage Placement Drives';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-briefcase-fill text-primary me-2"></i>Campus Placement Drives</h3>
                    <p class="text-muted mb-0">Manage campus drives, job eligibility criteria, and candidate applications</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>admin/drive_create.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Create Placement Drive
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Drives Table Card -->
            <div class="card card-portal">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-list-task text-primary me-2"></i>All Recruitment Drives (<?= count($drives) ?>)</h5>
                    <input type="text" class="form-control form-control-sm w-25" placeholder="Search drives..." data-table-search="drivesTable">
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($drives)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-portal align-middle mb-0" id="drivesTable">
                                <thead>
                                    <tr>
                                        <th>Company & Role</th>
                                        <th>Package (CTC)</th>
                                        <th>Min CGPA / Att.</th>
                                        <th>Drive Date</th>
                                        <th>Deadline</th>
                                        <th>Applications</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drives as $dr): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= e($dr['job_title']) ?></strong>
                                                <span class="badge bg-light text-dark"><i class="bi bi-building me-1"></i><?= e($dr['company_name']) ?></span>
                                            </td>
                                            <td>
                                                <strong class="text-success">₹ <?= number_format((float)$dr['package_lpa'], 2) ?> LPA</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">CGPA: <?= e($dr['min_cgpa']) ?></span>
                                                <span class="badge bg-light text-dark"><?= e($dr['min_attendance']) ?>% Att.</span>
                                            </td>
                                            <td><?= formatPortalDate($dr['drive_date']) ?></td>
                                            <td>
                                                <?php if ($dr['deadline'] < date('Y-m-d')): ?>
                                                    <span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i> <?= formatPortalDate($dr['deadline']) ?> (Closed)</span>
                                                <?php else: ?>
                                                    <span class="text-dark"><i class="bi bi-calendar-event me-1 text-primary"></i> <?= formatPortalDate($dr['deadline']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= $dr['total_applications'] ?> Applied</span>
                                                <?php if ($dr['selected_count'] > 0): ?>
                                                    <span class="badge bg-success ms-1"><?= $dr['selected_count'] ?> Selected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($dr['status'] === 'Active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($dr['status'] === 'Upcoming'): ?>
                                                    <span class="badge bg-info text-dark">Upcoming</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Closed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>admin/drive_view.php?id=<?= $dr['id'] ?>" class="btn btn-outline-primary" title="View Applicants & Eligible Pool">
                                                        <i class="bi bi-people-fill"></i> Review
                                                    </a>
                                                    <a href="<?= BASE_URL ?>admin/drive_edit.php?id=<?= $dr['id'] ?>" class="btn btn-outline-secondary" title="Edit Drive">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="<?= BASE_URL ?>admin/drives.php" method="POST" class="d-inline" onsubmit="return confirm('Delete drive for <?= e(addslashes($dr['job_title'])) ?>? All applications will be permanently deleted.');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $dr['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Drive">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-briefcase display-4 d-block mb-3 text-secondary"></i>
                            <h5 class="fw-bold text-dark">No Placement Drives Created</h5>
                            <p class="small text-muted mb-3">Post your first company recruitment drive to open applications.</p>
                            <a href="<?= BASE_URL ?>admin/drive_create.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Create Drive
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
