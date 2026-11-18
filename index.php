<?php
/**
 * Modern PlacementIndia Style Public Landing & Job Discovery Portal
 * Smart Student Management & Placement Portal V2
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$pageTitle = 'Campus Recruitment & Job Discovery Portal';

// Fetch public stats
$studentCount      = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
$companyCount      = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn() ?: 0;
$activeDrivesCount = $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active'")->fetchColumn() ?: 0;
$placedCount       = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM applications WHERE status = 'Selected'")->fetchColumn() ?: 0;

// Search Query Filters
$searchKeyword = trim($_GET['keyword'] ?? '');
$searchBranch  = trim($_GET['branch'] ?? '');
$searchCity    = trim($_GET['city'] ?? '');

$sql = "
    SELECT d.*, c.name as company_name, c.location as company_location, c.website as company_website
    FROM placement_drives d
    JOIN companies c ON d.company_id = c.id
    WHERE d.status = 'Active' AND d.deadline >= CURDATE()
";
$params = [];

if (!empty($searchKeyword)) {
    $kw = '%' . $searchKeyword . '%';
    $sql .= " AND (d.job_title LIKE ? OR d.job_description LIKE ? OR c.name LIKE ?)";
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}
if (!empty($searchBranch) && $searchBranch !== 'All') {
    $sql .= " AND (d.allowed_branches = 'All' OR d.allowed_branches LIKE ?)";
    $params[] = '%' . $searchBranch . '%';
}
if (!empty($searchCity)) {
    $city = '%' . $searchCity . '%';
    $sql .= " AND (d.job_location LIKE ? OR c.location LIKE ?)";
    $params[] = $city;
    $params[] = $city;
}

$sql .= " ORDER BY d.deadline ASC LIMIT 9";
$stmtDrives = $pdo->prepare($sql);
$stmtDrives->execute($params);
$activeDrives = $stmtDrives->fetchAll();

// Branch wise counts for PlacementIndia category tiles
$csCount   = $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active' AND (allowed_branches = 'All' OR allowed_branches LIKE '%Computer%' OR allowed_branches LIKE '%Information%')")->fetchColumn() ?: 0;
$mechCount = $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active' AND (allowed_branches = 'All' OR allowed_branches LIKE '%Mechanical%')")->fetchColumn() ?: 0;
$civilCount= $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active' AND (allowed_branches = 'All' OR allowed_branches LIKE '%Civil%')")->fetchColumn() ?: 0;
$eceCount  = $pdo->query("SELECT COUNT(*) FROM placement_drives WHERE status = 'Active' AND (allowed_branches = 'All' OR allowed_branches LIKE '%Electronics%' OR allowed_branches LIKE '%Electrical%')")->fetchColumn() ?: 0;

// Top Partner Companies
$featuredCompanies = $pdo->query("SELECT * FROM companies ORDER BY id DESC LIMIT 6")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- PlacementIndia Style Hero & Universal Search Bar -->
<section class="hero-section text-white py-5" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
    <div class="container py-4">
        <div class="row align-items-center mb-4">
            <div class="col-lg-9 mx-auto text-center">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold shadow-sm">
                    <i class="bi bi-briefcase-fill me-1"></i> Official Polytechnic Placement Portal 2026
                </span>
                <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -0.5px;">
                    Find Your Dream Engineering Job & Internship
                </h1>
                <p class="lead text-white-50 mb-4">
                    Connect directly with verified corporate recruiters. Discover branch-specific job drives, compare CTC packages, and apply with your verified diploma credentials.
                </p>
            </div>
        </div>

        <!-- PLACEMENTINDIA STYLE MULTI-FIELD SEARCH BOX -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-lg rounded-4 p-3 bg-white">
                    <form action="<?= BASE_URL ?>index.php#drivesSection" method="GET" class="row g-2 align-items-center">
                        <!-- Keyword -->
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="keyword" class="form-control border-0 ps-0 shadow-none" placeholder="Job title, skills (e.g. Java, AutoCAD)..." value="<?= e($searchKeyword) ?>">
                            </div>
                        </div>

                        <!-- Branch Filter -->
                        <div class="col-md-3 border-start-md">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-mortarboard"></i></span>
                                <select name="branch" class="form-select border-0 ps-0 shadow-none">
                                    <option value="">All Engineering Branches</option>
                                    <option value="Computer Engineering" <?= $searchBranch === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                                    <option value="Information Technology" <?= $searchBranch === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                    <option value="Mechanical Engineering" <?= $searchBranch === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                                    <option value="Civil Engineering" <?= $searchBranch === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                    <option value="Electronics & Communication" <?= $searchBranch === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Communication</option>
                                    <option value="Electrical Engineering" <?= $searchBranch === 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                                </select>
                            </div>
                        </div>

                        <!-- Location / City -->
                        <div class="col-md-3 border-start-md">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="city" class="form-control border-0 ps-0 shadow-none" placeholder="City (e.g. Pune, Delhi)..." value="<?= e($searchCity) ?>">
                            </div>
                        </div>

                        <!-- Search Button -->
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm py-2">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Access Role Buttons -->
        <div class="text-center mt-4">
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="<?= BASE_URL ?>auth/login.php?role=student" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                    <i class="bi bi-person-circle me-1"></i> Student Login
                </a>
                <a href="<?= BASE_URL ?>auth/login.php?role=company" class="btn btn-outline-warning btn-sm px-3 rounded-pill">
                    <i class="bi bi-building me-1"></i> Recruiter Portal
                </a>
                <a href="<?= BASE_URL ?>auth/register_company.php" class="btn btn-success btn-sm px-3 rounded-pill fw-bold">
                    <i class="bi bi-plus-circle me-1"></i> Register as Hiring Partner
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Live Stats Counter Strip -->
<section class="py-4 bg-white border-bottom shadow-sm">
    <div class="container">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-primary mb-0"><?= number_format($studentCount) ?>+</h3>
                <small class="text-muted text-uppercase fw-semibold">Enrolled Candidates</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-success mb-0"><?= number_format($companyCount) ?>+</h3>
                <small class="text-muted text-uppercase fw-semibold">Hiring Companies</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-dark mb-0"><?= number_format($activeDrivesCount) ?></h3>
                <small class="text-muted text-uppercase fw-semibold">Active Recruitment Drives</small>
            </div>
            <div class="col-6 col-md-3">
                <h3 class="fw-bold text-warning mb-0"><?= number_format($placedCount) ?></h3>
                <small class="text-muted text-uppercase fw-semibold">Candidates Placed</small>
            </div>
        </div>
    </div>
</section>

<!-- PLACEMENTINDIA STYLE: JOBS BY INDUSTRY / BRANCH TILES -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary px-3 py-2 text-uppercase fw-bold mb-2">Explore By Discipline</span>
            <h2 class="fw-bold text-dark">Browse Campus Drives by Engineering Branch</h2>
            <p class="text-muted">Directly access verified placement opportunities tailored for your diploma stream</p>
        </div>

        <div class="row g-4">
            <!-- Computer / IT -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= BASE_URL ?>index.php?branch=Computer+Engineering#drivesSection" class="text-decoration-none">
                    <div class="card card-portal h-100 border-0 shadow-sm p-4 text-center rounded-4 hover-lift">
                        <div class="avatar-circle mx-auto mb-3 bg-primary bg-opacity-10 text-primary" style="width: 65px; height: 65px; font-size: 1.8rem;">
                            <i class="bi bi-laptop"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Computer / IT</h5>
                        <p class="text-muted small mb-3">Software, Web, Cloud & App Development</p>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold py-2 px-3 rounded-pill">
                            <?= $csCount ?> Active Drives &rarr;
                        </span>
                    </div>
                </a>
            </div>

            <!-- Mechanical -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= BASE_URL ?>index.php?branch=Mechanical+Engineering#drivesSection" class="text-decoration-none">
                    <div class="card card-portal h-100 border-0 shadow-sm p-4 text-center rounded-4 hover-lift">
                        <div class="avatar-circle mx-auto mb-3 bg-danger bg-opacity-10 text-danger" style="width: 65px; height: 65px; font-size: 1.8rem;">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Mechanical Engg.</h5>
                        <p class="text-muted small mb-3">Design, CAD/CAM, Production & Quality</p>
                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold py-2 px-3 rounded-pill">
                            <?= $mechCount ?> Active Drives &rarr;
                        </span>
                    </div>
                </a>
            </div>

            <!-- Civil -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= BASE_URL ?>index.php?branch=Civil+Engineering#drivesSection" class="text-decoration-none">
                    <div class="card card-portal h-100 border-0 shadow-sm p-4 text-center rounded-4 hover-lift">
                        <div class="avatar-circle mx-auto mb-3 bg-success bg-opacity-10 text-success" style="width: 65px; height: 65px; font-size: 1.8rem;">
                            <i class="bi bi-building"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Civil Engineering</h5>
                        <p class="text-muted small mb-3">Structural, Surveying & Site Execution</p>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold py-2 px-3 rounded-pill">
                            <?= $civilCount ?> Active Drives &rarr;
                        </span>
                    </div>
                </a>
            </div>

            <!-- Electronics & Electrical -->
            <div class="col-md-6 col-lg-3">
                <a href="<?= BASE_URL ?>index.php?branch=Electronics+%26+Communication#drivesSection" class="text-decoration-none">
                    <div class="card card-portal h-100 border-0 shadow-sm p-4 text-center rounded-4 hover-lift">
                        <div class="avatar-circle mx-auto mb-3 bg-warning bg-opacity-10 text-warning" style="width: 65px; height: 65px; font-size: 1.8rem;">
                            <i class="bi bi-cpu"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">ECE & Electrical</h5>
                        <p class="text-muted small mb-3">Embedded, Automation & Circuit Design</p>
                        <span class="badge bg-warning bg-opacity-10 text-dark fw-bold py-2 px-3 rounded-pill">
                            <?= $eceCount ?> Active Drives &rarr;
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Attractive Homepage Resume Builder Showcase Banner (PlacementIndia Style) -->
<section class="py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7 text-white">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-25 text-info border border-info border-opacity-25 mb-3 small fw-bold">
                    <i class="bi bi-stars"></i> FREE PLACEMENT RESUME GENERATOR
                </div>
                <h2 class="display-6 fw-bold mb-3 text-white">
                    Build a High-Impact, ATS-Friendly Resume in 2 Minutes
                </h2>
                <p class="text-white-50 fs-6 mb-4" style="line-height: 1.7;">
                    Stand out to top recruiters with our modern two-column executive resume template. Your college marks, verified technical skills, and profile photo auto-sync seamlessly into an industry-approved PDF.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4 text-white-50 small">
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Two-Column Modern Layout</div>
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Verified CGPA & Skills Sync</div>
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> 1-Click Print & PDF Export</div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (isLoggedIn() && isStudent()): ?>
                        <a href="<?= BASE_URL ?>student/resume_builder.php" class="btn btn-warning btn-lg fw-bold px-4 py-3 shadow">
                            <i class="bi bi-file-earmark-person-fill me-2"></i> Open Resume Builder &rarr;
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-warning btn-lg fw-bold px-4 py-3 shadow">
                            <i class="bi bi-person-plus-fill me-2"></i> Register as Student to Create Resume
                        </a>
                        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-light btn-lg fw-semibold px-4 py-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Student Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Graphic Mockup Preview -->
            <div class="col-lg-5 text-center">
                <div class="bg-white p-3 rounded-4 shadow-lg border border-secondary border-opacity-25 mx-auto" style="max-width: 330px; transform: rotate(1deg);">
                    <div class="d-flex border rounded-3 overflow-hidden text-start shadow-sm" style="font-size: 8px; height: 380px;">
                        <!-- Mockup Left -->
                        <div style="width: 35%; background: #e2e8f0; padding: 12px 8px;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: #1e293b; margin: 0 auto 12px; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div style="font-weight: 800; font-size: 7.5px; color: #0f172a; margin-bottom: 4px;">CONTACT</div>
                            <div style="background: #94a3b8; height: 3px; width: 85%; margin-bottom: 4px; border-radius: 2px;"></div>
                            <div style="background: #94a3b8; height: 3px; width: 65%; margin-bottom: 10px; border-radius: 2px;"></div>
                            
                            <div style="font-weight: 800; font-size: 7.5px; color: #0f172a; margin-bottom: 4px;">SKILLS</div>
                            <div style="background: #2563eb; height: 4px; width: 75%; margin-bottom: 4px; border-radius: 2px;"></div>
                            <div style="background: #2563eb; height: 4px; width: 90%; margin-bottom: 4px; border-radius: 2px;"></div>
                            <div style="background: #2563eb; height: 4px; width: 60%; margin-bottom: 10px; border-radius: 2px;"></div>

                            <div style="font-weight: 800; font-size: 7.5px; color: #0f172a; margin-bottom: 4px;">LANGUAGES</div>
                            <div style="background: #94a3b8; height: 3px; width: 70%; margin-bottom: 3px; border-radius: 2px;"></div>
                        </div>
                        <!-- Mockup Right -->
                        <div style="width: 65%; background: #ffffff;">
                            <div style="background: #1e293b; color: #fff; padding: 14px 10px;">
                                <div style="font-weight: 800; font-size: 11px; letter-spacing: 0.5px;">STUDENT NAME</div>
                                <div style="font-size: 7px; color: #94a3b8; text-transform: uppercase;">SOFTWARE ENGINEER</div>
                            </div>
                            <div style="padding: 12px 10px;">
                                <div style="font-weight: 800; font-size: 7.5px; color: #0f172a; margin-bottom: 4px;">PROFILE SUMMARY</div>
                                <div style="background: #cbd5e1; height: 3px; width: 95%; margin-bottom: 3px; border-radius: 2px;"></div>
                                <div style="background: #cbd5e1; height: 3px; width: 80%; margin-bottom: 10px; border-radius: 2px;"></div>

                                <div style="font-weight: 800; font-size: 7.5px; color: #0f172a; margin-bottom: 4px;">KEY PROJECTS</div>
                                <div style="background: #3b82f6; height: 3px; width: 90%; margin-bottom: 3px; border-radius: 2px;"></div>
                                <div style="background: #cbd5e1; height: 3px; width: 75%; margin-bottom: 10px; border-radius: 2px;"></div>

                                <div style="font-weight: 800; font-size: 7.5px; color: #0f172a; margin-bottom: 4px;">EDUCATION</div>
                                <div style="background: #10b981; height: 3px; width: 85%; margin-bottom: 3px; border-radius: 2px;"></div>
                                <div style="background: #cbd5e1; height: 3px; width: 60%; border-radius: 2px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <small class="fw-bold text-dark" style="font-size: 11px;">
                            <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> Two-Column Executive Resume
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Active Placement Drives Listings -->
<section class="py-5" id="drivesSection">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="bi bi-fire text-danger me-2"></i>Active Campus Recruitment Drives
                </h3>
                <p class="text-muted mb-0">
                    <?php if (!empty($searchKeyword) || !empty($searchBranch) || !empty($searchCity)): ?>
                        Showing filtered search results (<?= count($activeDrives) ?> matching) &bull; <a href="<?= BASE_URL ?>index.php" class="text-danger small">Clear Filters</a>
                    <?php else: ?>
                        Latest hiring announcements with package details and eligibility checklists
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= BASE_URL ?>auth/login.php?role=student" class="btn btn-outline-primary btn-sm fw-bold mt-2 mt-md-0">
                <i class="bi bi-lock me-1"></i> Sign In to Apply
            </a>
        </div>

        <?php if (!empty($activeDrives)): ?>
            <div class="row g-4">
                <?php foreach ($activeDrives as $drive): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-portal h-100 shadow-sm border-0 rounded-4 overflow-hidden d-flex flex-column hover-lift">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-light text-primary border py-1 px-2 fw-semibold">
                                        <i class="bi bi-building me-1"></i><?= e($drive['company_name']) ?>
                                    </span>
                                    <strong class="text-success fs-6">
                                        ₹ <?= number_format((float)$drive['package_lpa'], 2) ?> LPA
                                    </strong>
                                </div>

                                <h5 class="card-title fw-bold text-dark mb-2"><?= e($drive['job_title']) ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.5;">
                                    <?= e(substr($drive['job_description'], 0, 115)) ?>...
                                </p>
                                
                                <div class="bg-light p-3 rounded-3 mb-3 small">
                                    <div class="mb-1"><i class="bi bi-mortarboard me-1 text-primary"></i> <strong>Min CGPA:</strong> <?= e($drive['min_cgpa']) ?></div>
                                    <div class="mb-1"><i class="bi bi-clock-history me-1 text-primary"></i> <strong>Min Attendance:</strong> <?= e($drive['min_attendance']) ?>%</div>
                                    <div class="mb-1"><i class="bi bi-geo-alt me-1 text-secondary"></i> <strong>Location:</strong> <?= e($drive['job_location']) ?></div>
                                    <div><i class="bi bi-calendar-event me-1 text-danger"></i> <strong>Deadline:</strong> <?= formatPortalDate($drive['deadline']) ?></div>
                                </div>

                                <a href="<?= BASE_URL ?>auth/login.php?role=student" class="btn btn-primary btn-sm w-100 fw-bold py-2">
                                    <i class="bi bi-send-check me-1"></i> Check Eligibility & Apply
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5 bg-light rounded-4">
                <i class="bi bi-search display-4 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold text-dark">No Placement Drives Found Matching Your Search</h5>
                <p class="text-muted small mb-3">Try clearing search filters or changing the branch selection.</p>
                <a href="<?= BASE_URL ?>index.php" class="btn btn-primary btn-sm px-4">View All Active Drives</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Featured Companies Carousel / Grid -->
<section class="py-5 bg-white border-top">
    <div class="container text-center">
        <h4 class="fw-bold text-dark mb-4">Top Companies Recruiting From Our Campus</h4>
        <div class="row g-3 justify-content-center">
            <?php foreach ($featuredCompanies as $fc): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="card p-3 border rounded-3 h-100 shadow-sm d-flex justify-content-center align-items-center">
                        <i class="bi bi-building fs-3 text-secondary mb-1"></i>
                        <strong class="small text-dark text-truncate w-100"><?= e($fc['name']) ?></strong>
                        <small class="text-muted" style="font-size: 11px;"><?= e($fc['location']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}
@media (min-width: 768px) {
    .border-start-md {
        border-left: 1px solid #e2e8f0;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
