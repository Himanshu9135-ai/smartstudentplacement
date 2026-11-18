<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

// 1. Core Platform Statistics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$totalCompanies = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn() ?: 0;
$activeDrives = $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active'")->fetchColumn() ?: 0;
$totalApplications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn() ?: 0;
$placedStudents = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM applications WHERE status = 'Selected'")->fetchColumn() ?: 0;

// Placement Rate %
$placementRate = $totalStudents > 0 ? round(($placedStudents / $totalStudents) * 100, 1) : 0;

// 2. Recent Applications
$recentApps = $pdo->query("
    SELECT a.*, s.enrollment_no, s.first_name, s.last_name, s.department,
           d.job_title, c.name as company_name, ar.cgpa, ar.attendance_percentage
    FROM applications a
    JOIN students s ON a.student_id = s.id
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    ORDER BY a.applied_at DESC
    LIMIT 6
")->fetchAll();

// 3. Active Drives Summary
$drivesList = $pdo->query("
    SELECT d.*, c.name as company_name,
           (SELECT COUNT(*) FROM applications WHERE drive_id = d.id) as application_count
    FROM placement_drives d
    JOIN companies c ON d.company_id = c.id
    WHERE d.status = 'Active'
    ORDER BY d.deadline ASC
    LIMIT 4
")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>T&P Admin Dashboard</h3>
                    <p class="text-muted mb-0">Training and Placement Cell Monitoring & Operations Center</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>admin/drive_create.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> New Drive
                    </a>
                    <a href="<?= BASE_URL ?>admin/student_add.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i> Add Student
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Metrics Row -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-people text-primary stat-icon"></i>
                        <span class="stat-label text-muted">Total Students</span>
                        <div class="stat-value text-primary"><?= $totalStudents ?></div>
                        <small class="text-muted"><i class="bi bi-person-check me-1"></i>Enrolled cohort</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-building text-info stat-icon"></i>
                        <span class="stat-label text-muted">Partner Companies</span>
                        <div class="stat-value text-info"><?= $totalCompanies ?></div>
                        <small class="text-muted"><i class="bi bi-briefcase me-1"></i><?= $activeDrives ?> Active Drives</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-send text-warning stat-icon"></i>
                        <span class="stat-label text-muted">Total Applications</span>
                        <div class="stat-value text-warning"><?= $totalApplications ?></div>
                        <small class="text-muted"><i class="bi bi-clock-history me-1"></i>Submitted to drives</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-trophy text-success stat-icon"></i>
                        <span class="stat-label text-muted">Placed Students</span>
                        <div class="stat-value text-success"><?= $placedStudents ?></div>
                        <small class="text-success fw-bold"><i class="bi bi-graph-up me-1"></i><?= $placementRate ?>% Placement Rate</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Recent Applications Table -->
                <div class="col-lg-8">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Drive Applications</h5>
                            <a href="<?= BASE_URL ?>admin/drives.php" class="btn btn-sm btn-outline-primary">Manage Drives</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentApps)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-portal align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Applied Drive</th>
                                                <th>CGPA / Att.</th>
                                                <th>Status</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentApps as $app): ?>
                                                <tr>
                                                    <td>
                                                        <strong class="d-block text-dark"><?= e($app['first_name'] . ' ' . $app['last_name']) ?></strong>
                                                        <small class="text-muted"><?= e($app['enrollment_no']) ?> (<?= e($app['department']) ?>)</small>
                                                    </td>
                                                    <td>
                                                        <strong class="d-block text-dark"><?= e($app['job_title']) ?></strong>
                                                        <small class="text-muted"><?= e($app['company_name']) ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark">CGPA: <?= number_format((float)($app['cgpa'] ?? 0), 2) ?></span>
                                                        <span class="badge bg-light text-dark"><?= number_format((float)($app['attendance_percentage'] ?? 0), 1) ?>%</span>
                                                    </td>
                                                    <td><?= getStatusBadge($app['status']) ?></td>
                                                    <td class="text-end">
                                                        <a href="<?= BASE_URL ?>admin/drive_view.php?id=<?= $app['drive_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil-square"></i> Review
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No student applications submitted yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Active Drives Sidebar Box -->
                <div class="col-lg-4">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-briefcase me-2 text-primary"></i>Ongoing Drives</h5>
                            <a href="<?= BASE_URL ?>admin/drives.php" class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>
                        <div class="card-body p-3">
                            <?php if (!empty($drivesList)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($drivesList as $dr): ?>
                                        <div class="list-group-item px-0 py-2 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 fw-bold text-dark"><?= e($dr['job_title']) ?></h6>
                                                <span class="badge bg-primary rounded-pill"><?= $dr['application_count'] ?> Applied</span>
                                            </div>
                                            <div class="small text-muted mb-1">
                                                <i class="bi bi-building me-1"></i> <?= e($dr['company_name']) ?> | ₹ <?= number_format((float)$dr['package_lpa'], 2) ?> LPA
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center small">
                                                <span class="text-danger"><i class="bi bi-clock me-1"></i> Ends: <?= formatPortalDate($dr['deadline']) ?></span>
                                                <a href="<?= BASE_URL ?>admin/drive_view.php?id=<?= $dr['id'] ?>" class="text-primary fw-bold">Manage &rarr;</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-4 mb-0">No active placement drives.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Navigation Card -->
                    <div class="card bg-light border-0 shadow-sm p-3">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-gear-wide-connected text-primary me-2"></i>Quick Actions</h6>
                        <div class="d-grid gap-2 small">
                            <a href="<?= BASE_URL ?>admin/academics.php" class="btn btn-white btn-sm text-start border shadow-sm">
                                <i class="bi bi-pencil me-1 text-primary"></i> Batch Update CGPA & Attendance
                            </a>
                            <a href="<?= BASE_URL ?>admin/skills_master.php" class="btn btn-white btn-sm text-start border shadow-sm">
                                <i class="bi bi-stars me-1 text-warning"></i> Add Master Technical Skills
                            </a>
                            <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-white btn-sm text-start border shadow-sm">
                                <i class="bi bi-file-earmark-bar-graph me-1 text-success"></i> Download / Print Placement Report
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
