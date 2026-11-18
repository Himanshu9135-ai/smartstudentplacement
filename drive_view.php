<?php
/**
 * Admin: Drive Review & Candidate Evaluation Center
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
$stmt = $pdo->prepare("
    SELECT d.*, c.name as company_name, c.location as company_location, c.website as company_website,
           c.contact_email, c.contact_phone
    FROM placement_drives d
    JOIN companies c ON d.company_id = c.id
    WHERE d.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $driveId]);
$drive = $stmt->fetch();

if (!$drive) {
    setFlash('danger', 'Drive not found.');
    header('Location: ' . BASE_URL . 'admin/drives.php');
    exit;
}

// Fetch Required Skills
$requiredSkills = getDriveRequiredSkills($pdo, $driveId);

// 1. Fetch Submitted Applications
$stmtApps = $pdo->prepare("
    SELECT a.*, s.id as student_id, s.enrollment_no, s.first_name, s.last_name, s.department, s.semester,
           s.phone, s.resume_path, u.email,
           ar.cgpa, ar.attendance_percentage, ar.active_backlogs
    FROM applications a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE a.drive_id = :did
    ORDER BY a.applied_at DESC
");
$stmtApps->execute([':did' => $driveId]);
$applications = $stmtApps->fetchAll();

// 2. Automated Eligibility Scan for ALL Students
$allStudents = $pdo->query("
    SELECT s.id, s.enrollment_no, s.first_name, s.last_name, s.department, s.phone, s.resume_path,
           ar.cgpa, ar.attendance_percentage, ar.active_backlogs
    FROM students s
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    ORDER BY ar.cgpa DESC
")->fetchAll();

$eligiblePool = [];
$appliedStudentIds = array_column($applications, 'student_id');

foreach ($allStudents as $st) {
    $res = checkStudentEligibility($pdo, $st['id'], $driveId);
    if ($res['is_eligible']) {
        $st['has_applied'] = in_array($st['id'], $appliedStudentIds);
        $eligiblePool[] = $st;
    }
}

$pageTitle = 'Review Drive - ' . e($drive['job_title']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/drives.php">Placement Drives</a></li>
                            <li class="breadcrumb-item active"><?= e($drive['job_title']) ?></li>
                        </ol>
                    </nav>
                    <h3 class="fw-bold mb-0"><i class="bi bi-briefcase-fill text-primary me-2"></i><?= e($drive['job_title']) ?></h3>
                    <small class="text-muted"><i class="bi bi-building me-1"></i><?= e($drive['company_name']) ?> &bull; ₹ <?= number_format((float)$drive['package_lpa'], 2) ?> LPA</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>admin/drive_edit.php?id=<?= $driveId ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Drive
                    </a>
                    <a href="<?= BASE_URL ?>admin/drives.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Drives
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Drive Overview Card -->
            <div class="card card-portal mb-4">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <h6 class="fw-bold text-dark mb-2">Role Overview & Required Criteria</h6>
                            <p class="text-secondary small mb-3"><?= nl2br(e($drive['job_description'])) ?></p>
                            
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark border p-2">
                                    <i class="bi bi-mortarboard text-primary me-1"></i> Min CGPA: <strong><?= e($drive['min_cgpa']) ?></strong>
                                </span>
                                <span class="badge bg-light text-dark border p-2">
                                    <i class="bi bi-clock-history text-primary me-1"></i> Min Attendance: <strong><?= e($drive['min_attendance']) ?>%</strong>
                                </span>
                                <span class="badge bg-light text-dark border p-2">
                                    <i class="bi bi-x-octagon text-danger me-1"></i> Max Backlogs: <strong><?= e($drive['max_backlogs']) ?></strong>
                                </span>
                                <span class="badge bg-light text-dark border p-2">
                                    <i class="bi bi-diagram-3 text-info me-1"></i> Branches: <strong><?= e($drive['allowed_branches']) ?></strong>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-4 border-start-md ps-md-4">
                            <h6 class="fw-bold text-dark mb-2">Key Dates & Status</h6>
                            <div class="small mb-2"><strong>Drive Date:</strong> <?= formatPortalDate($drive['drive_date']) ?></div>
                            <div class="small mb-2"><strong>Deadline:</strong> <span class="text-danger fw-bold"><?= formatPortalDate($drive['deadline']) ?></span></div>
                            <div class="small mb-3"><strong>Status:</strong> <span class="badge bg-success"><?= e($drive['status']) ?></span></div>

                            <h6 class="fw-bold text-dark mb-2 small text-uppercase text-muted">Required Skills</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (!empty($requiredSkills)): ?>
                                    <?php foreach ($requiredSkills as $rsk): ?>
                                        <span class="badge bg-primary"><?= e($rsk['skill_name']) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">None specified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed View: Received Applications vs Eligible Candidates -->
            <ul class="nav nav-tabs mb-4" id="driveTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="apps-tab" data-bs-toggle="tab" data-bs-target="#apps-pane" type="button">
                        <i class="bi bi-send-check-fill me-1 text-primary"></i> Received Applications (<?= count($applications) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="pool-tab" data-bs-toggle="tab" data-bs-target="#pool-pane" type="button">
                        <i class="bi bi-patch-check-fill me-1 text-success"></i> Automated Eligible Student Pool (<?= count($eligiblePool) ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="driveTabContent">
                
                <!-- TAB 1: Applications Received -->
                <div class="tab-pane fade show active" id="apps-pane" role="tabpanel">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>Candidates Applied (<?= count($applications) ?>)</h5>
                            <input type="text" class="form-control form-control-sm w-25" placeholder="Search applicants..." data-table-search="applicantsTable">
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($applications)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-portal align-middle mb-0" id="applicantsTable">
                                        <thead>
                                            <tr>
                                                <th>Candidate</th>
                                                <th>Branch</th>
                                                <th>CGPA / Att.</th>
                                                <th>Applied Date</th>
                                                <th>Resume</th>
                                                <th>Stage</th>
                                                <th>Remarks</th>
                                                <th class="text-end">Update Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($applications as $app): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="d-block text-dark"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong>
                                                        <small class="text-primary fw-bold"><?= e($app['enrollment_no']) ?></small>
                                                    </td>
                                                    <td><?= e($app['department']) ?></td>
                                                    <td>
                                                        <strong class="text-success"><?= number_format((float)($app['cgpa'] ?? 0), 2) ?></strong>
                                                        <small class="text-muted d-block"><?= number_format((float)($app['attendance_percentage'] ?? 0), 1) ?>% Att.</small>
                                                    </td>
                                                    <td><?= formatPortalDate($app['applied_at']) ?></td>
                                                    <td>
                                                        <?php if (!empty($app['resume_path']) && file_exists(__DIR__ . '/../' . $app['resume_path'])): ?>
                                                            <a href="<?= e(BASE_URL . $app['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2">
                                                                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">None</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= getStatusBadge($app['status']) ?></td>
                                                    <td><small class="text-muted"><?= e($app['admin_notes'] ?? 'None') ?></small></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?= $app['id'] ?>">
                                                            <i class="bi bi-sliders me-1"></i> Update
                                                        </button>
                                                    </td>
                                                </tr>

                                                <!-- Application Status Modal -->
                                                <div class="modal fade" id="statusModal<?= $app['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="<?= BASE_URL ?>admin/application_status.php" method="POST">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                                <input type="hidden" name="drive_id" value="<?= $driveId ?>">

                                                                <div class="modal-header bg-primary text-white">
                                                                    <h5 class="modal-title fw-bold">Update Status: <?= e($app['first_name'] . ' ' . $app['last_name']) ?></h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-4">
                                                                    <div class="mb-3">
                                                                        <label for="status_<?= $app['id'] ?>" class="form-label fw-semibold">Application Stage *</label>
                                                                        <select name="status" id="status_<?= $app['id'] ?>" class="form-select form-select-lg">
                                                                            <option value="Applied" <?= $app['status'] === 'Applied' ? 'selected' : '' ?>>Applied (Initial Submission)</option>
                                                                            <option value="Shortlisted" <?= $app['status'] === 'Shortlisted' ? 'selected' : '' ?>>Shortlisted (Profile Cleared)</option>
                                                                            <option value="Interview" <?= $app['status'] === 'Interview' ? 'selected' : '' ?>>Interview Scheduled</option>
                                                                            <option value="Selected" <?= $app['status'] === 'Selected' ? 'selected' : '' ?>>Selected (Job Offer Extended)</option>
                                                                            <option value="Rejected" <?= $app['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected (Did Not Clear)</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label for="admin_notes_<?= $app['id'] ?>" class="form-label fw-semibold">TPO Remarks / Venue / Interview Timing</label>
                                                                        <textarea name="admin_notes" id="admin_notes_<?= $app['id'] ?>" class="form-control" rows="3" placeholder="e.g. Interview scheduled on 24th Oct, 10:30 AM in Seminar Hall 2"><?= e($app['admin_notes']) ?></textarea>
                                                                        <small class="text-muted">These remarks will be visible to the student in their tracker.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary fw-bold">Save Status</button>
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
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No student applications submitted yet for this drive.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Eligible Pool (Automated) -->
                <div class="tab-pane fade" id="pool-pane" role="tabpanel">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 fw-bold"><i class="bi bi-magic text-success me-2"></i>Automated Eligible Candidates Pool (<?= count($eligiblePool) ?>)</h5>
                                <small class="text-muted">Students across all departments meeting CGPA (<?= $drive['min_cgpa'] ?>), Attendance (<?= $drive['min_attendance'] ?>%), and Skills criteria</small>
                            </div>
                            <input type="text" class="form-control form-control-sm w-25" placeholder="Search eligible pool..." data-table-search="poolTable">
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($eligiblePool)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-portal align-middle mb-0" id="poolTable">
                                        <thead>
                                            <tr>
                                                <th>Roll No</th>
                                                <th>Student Name</th>
                                                <th>Department</th>
                                                <th>CGPA</th>
                                                <th>Attendance</th>
                                                <th>Resume</th>
                                                <th>Application Status</th>
                                                <th class="text-end">Student Profile</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($eligiblePool as $ep): ?>
                                                <tr>
                                                    <td><strong class="text-primary"><?= e($ep['enrollment_no']) ?></strong></td>
                                                    <td><strong class="text-dark"><?= e($ep['first_name'] . ' ' . $ep['last_name']) ?></strong></td>
                                                    <td><?= e($ep['department']) ?></td>
                                                    <td><span class="badge bg-success fs-6"><?= number_format((float)($ep['cgpa'] ?? 0), 2) ?></span></td>
                                                    <td><span class="badge bg-light text-dark border"><?= number_format((float)($ep['attendance_percentage'] ?? 0), 1) ?>%</span></td>
                                                    <td>
                                                        <?php if (!empty($ep['resume_path']) && file_exists(__DIR__ . '/../' . $ep['resume_path'])): ?>
                                                            <a href="<?= e(BASE_URL . $ep['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2">
                                                                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">No File</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($ep['has_applied']): ?>
                                                            <span class="badge bg-primary"><i class="bi bi-check-circle-fill me-1"></i> Applied</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Not Applied Yet</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="<?= BASE_URL ?>admin/student_view.php?id=<?= $ep['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> View 360 Profile
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-slash-circle fs-1 d-block mb-2"></i>
                                    No students currently match the strict eligibility criteria for this drive.
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
