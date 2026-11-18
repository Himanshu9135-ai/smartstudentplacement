<?php
/**
 * Admin: Placement Statistics & Reports Generator
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

// 1. Overall Metrics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$placedStudents = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM applications WHERE status = 'Selected'")->fetchColumn() ?: 0;
$totalOffers = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'Selected'")->fetchColumn() ?: 0;
$maxPackage = $pdo->query("SELECT MAX(d.package_lpa) FROM applications a JOIN placement_drives d ON a.drive_id = d.id WHERE a.status = 'Selected'")->fetchColumn() ?: 0.00;
$avgPackage = $pdo->query("SELECT AVG(d.package_lpa) FROM applications a JOIN placement_drives d ON a.drive_id = d.id WHERE a.status = 'Selected'")->fetchColumn() ?: 0.00;
$placementRate = $totalStudents > 0 ? round(($placedStudents / $totalStudents) * 100, 1) : 0;

// 2. Department-wise Placement Breakdown
$deptStats = $pdo->query("
    SELECT s.department,
           COUNT(DISTINCT s.id) as total_students,
           COUNT(DISTINCT CASE WHEN a.status = 'Selected' THEN s.id END) as placed_students
    FROM students s
    LEFT JOIN applications a ON s.id = a.student_id
    GROUP BY s.department
    ORDER BY placed_students DESC
")->fetchAll();

// 3. Company-wise Recruitment Breakdown
$companyStats = $pdo->query("
    SELECT c.name as company_name, c.location,
           COUNT(DISTINCT d.id) as total_drives,
           COUNT(CASE WHEN a.status = 'Selected' THEN 1 END) as hires_count,
           MAX(d.package_lpa) as highest_ctc
    FROM companies c
    LEFT JOIN placement_drives d ON c.id = d.company_id
    LEFT JOIN applications a ON d.id = a.drive_id
    GROUP BY c.id
    ORDER BY hires_count DESC, c.name ASC
")->fetchAll();

// 4. Placed Candidates Master Honor List
$placedList = $pdo->query("
    SELECT a.*, s.enrollment_no, s.first_name, s.last_name, s.department, s.phone,
           d.job_title, d.package_lpa, c.name as company_name
    FROM applications a
    JOIN students s ON a.student_id = s.id
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    WHERE a.status = 'Selected'
    ORDER BY d.package_lpa DESC, s.enrollment_no ASC
")->fetchAll();

$pageTitle = 'Placement Statistics & Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Placement Reports & Analytics</h3>
                    <p class="text-muted mb-0">Official polytechnic campus placement statistics and recruiter metrics</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>admin/export_excel.php?type=placed_students" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export to Excel
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-printer me-1"></i> Print Report
                    </button>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Executive KPI Row -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-people text-primary stat-icon"></i>
                        <span class="stat-label text-muted">Eligible Students</span>
                        <div class="stat-value text-primary"><?= $totalStudents ?></div>
                        <small class="text-muted">Enrolled candidates</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-trophy text-success stat-icon"></i>
                        <span class="stat-label text-muted">Placed Students</span>
                        <div class="stat-value text-success"><?= $placedStudents ?></div>
                        <small class="text-success fw-bold"><?= $placementRate ?>% Placement Success</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-award text-warning stat-icon"></i>
                        <span class="stat-label text-muted">Highest CTC</span>
                        <div class="stat-value text-warning">₹ <?= number_format((float)$maxPackage, 2) ?></div>
                        <small class="text-muted">Lakhs Per Annum</small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card bg-white p-3">
                        <i class="bi bi-graph-up text-info stat-icon"></i>
                        <span class="stat-label text-muted">Average CTC</span>
                        <div class="stat-value text-info">₹ <?= number_format((float)$avgPackage, 2) ?></div>
                        <small class="text-muted">LPA Mean Package</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Department Breakdown -->
                <div class="col-lg-6">
                    <div class="card card-portal h-100">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i>Department-wise Placement Ratio</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-portal align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Department / Branch</th>
                                            <th>Registered</th>
                                            <th>Placed</th>
                                            <th>Placement Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deptStats as $ds): ?>
                                            <?php $dRate = $ds['total_students'] > 0 ? round(($ds['placed_students'] / $ds['total_students']) * 100, 1) : 0; ?>
                                            <tr>
                                                <td><strong class="text-dark"><?= e($ds['department']) ?></strong></td>
                                                <td><?= $ds['total_students'] ?></td>
                                                <td><strong class="text-success"><?= $ds['placed_students'] ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                            <div class="progress-bar bg-success" style="width: <?= $dRate ?>%;"></div>
                                                        </div>
                                                        <span class="small fw-bold"><?= $dRate ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Company-wise Hiring Summary -->
                <div class="col-lg-6">
                    <div class="card card-portal h-100">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-building text-primary me-2"></i>Company Recruitment Summary</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-portal align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Company</th>
                                            <th>Drives</th>
                                            <th>Total Selections</th>
                                            <th>Max Package</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($companyStats as $cs): ?>
                                            <tr>
                                                <td><strong class="text-dark"><?= e($cs['company_name']) ?></strong></td>
                                                <td><span class="badge bg-light text-dark"><?= $cs['total_drives'] ?> Drives</span></td>
                                                <td><strong class="text-success"><?= $cs['hires_count'] ?> Hires</strong></td>
                                                <td>₹ <?= number_format((float)($cs['highest_ctc'] ?? 0), 2) ?> LPA</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Placed Students Honor List Table -->
            <div class="card card-portal">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-trophy-fill text-warning me-2"></i>Placed Candidates Honor Roll (<?= count($placedList) ?>)</h5>
                    <input type="text" class="form-control form-control-sm w-25" placeholder="Search placed students..." data-table-search="placedTable">
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($placedList)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-portal align-middle mb-0" id="placedTable">
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <th>Department</th>
                                        <th>Placed Company</th>
                                        <th>Designation</th>
                                        <th>Package (LPA)</th>
                                        <th>Offer Date</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($placedList as $pl): ?>
                                        <tr>
                                            <td><strong class="text-primary"><?= e($pl['enrollment_no']) ?></strong></td>
                                            <td><strong class="text-dark"><?= e($pl['first_name'] . ' ' . $pl['last_name']) ?></strong></td>
                                            <td><?= e($pl['department']) ?></td>
                                            <td><span class="badge bg-light text-dark"><i class="bi bi-building me-1"></i><?= e($pl['company_name']) ?></span></td>
                                            <td><?= e($pl['job_title']) ?></td>
                                            <td><strong class="text-success">₹ <?= number_format((float)$pl['package_lpa'], 2) ?> LPA</strong></td>
                                            <td><?= formatPortalDate($pl['applied_at']) ?></td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>admin/offer_letter.php?id=<?= $pl['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Generate Official Offer Letter">
                                                    <i class="bi bi-file-earmark-check-fill me-1"></i> Offer Letter
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-trophy display-4 d-block mb-3 text-secondary"></i>
                            <h5 class="fw-bold text-dark">No Selected Offers Yet</h5>
                            <p class="small text-muted mb-0">Selections will appear here once the TPO updates application status to "Selected".</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
