<?php
/**
 * Admin: Create Placement Drive
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

$errors = [];
$formData = [
    'company_id' => 0,
    'job_title' => '',
    'job_description' => '',
    'package_lpa' => '3.60',
    'job_location' => 'On-Site',
    'min_cgpa' => '6.50',
    'min_attendance' => '75.00',
    'max_backlogs' => 0,
    'allowed_branches' => 'All',
    'drive_date' => date('Y-m-d', strtotime('+14 days')),
    'deadline' => date('Y-m-d', strtotime('+7 days')),
    'status' => 'Active',
    'skills' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    }

    $formData['company_id']       = (int)($_POST['company_id'] ?? 0);
    $formData['job_title']        = trim($_POST['job_title'] ?? '');
    $formData['job_description']  = trim($_POST['job_description'] ?? '');
    $formData['package_lpa']      = (float)($_POST['package_lpa'] ?? 0);
    $formData['job_location']     = trim($_POST['job_location'] ?? 'On-Site');
    $formData['min_cgpa']         = (float)($_POST['min_cgpa'] ?? 6.0);
    $formData['min_attendance']   = (float)($_POST['min_attendance'] ?? 75.0);
    $formData['max_backlogs']     = (int)($_POST['max_backlogs'] ?? 0);
    $formData['allowed_branches'] = trim($_POST['allowed_branches'] ?? 'All');
    $formData['drive_date']       = !empty($_POST['drive_date']) ? $_POST['drive_date'] : null;
    $formData['deadline']         = $_POST['deadline'] ?? '';
    
    $rawStatus                    = $_POST['status'] ?? 'Active';
    $formData['status']           = in_array($rawStatus, ['Active', 'Upcoming', 'Closed'], true) ? $rawStatus : 'Active';
    $formData['skills']           = is_array($_POST['skills'] ?? null) ? $_POST['skills'] : [];

    if ($formData['company_id'] <= 0) $errors[] = 'Please select a hosting company.';
    if (empty($formData['job_title'])) $errors[] = 'Job designation/title is required.';
    if (empty($formData['job_description'])) $errors[] = 'Job description details are required.';
    if (empty($formData['deadline'])) $errors[] = 'Application deadline date is required.';
    if (!empty($formData['drive_date']) && !empty($formData['deadline']) && $formData['deadline'] > $formData['drive_date']) {
        $errors[] = 'Application deadline cannot be after the drive date.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO placement_drives (
                    company_id, job_title, job_description, package_lpa, job_location,
                    min_cgpa, min_attendance, max_backlogs, allowed_branches,
                    drive_date, deadline, status
                ) VALUES (
                    :cid, :title, :descr, :lpa, :loc,
                    :cgpa, :att, :backlogs, :branches,
                    :ddate, :dline, :status
                )
            ");
            $stmt->execute([
                ':cid'      => $formData['company_id'],
                ':title'    => $formData['job_title'],
                ':descr'    => $formData['job_description'],
                ':lpa'      => $formData['package_lpa'],
                ':loc'      => $formData['job_location'],
                ':cgpa'     => $formData['min_cgpa'],
                ':att'      => $formData['min_attendance'],
                ':backlogs' => $formData['max_backlogs'],
                ':branches' => $formData['allowed_branches'],
                ':ddate'    => $formData['drive_date'],
                ':dline'    => $formData['deadline'],
                ':status'   => $formData['status']
            ]);
            $driveId = $pdo->lastInsertId();

            // Insert required skills mapping
            if (!empty($formData['skills']) && is_array($formData['skills'])) {
                $stmtSkill = $pdo->prepare("INSERT INTO drive_skills (drive_id, skill_id) VALUES (:did, :skid)");
                foreach ($formData['skills'] as $skillId) {
                    $stmtSkill->execute([':did' => $driveId, ':skid' => (int)$skillId]);
                }
            }

            $pdo->commit();

            setFlash('success', 'Placement Drive for "' . e($formData['job_title']) . '" created successfully!');
            header('Location: ' . BASE_URL . 'admin/drives.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Drive create error: " . $e->getMessage());
            $errors[] = 'Failed to create drive due to an internal system error.';
        }
    }
}

// Fetch companies & skills
$companies = $pdo->query("SELECT id, name, location FROM companies ORDER BY name ASC")->fetchAll();
$allSkills = $pdo->query("SELECT * FROM skills ORDER BY category ASC, skill_name ASC")->fetchAll();

$pageTitle = 'Create Placement Drive';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>Create Placement Drive</h3>
                    <p class="text-muted mb-0">Post a new campus recruitment drive with automated eligibility thresholds</p>
                </div>
                <a href="<?= BASE_URL ?>admin/drives.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Drives
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Errors encountered:</h6>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>admin/drive_create.php" method="POST" class="needs-validation">
                <?= csrfField() ?>
                <div class="row g-4">
                    <!-- Left: Job Details -->
                    <div class="col-lg-7">
                        <div class="card card-portal">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Job & Company Profile</h5>
                            </div>
                            <div class="card-body p-4">
                                
                                <div class="mb-3">
                                    <label for="company_id" class="form-label fw-semibold">Hosting Company *</label>
                                    <select class="form-select form-select-lg" id="company_id" name="company_id" required>
                                        <option value="">-- Select Partner Company --</option>
                                        <?php foreach ($companies as $comp): ?>
                                            <option value="<?= $comp['id'] ?>" <?= $formData['company_id'] == $comp['id'] ? 'selected' : '' ?>>
                                                <?= e($comp['name']) ?> (<?= e($comp['location']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($companies)): ?>
                                        <small class="text-danger">No companies registered yet. <a href="<?= BASE_URL ?>admin/companies.php">Add a company first</a>.</small>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="job_title" class="form-label fw-semibold">Job Title / Designation *</label>
                                    <input type="text" class="form-control form-control-lg" id="job_title" name="job_title" value="<?= e($formData['job_title']) ?>" placeholder="e.g. Junior Software Engineer, Graduate Trainee" required>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="package_lpa" class="form-label fw-semibold">Salary / CTC (LPA)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.05" min="0" class="form-control" id="package_lpa" name="package_lpa" value="<?= e($formData['package_lpa']) ?>" placeholder="3.50">
                                            <span class="input-group-text">LPA</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="job_location" class="form-label fw-semibold">Job Work Location</label>
                                        <input type="text" class="form-control" id="job_location" name="job_location" value="<?= e($formData['job_location']) ?>" placeholder="e.g. Pune, Bangalore, Hybrid">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="job_description" class="form-label fw-semibold">Job Description & Responsibilities *</label>
                                    <textarea class="form-control" id="job_description" name="job_description" rows="5" placeholder="Detail the role expectations, day-to-day duties, and project assignments..." required><?= e($formData['job_description']) ?></textarea>
                                </div>

                                <!-- Required Skills Checkboxes -->
                                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-stars text-warning me-2"></i>Required Technical Skills</h6>
                                <p class="text-muted small">Select the skills required for candidate eligibility</p>
                                <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                                    <div class="row g-2">
                                        <?php foreach ($allSkills as $sk): ?>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" value="<?= $sk['id'] ?>" id="sk_<?= $sk['id'] ?>" <?= in_array($sk['id'], $formData['skills']) ? 'checked' : '' ?>>
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

                    <!-- Right: Eligibility Criteria & Schedule -->
                    <div class="col-lg-5">
                        <div class="card card-portal mb-4">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-sliders text-primary me-2"></i>Eligibility Criteria</h5>
                            </div>
                            <div class="card-body p-4">
                                
                                <div class="mb-3">
                                    <label for="min_cgpa" class="form-label fw-semibold">Minimum CGPA Required (0 - 10) *</label>
                                    <input type="number" step="0.01" min="0" max="10" class="form-control form-control-lg" id="min_cgpa" name="min_cgpa" value="<?= e($formData['min_cgpa']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="min_attendance" class="form-label fw-semibold">Minimum Attendance % *</label>
                                    <input type="number" step="0.1" min="0" max="100" class="form-control form-control-lg" id="min_attendance" name="min_attendance" value="<?= e($formData['min_attendance']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="max_backlogs" class="form-label fw-semibold">Maximum Allowed Standing Backlogs</label>
                                    <input type="number" min="0" max="10" class="form-control" id="max_backlogs" name="max_backlogs" value="<?= e($formData['max_backlogs']) ?>">
                                    <small class="text-muted">Enter 0 for strict no-backlog criterion.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="allowed_branches" class="form-label fw-semibold">Eligible Branches / Departments</label>
                                    <input type="text" class="form-control" id="allowed_branches" name="allowed_branches" value="<?= e($formData['allowed_branches']) ?>" placeholder="e.g. Computer Engineering, Information Technology or All">
                                    <small class="text-muted">Type <code>All</code> or comma-separated branch names.</small>
                                </div>

                            </div>
                        </div>

                        <!-- Schedule & Publish Status Card -->
                        <div class="card card-portal">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-calendar-check text-primary me-2"></i>Schedule & Status</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="drive_date" class="form-label fw-semibold">Drive / Interview Date</label>
                                    <input type="date" class="form-control" id="drive_date" name="drive_date" value="<?= e($formData['drive_date']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="deadline" class="form-label fw-semibold">Application Deadline *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" value="<?= e($formData['deadline']) ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label for="status" class="form-label fw-semibold">Drive Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Active" <?= $formData['status'] === 'Active' ? 'selected' : '' ?>>Active (Open for Student Applications)</option>
                                        <option value="Upcoming" <?= $formData['status'] === 'Upcoming' ? 'selected' : '' ?>>Upcoming (Announced)</option>
                                        <option value="Closed" <?= $formData['status'] === 'Closed' ? 'selected' : '' ?>>Closed (Applications Locked)</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                    <i class="bi bi-check2-circle me-1"></i> Publish Placement Drive
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
