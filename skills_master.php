<?php
/**
 * Admin: Master Skills Repository CRUD
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        setFlash('danger', 'Security validation failed (Invalid CSRF token).');
        header('Location: ' . BASE_URL . 'admin/skills_master.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add_skill') {
        $skill_name = trim($_POST['skill_name'] ?? '');
        $category   = trim($_POST['category'] ?? 'General');

        if (!empty($skill_name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO skills (skill_name, category) VALUES (:name, :cat)");
                $stmt->execute([':name' => $skill_name, ':cat' => $category]);
                setFlash('success', 'Skill "' . e($skill_name) . '" added successfully!');
            } catch (PDOException $e) {
                setFlash('danger', 'Skill name already exists or database error.');
            }
        }
    } elseif ($action === 'edit_skill') {
        $skill_id   = (int)($_POST['skill_id'] ?? 0);
        $skill_name = trim($_POST['skill_name'] ?? '');
        $category   = trim($_POST['category'] ?? 'General');

        if ($skill_id > 0 && !empty($skill_name)) {
            $stmt = $pdo->prepare("UPDATE skills SET skill_name = :name, category = :cat WHERE id = :id");
            $stmt->execute([':name' => $skill_name, ':cat' => $category, ':id' => $skill_id]);
            setFlash('success', 'Skill updated successfully!');
        }
    } elseif ($action === 'delete_skill') {
        $skill_id = (int)($_POST['skill_id'] ?? 0);
        if ($skill_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM skills WHERE id = :id");
            $stmt->execute([':id' => $skill_id]);
            setFlash('info', 'Skill removed from master repository.');
        }
    }

    header('Location: ' . BASE_URL . 'admin/skills_master.php');
    exit;
}

// Fetch all skills with student count
$skills = $pdo->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM student_skills WHERE skill_id = s.id) as student_count,
           (SELECT COUNT(*) FROM drive_skills WHERE skill_id = s.id) as drive_count
    FROM skills s
    ORDER BY s.category ASC, s.skill_name ASC
")->fetchAll();

$pageTitle = 'Master Skills Catalog';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-stars text-primary me-2"></i>Master Skills Catalog</h3>
                    <p class="text-muted mb-0">Manage technical skills used for placement drive criteria and student profiling</p>
                </div>
            </div>

            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="row g-4">
                <!-- Add Skill Form -->
                <div class="col-lg-4">
                    <div class="card card-portal">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add New Skill</h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="<?= BASE_URL ?>admin/skills_master.php" method="POST" class="needs-validation">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add_skill">

                                <div class="mb-3">
                                    <label for="skill_name" class="form-label fw-semibold">Skill Name *</label>
                                    <input type="text" class="form-control" id="skill_name" name="skill_name" placeholder="e.g. Node.js, Spring Boot" required>
                                </div>

                                <div class="mb-4">
                                    <label for="category" class="form-label fw-semibold">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="Programming Language">Programming Language</option>
                                        <option value="Web Development">Web Development</option>
                                        <option value="Database">Database</option>
                                        <option value="Frontend Framework">Frontend Framework</option>
                                        <option value="Backend Framework">Backend Framework</option>
                                        <option value="Dev Tools">Dev Tools & Cloud</option>
                                        <option value="Core Programming">Core Programming</option>
                                        <option value="General">General Technical</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-plus-lg me-1"></i> Add to Repository
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Skills List Table -->
                <div class="col-lg-8">
                    <div class="card card-portal">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-list-check text-primary me-2"></i>Cataloged Skills (<?= count($skills) ?>)</h5>
                            <input type="text" class="form-control form-control-sm w-25" placeholder="Search skills..." data-table-search="skillsTable">
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-portal align-middle mb-0" id="skillsTable">
                                    <thead>
                                        <tr>
                                            <th>Skill Name</th>
                                            <th>Category</th>
                                            <th>Students</th>
                                            <th>Drives Req.</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($skills as $sk): ?>
                                            <tr>
                                                <td><strong class="text-dark"><?= e($sk['skill_name']) ?></strong></td>
                                                <td><span class="badge bg-light text-secondary border"><?= e($sk['category']) ?></span></td>
                                                <td><span class="badge bg-primary rounded-pill"><?= $sk['student_count'] ?> Students</span></td>
                                                <td><span class="badge bg-info text-dark rounded-pill"><?= $sk['drive_count'] ?> Drives</span></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSkillModal<?= $sk['id'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form action="<?= BASE_URL ?>admin/skills_master.php" method="POST" class="d-inline">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete_skill">
                                                        <input type="hidden" name="skill_id" value="<?= $sk['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete skill <?= e($sk['skill_name']) ?>?">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- Edit Skill Modal -->
                                            <div class="modal fade" id="editSkillModal<?= $sk['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="<?= BASE_URL ?>admin/skills_master.php" method="POST">
                                                            <?= csrfField() ?>
                                                            <input type="hidden" name="action" value="edit_skill">
                                                            <input type="hidden" name="skill_id" value="<?= $sk['id'] ?>">

                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title fw-bold">Edit Skill</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Skill Name</label>
                                                                    <input type="text" name="skill_name" class="form-control" value="<?= e($sk['skill_name']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-semibold">Category</label>
                                                                    <input type="text" name="category" class="form-control" value="<?= e($sk['category']) ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary fw-bold">Update Skill</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
