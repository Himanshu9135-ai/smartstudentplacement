<?php
/**
 * Admin: Edit Placement Drive
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

$driveId = (int)($_GET['id'] ?? 0);
if ($driveId <= 0) {
    setFlash('danger', 'Invalid drive ID.');
    header('Location: ' . BASE_URL . 'admin/drives.php');
    exit;
}

// Fetch Drive
$stmt = $pdo->prepare("SELECT * FROM placement_drives WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $driveId]);
$drive = $stmt->fetch();

if (!$drive) {
    setFlash('danger', 'Placement drive not found.');
    header('Location: ' . BASE_URL . 'admin/drives.php');
    exit;
}

// Current skills
$stmtCurSkills = $pdo->prepare("SELECT skill_id FROM drive_skills WHERE drive_id = :did");
$stmtCurSkills->execute([':did' => $driveId]);
$currentSkillIds = $stmtCurSkills->fetchAll(PDO::FETCH_COLUMN);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    }

    $company_id       = (int)($_POST['company_id'] ?? 0);
    $job_title        = trim($_POST['job_title'] ?? '');
    $job_description  = trim($_POST['job_description'] ?? '');
    $package_lpa      = (float)($_POST['package_lpa'] ?? 0);
    $job_location     = trim($_POST['job_location'] ?? 'On-Site');
    $min_cgpa         = (float)($_POST['min_cgpa'] ?? 6.0);
    $min_attendance   = (float)($_POST['min_attendance'] ?? 75.0);
    $max_backlogs     = (int)($_POST['max_backlogs'] ?? 0);
    $allowed_branches = trim($_POST['allowed_branches'] ?? 'All');
    $drive_date       = !empty($_POST['drive_date']) ? $_POST['drive_date'] : null;
    $deadline         = $_POST['deadline'] ?? '';
    
    $rawStatus        = $_POST['status'] ?? 'Active';
    $status           = in_array($rawStatus, ['Active', 'Upcoming', 'Closed'], true) ? $rawStatus : 'Active';
    $skills           = is_array($_POST['skills'] ?? null) ? $_POST['skills'] : [];

    if ($company_id <= 0) $errors[] = 'Please select a company.';
    if (empty($job_title)) $errors[] = 'Job title is required.';
    if (empty($job_description)) $errors[] = 'Job description is required.';
    if (!empty($drive_date) && !empty($deadline) && $deadline > $drive_date) {
        $errors[] = 'Application deadline cannot be after the drive date.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmtUp = $pdo->prepare("
                UPDATE placement_drives 
                SET company_id = :cid, job_title = :title, job_description = :descr,
                    package_lpa = :lpa, job_location = :loc, min_cgpa = :cgpa,
                    min_attendance = :att, max_backlogs = :backlogs, allowed_branches = :branches,
                    drive_date = :ddate, deadline = :dline, status = :status
                WHERE id = :id
            ");
            $stmtUp->execute([
                ':cid'      => $company_id,
                ':title'    => $job_title,
                ':descr'    => $job_description,
                ':lpa'      => $package_lpa,
                ':loc'      => $job_location,
                ':cgpa'     => $min_cgpa,
                ':att'      => $min_attendance,
                ':backlogs' => $max_backlogs,
                ':branches' => $allowed_branches,
                ':ddate'    => $drive_date,
                ':dline'    => $deadline,
                ':status'   => $status,
                ':id'       => $driveId
            ]);

            // Update skills
            $pdo->prepare("DELETE FROM drive_skills WHERE drive_id = :did")->execute([':did' => $driveId]);
            if (!empty($skills) && is_array($skills)) {
                $stmtInsSkill = $pdo->prepare("INSERT INTO drive_skills (drive_id, skill_id) VALUES (:did, :skid)");
                foreach ($skills as $skId) {
                    $stmtInsSkill->execute([':did' => $driveId, ':skid' => (int)$skId]);
                }
            }

            $pdo->commit();

            setFlash('success', 'Drive details updated successfully!');
            header('Location: ' . BASE_URL . 'admin/drives.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Drive update error: " . $e->getMessage());
            $errors[] = 'Update failed due to an internal system error.';
        }
    }
}

// Fetch companies and skills
$companies = $pdo->query("SELECT id, name, location FROM companies ORDER BY name ASC")->fetchAll();
$allSkills = $pdo->query("SELECT * FROM skills ORDER BY category ASC, skill_name ASC")->fetchAll();

$pageTitle = 'Edit Drive - ' . e($drive['job_title']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Placement Drive</h3>
                    <p class="text-muted mb-0"><?= e($drive['job_title']) ?></p>
                </div>
                <a href="<?= BASE_URL ?>admin/drives.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Drives
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errors:</h6>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>admin/drive_edit.php?id=<?= $driveId ?>" method="POST" class="needs-validation">
                <?= csrfField() ?>
                <div class="row g-4">
                    <!-- Left Column -->
                    <div class="col-lg-7">
                        <div class="card card-portal">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Job & Company Profile</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="company_id" class="form-label fw-semibold">Hosting Company *</label>
                                    <select class="form-select form-select-lg" id="company_id" name="company_id" required>
                                        <?php foreach ($companies as $comp): ?>
                                            <option value="<?= $comp['id'] ?>" <?= $drive['company_id'] == $comp['id'] ? 'selected' : '' ?>>
                                                <?= e($comp['name']) ?> (<?= e($comp['location']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="job_title" class="form-label fw-semibold">Job Title / Designation *</label>
                                    <input type="text" class="form-control form-control-lg" id="job_title" name="job_title" value="<?= e($drive['job_title']) ?>" required>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="package_lpa" class="form-label fw-semibold">Salary / CTC (LPA)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.05" min="0" class="form-control" id="package_lpa" name="package_lpa" value="<?= e($drive['package_lpa']) ?>">
                                            <span class="input-group-text">LPA</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="job_location" class="form-label fw-semibold">Job Work Location</label>
                                        <input type="text" class="form-control" id="job_location" name="job_location" value="<?= e($drive['job_location']) ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="job_description" class="form-label fw-semibold">Job Description *</label>
                                    <textarea class="form-control" id="job_description" name="job_description" rows="5" required><?= e($drive['job_description']) ?></textarea>
                                </div>

                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-stars text-warning me-2"></i>Required Technical Skills</h6>
                                <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                    <div class="row g-2">
                                        <?php foreach ($allSkills as $sk): ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="sk_<?= $sk['id'] ?>" <?= in_array($sk['id'], $currentSkillIds) ? 'checked' : '' ?>>
                                                    <label class="form-check-label small" for="sk_<?= $sk['id'] ?>">
                                                        <strong><?= e($sk['skill_name']) ?></strong> <span class="text-muted">(<?= e($sk['category']) ?>)</span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-5">
                        <div class="card card-portal mb-4">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-sliders text-primary me-2"></i>Eligibility Criteria</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="min_cgpa" class="form-label fw-semibold">Minimum CGPA *</label>
                                    <input type="number" step="0.01" min="0" max="10" class="form-control form-control-lg" id="min_cgpa" name="min_cgpa" value="<?= e($drive['min_cgpa']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="min_attendance" class="form-label fw-semibold">Minimum Attendance % *</label>
                                    <input type="number" step="0.1" min="0" max="100" class="form-control form-control-lg" id="min_attendance" name="min_attendance" value="<?= e($drive['min_attendance']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="max_backlogs" class="form-label fw-semibold">Max Allowed Backlogs</label>
                                    <input type="number" min="0" max="10" class="form-control" id="max_backlogs" name="max_backlogs" value="<?= e($drive['max_backlogs']) ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="allowed_branches" class="form-label fw-semibold">Eligible Branches</label>
                                    <input type="text" class="form-control" id="allowed_branches" name="allowed_branches" value="<?= e($drive['allowed_branches']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card card-portal">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check text-primary me-2"></i>Schedule & Status</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="drive_date" class="form-label fw-semibold">Drive Date</label>
                                    <input type="date" class="form-control" id="drive_date" name="drive_date" value="<?= e($drive['drive_date']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="deadline" class="form-label fw-semibold">Application Deadline</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?= e($drive['deadline']) ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label for="status" class="form-label fw-semibold">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Active" <?= $drive['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option value="Upcoming" <?= $drive['status'] === 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                        <option value="Closed" <?= $drive['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= BASE_URL ?>admin/drives.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
