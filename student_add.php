<?php
/**
 * Admin: Add New Student Record
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

$errors = [];
$formData = [
    'enrollment_no' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'department' => 'Computer Engineering',
    'semester' => 5,
    'gender' => 'Male',
    'dob' => '',
    'tenth_percentage' => '80.00',
    'twelfth_percentage' => '75.00',
    'cgpa' => '7.50',
    'attendance_percentage' => '85.00',
    'active_backlogs' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Security validation failed (Invalid CSRF token). Please try again.';
    }

    $formData['enrollment_no']        = trim($_POST['enrollment_no'] ?? '');
    $formData['first_name']           = trim($_POST['first_name'] ?? '');
    $formData['last_name']            = trim($_POST['last_name'] ?? '');
    $formData['email']                = trim($_POST['email'] ?? '');
    $formData['phone']                = trim($_POST['phone'] ?? '');
    $formData['department']           = trim($_POST['department'] ?? 'Computer Engineering');
    $formData['semester']             = (int)($_POST['semester'] ?? 5);
    $formData['gender']               = trim($_POST['gender'] ?? 'Male');
    $formData['dob']                  = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $formData['tenth_percentage']     = (float)($_POST['tenth_percentage'] ?? 0);
    $formData['twelfth_percentage']   = !empty($_POST['twelfth_percentage']) ? (float)$_POST['twelfth_percentage'] : 0.00;
    $formData['cgpa']                 = (float)($_POST['cgpa'] ?? 0);
    $formData['attendance_percentage'] = (float)($_POST['attendance_percentage'] ?? 0);
    $formData['active_backlogs']      = (int)($_POST['active_backlogs'] ?? 0);
    $password                         = $_POST['password'] ?? '';

    if (empty($formData['enrollment_no'])) $errors[] = 'Enrollment number is required.';
    if (empty($formData['first_name']))    $errors[] = 'First name is required.';
    if (empty($formData['last_name']))     $errors[] = 'Last name is required.';
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Initial password must be at least 8 characters long.';
    }
    if ($formData['cgpa'] < 0 || $formData['cgpa'] > 10) {
        $errors[] = 'CGPA must be between 0.00 and 10.00.';
    }
    if ($formData['attendance_percentage'] < 0 || $formData['attendance_percentage'] > 100) {
        $errors[] = 'Attendance percentage must be between 0 and 100.';
    }
    if ($formData['tenth_percentage'] < 0 || $formData['tenth_percentage'] > 100) {
        $errors[] = '10th percentage must be between 0 and 100.';
    }
    if ($formData['active_backlogs'] < 0) {
        $errors[] = 'Active backlogs cannot be negative.';
    }

    if (empty($errors)) {
        // Check duplicate email
        $chk1 = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $chk1->execute([':email' => $formData['email']]);
        if ($chk1->fetch()) $errors[] = 'Email is already registered.';

        // Check duplicate enrollment
        $chk2 = $pdo->prepare("SELECT id FROM students WHERE enrollment_no = :roll");
        $chk2->execute([':roll' => $formData['enrollment_no']]);
        if ($chk2->fetch()) $errors[] = 'Enrollment number is already in use.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (:email, :pass, 'student', 'active')");
            $stmtUser->execute([':email' => $formData['email'], ':pass' => $hashed]);
            $userId = $pdo->lastInsertId();

            $stmtStudent = $pdo->prepare("
                INSERT INTO students (user_id, enrollment_no, first_name, last_name, phone, department, semester, gender, dob)
                VALUES (:uid, :roll, :fname, :lname, :phone, :dept, :sem, :gender, :dob)
            ");
            $stmtStudent->execute([
                ':uid'    => $userId,
                ':roll'   => $formData['enrollment_no'],
                ':fname'  => $formData['first_name'],
                ':lname'  => $formData['last_name'],
                ':phone'  => $formData['phone'],
                ':dept'   => $formData['department'],
                ':sem'    => $formData['semester'],
                ':gender' => $formData['gender'],
                ':dob'    => $formData['dob']
            ]);
            $studentId = $pdo->lastInsertId();

            $stmtAcad = $pdo->prepare("
                INSERT INTO academic_records (student_id, tenth_percentage, twelfth_percentage, cgpa, active_backlogs, attendance_percentage)
                VALUES (:sid, :tenth, :twelfth, :cgpa, :backlogs, :att)
            ");
            $stmtAcad->execute([
                ':sid'      => $studentId,
                ':tenth'    => $formData['tenth_percentage'],
                ':twelfth'  => $formData['twelfth_percentage'],
                ':cgpa'     => $formData['cgpa'],
                ':backlogs' => $formData['active_backlogs'],
                ':att'      => $formData['attendance_percentage']
            ]);

            $pdo->commit();

            setFlash('success', 'Student ' . e($formData['first_name'] . ' ' . $formData['last_name']) . ' added successfully!');
            header('Location: ' . BASE_URL . 'admin/students.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Student add error: " . $e->getMessage());
            $errors[] = 'Failed to create student due to an internal system error.';
        }
    }
}

$pageTitle = 'Add New Student';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-container">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="portal-content">
        <div class="container-fluid p-0">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1"><i class="bi bi-person-plus-fill text-primary me-2"></i>Add Student Record</h3>
                    <p class="text-muted mb-0">Register a student and initialize academic profile</p>
                </div>
                <a href="<?= BASE_URL ?>admin/students.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Student List
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

            <form action="<?= BASE_URL ?>admin/student_add.php" method="POST" class="needs-validation">
                <?= csrfField() ?>
                <div class="row g-4">
                    <!-- Personal Info Card -->
                    <div class="col-lg-7">
                        <div class="card card-portal">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-person me-2 text-primary"></i>Personal & Account Information</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="enrollment_no" class="form-label fw-semibold">Enrollment / Roll No *</label>
                                        <input type="text" class="form-control" id="enrollment_no" name="enrollment_no" value="<?= e($formData['enrollment_no']) ?>" placeholder="e.g. CSE2024099" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department" class="form-label fw-semibold">Department / Branch *</label>
                                        <select class="form-select" id="department" name="department" required>
                                            <option value="Computer Engineering" <?= $formData['department'] === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                                            <option value="Information Technology" <?= $formData['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                            <option value="Electronics & Telecommunication" <?= $formData['department'] === 'Electronics & Telecommunication' ? 'selected' : '' ?>>Electronics & Telecommunication</option>
                                            <option value="Mechanical Engineering" <?= $formData['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                                            <option value="Civil Engineering" <?= $formData['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                            <option value="Electrical Engineering" <?= $formData['department'] === 'Electrical Engineering' ? 'selected' : '' ?>>Electrical Engineering</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label fw-semibold">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($formData['first_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label fw-semibold">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($formData['last_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= e($formData['email']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold">Mobile Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>" placeholder="10-digit mobile number">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label for="semester" class="form-label fw-semibold">Semester</label>
                                        <select class="form-select" id="semester" name="semester">
                                            <?php for ($s = 1; $s <= 6; $s++): ?>
                                                <option value="<?= $s ?>" <?= $formData['semester'] == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label fw-semibold">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="Male" <?= $formData['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= $formData['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= $formData['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dob" class="form-label fw-semibold">Date of Birth</label>
                                        <input type="date" class="form-control" id="dob" name="dob" value="<?= e($formData['dob']) ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">Initial Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required>
                                    <small class="text-muted">Must be min. 8 characters. Student will use this for first login.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Records Card -->
                    <div class="col-lg-5">
                        <div class="card card-portal">
                            <div class="card-header">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>Academic & Eligibility Scores</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="cgpa" class="form-label fw-semibold">Diploma CGPA (0.00 - 10.00) *</label>
                                    <input type="number" step="0.01" min="0" max="10" class="form-control form-control-lg" id="cgpa" name="cgpa" value="<?= e($formData['cgpa']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="attendance_percentage" class="form-label fw-semibold">Attendance % (0.00 - 100.00) *</label>
                                    <input type="number" step="0.1" min="0" max="100" class="form-control form-control-lg" id="attendance_percentage" name="attendance_percentage" value="<?= e($formData['attendance_percentage']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="active_backlogs" class="form-label fw-semibold">Active Backlogs</label>
                                    <input type="number" min="0" max="20" class="form-control" id="active_backlogs" name="active_backlogs" value="<?= e($formData['active_backlogs']) ?>">
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <label for="tenth_percentage" class="form-label fw-semibold">10th %</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="tenth_percentage" name="tenth_percentage" value="<?= e($formData['tenth_percentage']) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label for="twelfth_percentage" class="form-label fw-semibold">12th / ITI %</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="twelfth_percentage" name="twelfth_percentage" value="<?= e($formData['twelfth_percentage']) ?>">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                                    <i class="bi bi-check2-circle me-1"></i> Save & Create Student
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
