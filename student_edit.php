<?php
/**
 * Admin: Edit Student Record
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

// Fetch Student
$stmt = $pdo->prepare("
    SELECT s.*, u.email, u.status as user_status
    FROM students s
    JOIN users u ON s.user_id = u.id
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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    }

    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $enrollment_no = trim($_POST['enrollment_no'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $department    = trim($_POST['department'] ?? '');
    $semester      = (int)($_POST['semester'] ?? 5);
    $gender        = trim($_POST['gender'] ?? 'Male');
    $raw_status    = trim($_POST['user_status'] ?? 'active');
    $user_status   = in_array($raw_status, ['active', 'inactive'], true) ? $raw_status : 'active';
    $new_password  = $_POST['new_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($enrollment_no) || empty($email)) {
        $errors[] = 'Name, enrollment number, and email are required fields.';
    }
    if (!empty($new_password) && strlen($new_password) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }

    if (empty($errors)) {
        // Check duplicate email
        $chk1 = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :uid");
        $chk1->execute([':email' => $email, ':uid' => $student['user_id']]);
        if ($chk1->fetch()) $errors[] = 'Email is already used by another account.';

        // Check duplicate enrollment
        $chk2 = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = :roll AND id != :sid");
        $chk2->execute([':roll' => $enrollment_no, ':sid' => $studentId]);
        if ($chk2->fetch()) $errors[] = 'Enrollment number is already in use by another student.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update user status and email
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmtU = $pdo->prepare("UPDATE users SET email = :email, status = :status, password = :pass WHERE id = :uid");
                $stmtU->execute([':email' => $email, ':status' => $user_status, ':pass' => $hashed, ':uid' => $student['user_id']]);
            } else {
                $stmtU = $pdo->prepare("UPDATE users SET email = :email, status = :status WHERE id = :uid");
                $stmtU->execute([':email' => $email, ':status' => $user_status, ':uid' => $student['user_id']]);
            }

            // Update student profile
            $stmtS = $pdo->prepare("
                UPDATE students 
                SET enrollment_no = :roll, first_name = :fname, last_name = :lname,
                    phone = :phone, department = :dept, semester = :sem, gender = :gender
                WHERE id = :sid
            ");
            $stmtS->execute([
                ':roll'   => $enrollment_no,
                ':fname'  => $first_name,
                ':lname'  => $last_name,
                ':phone'  => $phone,
                ':dept'   => $department,
                ':sem'    => $semester,
                ':gender' => $gender,
                ':sid'    => $studentId
            ]);

            $pdo->commit();

            setFlash('success', 'Student record updated successfully!');
            header('Location: ' . BASE_URL . 'admin/students.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Student update error: " . $e->getMessage());
            $errors[] = 'Database update failed due to an internal error.';
        }
    }
}

$pageTitle = 'Edit Student - ' . e($student['first_name']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Student Profile</h3>
                    <p class="text-muted mb-0">Modify student information, status, and account settings</p>
                </div>
                <a href="<?= BASE_URL ?>admin/students.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
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

            <div class="card card-portal max-w-750">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Student Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>admin/student_edit.php?id=<?= $studentId ?>" method="POST" class="needs-validation">
                        <?= csrfField() ?>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="enrollment_no" class="form-label fw-semibold">Enrollment / Roll No *</label>
                                <input type="text" class="form-control" id="enrollment_no" name="enrollment_no" value="<?= e($student['enrollment_no']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label fw-semibold">Department / Branch *</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="Computer Engineering" <?= $student['department'] === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                                    <option value="Information Technology" <?= $student['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                    <option value="Electronics & Telecommunication" <?= $student['department'] === 'Electronics & Telecommunication' ? 'selected' : '' ?>>Electronics & Telecommunication</option>
                                    <option value="Mechanical Engineering" <?= $student['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                                    <option value="Civil Engineering" <?= $student['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                    <option value="Electrical Engineering" <?= $student['department'] === 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label fw-semibold">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($student['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($student['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($student['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($student['phone']) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="semester" class="form-label fw-semibold">Semester</label>
                                <select class="form-select" id="semester" name="semester">
                                    <?php for ($s = 1; $s <= 6; $s++): ?>
                                        <option value="<?= $s ?>" <?= $student['semester'] == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="gender" class="form-label fw-semibold">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="user_status" class="form-label fw-semibold">Account Status</label>
                                <select class="form-select" id="user_status" name="user_status">
                                    <option value="active" <?= $student['user_status'] === 'active' ? 'selected' : '' ?>>Active (Can Login)</option>
                                    <option value="inactive" <?= $student['user_status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="new_password" class="form-label fw-semibold">Reset Password (Optional)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep existing password">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>admin/students.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Update Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
