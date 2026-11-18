<?php
/**
 * Admin Student Management Directory
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

$search = trim($_GET['search'] ?? '');
$deptFilter = trim($_GET['department'] ?? '');
$cgpaFilter = trim($_GET['cgpa_range'] ?? '');

// Build query
$sql = "
    SELECT s.*, u.email, u.status as user_status,
           ar.cgpa, ar.attendance_percentage, ar.active_backlogs,
           (SELECT COUNT(*) FROM student_skills WHERE student_id = s.id) as skill_count,
           (SELECT COUNT(*) FROM applications WHERE student_id = s.id AND status = 'Selected') as placed_offers
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE :s1 OR s.last_name LIKE :s2 OR s.enrollment_no LIKE :s3 OR u.email LIKE :s4)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
    $params[':s4'] = $term;
}

if (!empty($deptFilter)) {
    $sql .= " AND s.department = :dept";
    $params[':dept'] = $deptFilter;
}

if ($cgpaFilter === '7.5') {
    $sql .= " AND ar.cgpa >= 7.5";
} elseif ($cgpaFilter === '6.0') {
    $sql .= " AND ar.cgpa >= 6.0";
} elseif ($cgpaFilter === 'below_6.0') {
    $sql .= " AND (ar.cgpa < 6.0 OR ar.cgpa IS NULL)";
}

$sql .= " ORDER BY s.enrollment_no ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Distinct departments for filter dropdown
$departments = $pdo->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Manage Students';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Student Management</h3>
                    <p class="text-muted mb-0">View, search, filter, and manage student placement profiles and academic records</p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>admin/student_add.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Add New Student
                    </a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search & Filter Card -->
            <div class="card card-portal mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="<?= BASE_URL ?>admin/students.php" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Search Student</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by name, roll no, email..." value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Department</label>
                            <select name="department" class="form-select form-select-sm">
                                <option value="">-- All Departments --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= e($dept) ?>" <?= $deptFilter === $dept ? 'selected' : '' ?>><?= e($dept) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">CGPA Cutoff</label>
                            <select name="cgpa_range" class="form-select form-select-sm">
                                <option value="">-- All CGPA Ranges --</option>
                                <option value="7.5" <?= $cgpaFilter === '7.5' ? 'selected' : '' ?>>First Class (CGPA &ge; 7.5)</option>
                                <option value="6.0" <?= $cgpaFilter === '6.0' ? 'selected' : '' ?>>Eligible (CGPA &ge; 6.0)</option>
                                <option value="below_6.0" <?= $cgpaFilter === 'below_6.0' ? 'selected' : '' ?>>Below 6.0</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <?php if (!empty($search) || !empty($deptFilter) || !empty($cgpaFilter)): ?>
                                <a href="<?= BASE_URL ?>admin/students.php" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students Table Card -->
            <div class="card card-portal">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Enrolled Students (<?= count($students) ?>)</h5>
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Quick filter..." data-table-search="studentsTable">
                        <a href="<?= BASE_URL ?>admin/export_excel.php?type=all_students" class="btn btn-outline-success btn-sm text-nowrap">
                            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export Excel
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-portal align-middle mb-0" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <th>Branch & Sem</th>
                                        <th>CGPA</th>
                                        <th>Attendance</th>
                                        <th>Resume</th>
                                        <th>Placement</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $st): ?>
                                        <tr>
                                            <td><strong class="text-primary"><?= e($st['enrollment_no']) ?></strong></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= e($st['first_name'] . ' ' . $st['last_name']) ?></div>
                                                <small class="text-muted"><?= e($st['email']) ?></small>
                                            </td>
                                            <td>
                                                <div><?= e($st['department']) ?></div>
                                                <small class="text-muted">Sem <?= e($st['semester']) ?></small>
                                            </td>
                                            <td>
                                                <strong class="<?= ($st['cgpa'] ?? 0) >= 7.5 ? 'text-success' : (($st['cgpa'] ?? 0) >= 6.0 ? 'text-dark' : 'text-danger') ?>">
                                                    <?= number_format((float)($st['cgpa'] ?? 0), 2) ?>
                                                </strong>
                                                <?php if (($st['active_backlogs'] ?? 0) > 0): ?>
                                                    <span class="badge bg-danger rounded-pill ms-1" title="Backlogs"><?= $st['active_backlogs'] ?> Backlog</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($st['attendance_percentage'] ?? 0) >= 75 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= number_format((float)($st['attendance_percentage'] ?? 0), 1) ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($st['resume_path']) && file_exists(__DIR__ . '/../' . $st['resume_path'])): ?>
                                                    <a href="<?= e(BASE_URL . $st['resume_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2" title="View PDF Resume">
                                                        <i class="bi bi-file-earmark-pdf-fill"></i> View
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted">No File</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($st['placed_offers'] > 0): ?>
                                                    <span class="badge bg-success"><i class="bi bi-trophy-fill me-1"></i> Placed (<?= $st['placed_offers'] ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unplaced</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>admin/student_view.php?id=<?= $st['id'] ?>" class="btn btn-outline-primary" title="View 360 Profile">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>admin/student_edit.php?id=<?= $st['id'] ?>" class="btn btn-outline-secondary" title="Edit Profile">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="<?= BASE_URL ?>admin/student_delete.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete student <?= e(addslashes($st['first_name'] . ' ' . $st['last_name'])) ?>? All associated data will be deleted permanently.');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="id" value="<?= $st['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete Student">
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
                            <i class="bi bi-people display-4 d-block mb-3 text-secondary"></i>
                            <h5 class="fw-bold text-dark">No Students Found</h5>
                            <p class="small text-muted mb-0">Try changing your search keywords or filter criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
