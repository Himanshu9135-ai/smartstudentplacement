<?php
/**
 * Admin: Student 360-Degree Profile View
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

$studentId = (int)($_GET['id'] ?? 0);
if ($studentId <= 0) {
    setFlash('danger', 'Invalid student ID.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// Fetch Student & Academics
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.status as user_status,
           ar.tenth_percentage, ar.twelfth_percentage, ar.cgpa, ar.attendance_percentage, ar.active_backlogs
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $studentId]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Student not found.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

// Fetch Student Skills
$stmtSkills = $pdo->prepare("
    SELECT ss.proficiency, s.skill_name, s.category
    FROM student_skills ss
    JOIN skills s ON ss.skill_id = s.id
    WHERE ss.student_id = :sid
    ORDER BY s.skill_name ASC
");
$stmtSkills->execute([':sid' => $studentId]);
$skills = $stmtSkills->fetchAll();

// Fetch Applications
$stmtApps = $pdo->prepare("
    SELECT a.*, d.job_title, d.package_lpa, d.drive_date, c.name as company_name
    FROM applications a
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    WHERE a.student_id = :sid
    ORDER BY a.applied_at DESC
");
$stmtApps->execute([':sid' => $studentId]);
$applications = $stmtApps->fetchAll();

$pageTitle = e($student['first_name'] . ' ' . $student['last_name']) . ' - Student Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i>Student 360&deg; Profile</h3>
                    <p class="text-muted mb-0"><?= e($student['first_name'] . ' ' . $student['last_name']) ?> (<?= e($student['enrollment_no']) ?>)</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>admin/student_edit.php?id=<?= $studentId ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </a>
                    <a href="<?= BASE_URL ?>admin/students.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Directory
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4 mb-4">
                <!-- Left Column: Personal Info Card -->
                <div class="col-lg-4">
                    <div class="card card-portal text-center p-4">
                        <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?= e(strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1))) ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                        <p class="text-muted small mb-2"><?= e($student['enrollment_no']) ?></p>
                        <span class="badge bg-primary mb-3"><?= e($student['department']) ?></span>

                        <div class="border-top pt-3 text-start small">
                            <div class="mb-2"><i class="bi bi-envelope text-primary me-2"></i><strong>Email:</strong> <?= e($student['email']) ?></div>
                            <div class="mb-2"><i class="bi bi-telephone text-primary me-2"></i><strong>Phone:</strong> <?= e($student['phone'] ?? 'N/A') ?></div>
                            <div class="mb-2"><i class="bi bi-calendar3 text-primary me-2"></i><strong>Semester:</strong> <?= e($student['semester']) ?></div>
                            <div class="mb-2"><i class="bi bi-gender-ambiguous text-primary me-2"></i><strong>Gender:</strong> <?= e($student['gender'] ?? 'N/A') ?></div>
                            <div class="mb-2"><i class="bi bi-geo-alt text-primary me-2"></i><strong>Address:</strong> <?= e($student['address'] ?? 'N/A') ?></div>
                            <div>
                                <i class="bi bi-shield-check text-primary me-2"></i><strong>Account:</strong> 
                                <span class="badge <?= $student['user_status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($student['user_status']) ?></span>
                            </div>
                        </div>

                        <!-- Resume Box -->
                        <div class="border-top mt-3 pt-3">
                            <h6 class="fw-bold mb-2 small text-uppercase text-muted">Curriculum Vitae</h6>
                            <?php if (!empty($student['resume_path']) && file_exists(__DIR__ . '/../' . $student['resume_path'])): ?>
                                <a href="<?= e(BASE_URL . $student['resume_path']) ?>" target="_blank" class="btn btn-danger btn-sm w-100">
                                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> View / Download PDF
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-danger p-2 d-block">No PDF Resume Uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Academics & Skills -->
                <div class="col-lg-8">
                    <!-- Academic Scores Card -->
                    <div class="card card-portal mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-award text-primary me-2"></i>Academic & Eligibility Scores</h5>
                            <a href="<?= BASE_URL ?>admin/academics.php?search=<?= urlencode($student['enrollment_no']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Update Scores
                            </a>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 text-center mb-3">
                                <div class="col-sm-4">
                                    <div class="p-3 bg-light rounded border">
                                        <span class="text-muted small d-block">Overall CGPA</span>
                                        <h3 class="fw-bold text-primary mb-0"><?= number_format((float)($student['cgpa'] ?? 0), 2) ?></h3>
                                        <small class="text-muted">Scale of 10.0</small>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="p-3 bg-light rounded border">
                                        <span class="text-muted small d-block">Attendance Rate</span>
                                        <h3 class="fw-bold <?= ($student['attendance_percentage'] ?? 0) >= 75 ? 'text-success' : 'text-danger' ?> mb-0">
                                            <?= number_format((float)($student['attendance_percentage'] ?? 0), 1) ?>%
                                        </h3>
                                        <small class="text-muted">Min 75% required</small>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="p-3 bg-light rounded border">
                                        <span class="text-muted small d-block">Standing Backlogs</span>
                                        <h3 class="fw-bold <?= ($student['active_backlogs'] ?? 0) === 0 ? 'text-success' : 'text-danger' ?> mb-0">
                                            <?= (int)($student['active_backlogs'] ?? 0) ?>
                                        </h3>
                                        <small class="text-muted">Active backlogs</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 small text-muted">
                                <div class="col-6"><strong>10th Standard %:</strong> <?= number_format((float)($student['tenth_percentage'] ?? 0), 2) ?>%</div>
                                <div class="col-6"><strong>12th / ITI %:</strong> <?= !empty($student['twelfth_percentage']) ? number_format((float)$student['twelfth_percentage'], 2) . '%' : 'N/A' ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Skills Card -->
                    <div class="card card-portal mb-4">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-stars text-warning me-2"></i>Acquired Technical Skills (<?= count($skills) ?>)</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if (!empty($skills)): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($skills as $sk): ?>
                                        <span class="badge bg-light text-dark border p-2 fs-6">
                                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                                            <?= e($sk['skill_name']) ?> 
                                            <span class="badge bg-secondary ms-1"><?= e($sk['proficiency']) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">No technical skills added yet by the student.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application History Table -->
            <div class="card card-portal">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Placement Drives & Applications (<?= count($applications) ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($applications)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-portal align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Role / Title</th>
                                        <th>Package</th>
                                        <th>Applied On</th>
                                        <th>Status</th>
                                        <th>Admin Remarks</th>
                                        <th class="text-end">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td><strong><?= e($app['company_name']) ?></strong></td>
                                            <td><?= e($app['job_title']) ?></td>
                                            <td><span class="badge bg-light text-dark">₹ <?= number_format((float)$app['package_lpa'], 2) ?> LPA</span></td>
                                            <td><?= formatPortalDate($app['applied_at']) ?></td>
                                            <td><?= getStatusBadge($app['status']) ?></td>
                                            <td><small class="text-muted"><?= e($app['admin_notes'] ?? 'None') ?></small></td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>admin/drive_view.php?id=<?= $app['drive_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-sliders"></i> Update Status
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                            Student has not applied to any placement drives yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
