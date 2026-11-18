<?php
/**
 * Admin: Academic & Attendance Records Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

// Handle Score Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_academics'])) {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . BASE_URL . 'admin/academics.php');
        exit;
    }

    $student_id = (int)($_POST['student_id'] ?? 0);
    $cgpa       = (float)($_POST['cgpa'] ?? 0);
    $attendance = (float)($_POST['attendance_percentage'] ?? 0);
    $backlogs   = (int)($_POST['active_backlogs'] ?? 0);
    $tenth      = (float)($_POST['tenth_percentage'] ?? 0);
    $twelfth    = !empty($_POST['twelfth_percentage']) ? (float)$_POST['twelfth_percentage'] : 0.00;

    // Range checks
    if ($cgpa < 0 || $cgpa > 10 || $attendance < 0 || $attendance > 100 || $tenth < 0 || $tenth > 100 || $backlogs < 0) {
        setFlash('danger', 'Invalid academic score ranges provided. Update aborted.');
        header('Location: ' . BASE_URL . 'admin/academics.php');
        exit;
    }

    if ($student_id > 0) {
        try {
            $stmtUpdate = $pdo->prepare("
                INSERT INTO academic_records (student_id, tenth_percentage, twelfth_percentage, cgpa, active_backlogs, attendance_percentage)
                VALUES (:sid, :tenth, :twelfth, :cgpa, :backlogs, :att)
                ON DUPLICATE KEY UPDATE 
                    tenth_percentage = VALUES(tenth_percentage),
                    twelfth_percentage = VALUES(twelfth_percentage),
                    cgpa = VALUES(cgpa),
                    active_backlogs = VALUES(active_backlogs),
                    attendance_percentage = VALUES(attendance_percentage)
            ");
            $stmtUpdate->execute([
                ':sid'      => $student_id,
                ':tenth'    => $tenth,
                ':twelfth'  => $twelfth,
                ':cgpa'     => $cgpa,
                ':backlogs' => $backlogs,
                ':att'      => $attendance
            ]);
            setFlash('success', 'Academic records updated successfully!');
        } catch (PDOException $e) {
            error_log("Academics update error: " . $e->getMessage());
            setFlash('danger', 'Failed to update academic records due to a system error.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/academics.php');
    exit;
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? ''); // low_attendance | low_cgpa | backlogs

$sql = "
    SELECT s.id as student_id, s.enrollment_no, s.first_name, s.last_name, s.department, s.semester,
           ar.id as record_id, ar.tenth_percentage, ar.twelfth_percentage, 
           ar.cgpa, ar.attendance_percentage, ar.active_backlogs, ar.updated_at
    FROM students s
    LEFT JOIN academic_records ar ON s.id = ar.student_id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE :s1 OR s.last_name LIKE :s2 OR s.enrollment_no LIKE :s3)";
    $term = "%{$search}%";
    $params[':s1'] = $term;
    $params[':s2'] = $term;
    $params[':s3'] = $term;
}

if ($filter === 'low_attendance') {
    $sql .= " AND (ar.attendance_percentage < 75.0 OR ar.attendance_percentage IS NULL)";
} elseif ($filter === 'low_cgpa') {
    $sql .= " AND (ar.cgpa < 6.0 OR ar.cgpa IS NULL)";
} elseif ($filter === 'backlogs') {
    $sql .= " AND ar.active_backlogs > 0";
}

$sql .= " ORDER BY s.enrollment_no ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = 'Academic & Attendance Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Academic & Attendance Management</h3>
                    <p class="text-muted mb-0">Maintain verified student scores, CGPA thresholds, and official attendance quotas</p>
                </div>

                <div class="btn-group btn-group-sm shadow-sm">
                    <a href="<?= BASE_URL ?>admin/academics.php" class="btn <?= empty($filter) ? 'btn-primary' : 'btn-outline-primary' ?>">All Students</a>
                    <a href="<?= BASE_URL ?>admin/academics.php?filter=low_attendance" class="btn <?= $filter === 'low_attendance' ? 'btn-danger' : 'btn-outline-danger' ?>">Low Attendance (&lt;75%)</a>
                    <a href="<?= BASE_URL ?>admin/academics.php?filter=low_cgpa" class="btn <?= $filter === 'low_cgpa' ? 'btn-warning' : 'btn-outline-warning' ?>">Low CGPA (&lt;6.0)</a>
                    <a href="<?= BASE_URL ?>admin/academics.php?filter=backlogs" class="btn <?= $filter === 'backlogs' ? 'btn-secondary' : 'btn-outline-secondary' ?>">With Backlogs</a>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Table Card -->
            <div class="card card-portal">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-table text-primary me-2"></i>Student Scorecard Directory (<?= count($records) ?>)</h5>
                    <div class="w-25">
                        <input type="text" class="form-control form-control-sm" placeholder="Filter table..." data-table-search="academicsTable">
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($records)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-portal align-middle mb-0" id="academicsTable">
                                <thead>
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <th>Branch & Sem</th>
                                        <th>CGPA</th>
                                        <th>Attendance %</th>
                                        <th>Active Backlogs</th>
                                        <th>10th / 12th</th>
                                        <th class="text-end">Quick Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $r): ?>
                                        <tr>
                                            <td><strong class="text-primary"><?= e($r['enrollment_no']) ?></strong></td>
                                            <td><strong class="text-dark"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong></td>
                                            <td><?= e($r['department']) ?> (Sem <?= e($r['semester']) ?>)</td>
                                            <td>
                                                <span class="badge <?= ($r['cgpa'] ?? 0) >= 7.5 ? 'bg-success' : (($r['cgpa'] ?? 0) >= 6.0 ? 'bg-primary' : 'bg-danger') ?> fs-6">
                                                    <?= number_format((float)($r['cgpa'] ?? 0), 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= ($r['attendance_percentage'] ?? 0) >= 75 ? 'bg-success' : 'bg-danger' ?> fs-6">
                                                    <?= number_format((float)($r['attendance_percentage'] ?? 0), 1) ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (($r['active_backlogs'] ?? 0) == 0): ?>
                                                    <span class="badge bg-light text-success border">0 Backlogs</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><?= $r['active_backlogs'] ?> Backlog(s)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                10th: <?= number_format((float)($r['tenth_percentage'] ?? 0), 1) ?>%<br>
                                                12th: <?= !empty($r['twelfth_percentage']) ? number_format((float)$r['twelfth_percentage'], 1) . '%' : 'N/A' ?>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['student_id'] ?>">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal for Student -->
                                        <div class="modal fade" id="editModal<?= $r['student_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="<?= BASE_URL ?>admin/academics.php" method="POST">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="update_academics" value="1">
                                                        <input type="hidden" name="student_id" value="<?= $r['student_id'] ?>">

                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title fw-bold">Update Scores: <?= e($r['first_name'] . ' ' . $r['last_name']) ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <p class="text-muted small mb-3">Roll No: <strong><?= e($r['enrollment_no']) ?></strong> | <?= e($r['department']) ?></p>

                                                            <div class="row g-3 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold">CGPA (0 - 10) *</label>
                                                                    <input type="number" step="0.01" min="0" max="10" name="cgpa" class="form-control form-control-lg" value="<?= number_format((float)($r['cgpa'] ?? 0), 2) ?>" required>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold">Attendance % *</label>
                                                                    <input type="number" step="0.1" min="0" max="100" name="attendance_percentage" class="form-control form-control-lg" value="<?= number_format((float)($r['attendance_percentage'] ?? 0), 1) ?>" required>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Active Backlogs</label>
                                                                <input type="number" min="0" max="20" name="active_backlogs" class="form-control" value="<?= (int)($r['active_backlogs'] ?? 0) ?>">
                                                            </div>

                                                            <div class="row g-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold">10th %</label>
                                                                    <input type="number" step="0.01" min="0" max="100" name="tenth_percentage" class="form-control" value="<?= number_format((float)($r['tenth_percentage'] ?? 0), 2) ?>">
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-semibold">12th / ITI %</label>
                                                                    <input type="number" step="0.01" min="0" max="100" name="twelfth_percentage" class="form-control" value="<?= !empty($r['twelfth_percentage']) ? number_format((float)$r['twelfth_percentage'], 2) : '' ?>">
                                                                </div>
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
                            <i class="bi bi-award fs-1 d-block mb-2"></i>
                            No academic records matching your criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
