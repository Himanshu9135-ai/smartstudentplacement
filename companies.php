<?php
/**
 * Admin: Company / Recruiter Management CRUD
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . BASE_URL . 'admin/companies.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_company') {
            $name          = trim($_POST['name'] ?? '');
            $website       = trim($_POST['website'] ?? '');
            $contact_email = trim($_POST['contact_email'] ?? '');
            $contact_phone = trim($_POST['contact_phone'] ?? '');
            $location      = trim($_POST['location'] ?? '');
            $description   = trim($_POST['description'] ?? '');

            if (!empty($name)) {
                $stmt = $pdo->prepare("
                    INSERT INTO companies (name, website, contact_email, contact_phone, location, description)
                    VALUES (:name, :web, :email, :phone, :loc, :desc)
                ");
                $stmt->execute([
                    ':name'  => $name,
                    ':web'   => $website,
                    ':email' => $contact_email,
                    ':phone' => $contact_phone,
                    ':loc'   => $location,
                    ':desc'  => $description
                ]);
                setFlash('success', 'Company "' . e($name) . '" added successfully!');
            }
        } elseif ($action === 'edit_company') {
            $id            = (int)($_POST['company_id'] ?? 0);
            $name          = trim($_POST['name'] ?? '');
            $website       = trim($_POST['website'] ?? '');
            $contact_email = trim($_POST['contact_email'] ?? '');
            $contact_phone = trim($_POST['contact_phone'] ?? '');
            $location      = trim($_POST['location'] ?? '');
            $description   = trim($_POST['description'] ?? '');

            if ($id > 0 && !empty($name)) {
                $stmt = $pdo->prepare("
                    UPDATE companies 
                    SET name = :name, website = :web, contact_email = :email, 
                        contact_phone = :phone, location = :loc, description = :desc
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':name'  => $name,
                    ':web'   => $website,
                    ':email' => $contact_email,
                    ':phone' => $contact_phone,
                    ':loc'   => $location,
                    ':desc'  => $description,
                    ':id'    => $id
                ]);
                setFlash('success', 'Company details updated successfully!');
            }
        } elseif ($action === 'delete_company') {
            $id = (int)($_POST['company_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM companies WHERE id = :id");
                $stmt->execute([':id' => $id]);
                setFlash('info', 'Company record removed.');
            }
        }
    } catch (PDOException $e) {
        error_log("Company action error: " . $e->getMessage());
        setFlash('danger', 'Operation failed due to a database constraint or error.');
    }

    header('Location: ' . BASE_URL . 'admin/companies.php');
    exit;
}

// Fetch all companies with drive counts
$companies = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM placement_drives WHERE company_id = c.id) as total_drives,
           (SELECT COUNT(*) FROM applications a JOIN placement_drives d ON a.drive_id = d.id WHERE d.company_id = c.id AND a.status = 'Selected') as total_hires
    FROM companies c
    ORDER BY c.name ASC
")->fetchAll();

$pageTitle = 'Manage Companies';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-building text-primary me-2"></i>Recruiter & Company Directory</h3>
                    <p class="text-muted mb-0">Manage industry recruitment partners and visiting placement companies</p>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Add Company Card -->
                <div class="col-lg-4">
                    <div class="card card-portal">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add Company</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?= BASE_URL ?>admin/companies.php" method="POST" class="needs-validation">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add_company">

                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold">Company Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Infosys, TCS, Wipro" required>
                                </div>

                                <div class="mb-3">
                                    <label for="location" class="form-label fw-semibold">Location / Headquarters</label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Pune / Bangalore">
                                </div>

                                <div class="mb-3">
                                    <label for="website" class="form-label fw-semibold">Website URL</label>
                                    <input type="url" class="form-control" id="website" name="website" placeholder="https://company.com">
                                </div>

                                <div class="mb-3">
                                    <label for="contact_email" class="form-label fw-semibold">HR Contact Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="hr@company.com">
                                </div>

                                <div class="mb-3">
                                    <label for="contact_phone" class="form-label fw-semibold">HR Phone Number</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="+91 9876543210">
                                </div>

                                <div class="mb-4">
                                    <label for="description" class="form-label fw-semibold">Overview / Industry</label>
                                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Brief company profile..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-check-lg me-1"></i> Save Company
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Companies List -->
                <div class="col-lg-8">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-building-check text-primary me-2"></i>Partner Companies (<?= count($companies) ?>)</h5>
                            <input type="text" class="form-control form-control-sm w-25" placeholder="Search..." data-table-search="companiesTable">
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($companies)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-portal align-middle mb-0" id="companiesTable">
                                        <thead>
                                            <tr>
                                                <th>Company</th>
                                                <th>Location</th>
                                                <th>HR Contact</th>
                                                <th>Drives</th>
                                                <th>Hires</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($companies as $c): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="d-block text-dark"><?= e($c['name']) ?></strong>
                                                        <?php if (!empty($c['website'])): ?>
                                                            <a href="<?= e($c['website']) ?>" target="_blank" class="small text-primary"><i class="bi bi-link-45deg"></i> <?= e(parse_url($c['website'], PHP_URL_HOST) ?: $c['website']) ?></a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= e($c['location'] ?? 'N/A') ?></td>
                                                    <td class="small">
                                                        <div><i class="bi bi-envelope me-1"></i><?= e($c['contact_email'] ?? 'N/A') ?></div>
                                                        <div><i class="bi bi-telephone me-1"></i><?= e($c['contact_phone'] ?? 'N/A') ?></div>
                                                    </td>
                                                    <td><span class="badge bg-primary rounded-pill"><?= $c['total_drives'] ?> Drives</span></td>
                                                    <td><span class="badge bg-success rounded-pill"><?= $c['total_hires'] ?> Placed</span></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCompModal<?= $c['id'] ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form action="<?= BASE_URL ?>admin/companies.php" method="POST" class="d-inline">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="delete_company">
                                                            <input type="hidden" name="company_id" value="<?= $c['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete company <?= e($c['name']) ?>? All associated drives will also be removed.">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>

                                                <!-- Edit Company Modal -->
                                                <div class="modal fade" id="editCompModal<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="<?= BASE_URL ?>admin/companies.php" method="POST">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="action" value="edit_company">
                                                                <input type="hidden" name="company_id" value="<?= $c['id'] ?>">

                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title fw-bold">Edit Company: <?= e($c['name']) ?></h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">Company Name *</label>
                                                                        <input type="text" name="name" class="form-control" value="<?= e($c['name']) ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">Location</label>
                                                                        <input type="text" name="location" class="form-control" value="<?= e($c['location']) ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">Website</label>
                                                                        <input type="url" name="website" class="form-control" value="<?= e($c['website']) ?>">
                                                                    </div>
                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label fw-semibold">Contact Email</label>
                                                                            <input type="email" name="contact_email" class="form-control" value="<?= e($c['contact_email']) ?>">
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <label class="form-label fw-semibold">Contact Phone</label>
                                                                            <input type="tel" name="contact_phone" class="form-control" value="<?= e($c['contact_phone']) ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-semibold">Description</label>
                                                                        <textarea name="description" class="form-control" rows="2"><?= e($c['description']) ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                                    No companies registered yet. Add your first recruiter on the left!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
