<?php
/**
 * One-Click Database Setup & Seeder Script
 * Smart Student Management & Placement Portal
 */

define('LOCK_FILE', __DIR__ . '/config/database.installed');

// 1. Security Check: Check if database is already installed and locked
if (file_exists(LOCK_FILE)) {
    if (php_sapi_name() === 'cli') {
        echo "\n=== Smart Student Placement Portal Database Setup ===\n";
        echo "[LOCKED] Installation is already completed and locked for security.\n";
        echo "To re-run the installer, remove the lock file: config/database.installed\n\n";
        exit(0);
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation Locked - Smart Student Placement Portal</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .setup-card { max-width: 600px; margin: 60px auto; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
            .card-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-radius: 12px 12px 0 0 !important; padding: 25px; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card setup-card">
            <div class="card-header text-center">
                <h3 class="mb-1"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>Installation Locked</h3>
                <p class="mb-0 text-white-50">Smart Student Management & Placement Portal</p>
            </div>
            <div class="card-body p-4 text-center">
                <div class="alert alert-info text-start d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle-fill fs-3 me-3 text-primary"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Installation is already completed and locked for security.</h6>
                        <p class="mb-0 small text-muted">To protect existing student records and prevent accidental data loss or password resets, re-running this setup is restricted.</p>
                    </div>
                </div>

                <div class="alert alert-warning text-start small mb-4">
                    <i class="bi bi-shield-exclamation me-1"></i> Default credentials have been configured during initial setup. If you forgot your password, please contact your system administrator.
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <a href="index.php" class="btn btn-outline-primary px-4 py-2">
                        <i class="bi bi-house-door me-1"></i> Visit Homepage
                    </a>
                    <a href="auth/login.php" class="btn btn-primary px-4 py-2 fw-bold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'smart_placement_db');
define('DB_USER', 'root');
define('DB_PASS', '');

$messages = [];
$success = true;

function stripBOM($text) {
    $bom = pack('H*','EFBBBF');
    return preg_replace("/^$bom/", '', $text);
}

try {
    // 1. Connect without selecting DB to create it if needed
    $pdoRoot = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = "Database `" . DB_NAME . "` created or verified successfully.";

    // 2. Connect to the specific database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 3. Execute Schema
    $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
    if ($schemaSql) {
        $schemaSql = stripBOM($schemaSql);
        $pdo->exec($schemaSql);
        $messages[] = "Database schema tables created successfully.";
    }

    // 4. Seed Master Skills
    $seedSql = file_get_contents(__DIR__ . '/database/seed_data.sql');
    if ($seedSql) {
        $seedSql = stripBOM($seedSql);
        $pdo->exec($seedSql);
        $messages[] = "Master skills, companies, and sample drives seeded successfully.";
    }

    // 5. Seed Primary Admin User
    $adminEmail = 'mrhimanshukumar343@gmail.com';
    $adminPassword = password_hash('Sharma@#1432', PASSWORD_BCRYPT);
    $stmtAdmin = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmtAdmin->execute([':email' => $adminEmail]);
    if (!$stmtAdmin->fetch()) {
        $stmtInsert = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (:email, :password, 'admin', 'active')");
        $stmtInsert->execute([':email' => $adminEmail, ':password' => $adminPassword]);
        $messages[] = "Primary Admin account configured: <strong>mrhimanshukumar343@gmail.com</strong>";
    } else {
        $messages[] = "Primary Admin account verified (<strong>mrhimanshukumar343@gmail.com</strong>).";
    }

    // 6. Seed Sample Eligible Student
    $studentEmail = 'rahul.sharma@college.edu';
    $studentPass = password_hash('student123', PASSWORD_BCRYPT);
    $stmtStudent = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmtStudent->execute([':email' => $studentEmail]);
    $studentUser = $stmtStudent->fetch();

    if (!$studentUser) {
        $stmtInsUser = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (:email, :password, 'student', 'active')");
        $stmtInsUser->execute([':email' => $studentEmail, ':password' => $studentPass]);
        $studentUserId = $pdo->lastInsertId();

        $stmtInsProf = $pdo->prepare("
            INSERT INTO students (user_id, enrollment_no, first_name, last_name, phone, department, semester, gender, dob, address)
            VALUES (:uid, 'CSE2024001', 'Rahul', 'Sharma', '9876501234', 'Computer Engineering', 5, 'Male', '2005-04-15', 'Pune, Maharashtra')
        ");
        $stmtInsProf->execute([':uid' => $studentUserId]);
        $studentId = $pdo->lastInsertId();

        // Academic record with high CGPA & attendance
        $stmtInsAcad = $pdo->prepare("
            INSERT INTO academic_records (student_id, tenth_percentage, twelfth_percentage, cgpa, active_backlogs, attendance_percentage)
            VALUES (:sid, 85.50, 82.00, 8.45, 0, 88.50)
        ");
        $stmtInsAcad->execute([':sid' => $studentId]);

        // Skills: PHP, MySQL, JavaScript, HTML5/CSS3
        $stmtSkill = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id, proficiency) VALUES (:sid, :skid, 'Advanced')");
        $stmtSkill->execute([':sid' => $studentId, ':skid' => 1]); // PHP
        $stmtSkill->execute([':sid' => $studentId, ':skid' => 2]); // MySQL
        $stmtSkill->execute([':sid' => $studentId, ':skid' => 3]); // JavaScript
        $stmtSkill->execute([':sid' => $studentId, ':skid' => 4]); // HTML/CSS

        $messages[] = "Demo Student 1 (Eligible Candidate) created: <strong>rahul.sharma@college.edu</strong> (Password: <strong>student123</strong>)";
    }

    // 7. Seed Sample Student 2 (Marginal/Low Attendance)
    $student2Email = 'priya.patel@college.edu';
    $stmtStudent2 = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmtStudent2->execute([':email' => $student2Email]);
    if (!$stmtStudent2->fetch()) {
        $stmtInsUser2 = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (:email, :password, 'student', 'active')");
        $stmtInsUser2->execute([':email' => $student2Email, ':password' => $studentPass]);
        $s2UserId = $pdo->lastInsertId();

        $stmtInsProf2 = $pdo->prepare("
            INSERT INTO students (user_id, enrollment_no, first_name, last_name, phone, department, semester, gender, dob, address)
            VALUES (:uid, 'CSE2024002', 'Priya', 'Patel', '9876505678', 'Computer Engineering', 5, 'Female', '2005-08-20', 'Pune, Maharashtra')
        ");
        $stmtInsProf2->execute([':uid' => $s2UserId]);
        $s2Id = $pdo->lastInsertId();

        $stmtInsAcad2 = $pdo->prepare("
            INSERT INTO academic_records (student_id, tenth_percentage, twelfth_percentage, cgpa, active_backlogs, attendance_percentage)
            VALUES (:sid, 78.00, 75.00, 6.20, 1, 68.00)
        ");
        $stmtInsAcad2->execute([':sid' => $s2Id]);

        // Skills: Python, Java
        $stmtSkill2 = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id, proficiency) VALUES (:sid, :skid, 'Intermediate')");
        $stmtSkill2->execute([':sid' => $s2Id, ':skid' => 5]); // Python
        $stmtSkill2->execute([':sid' => $s2Id, ':skid' => 6]); // Java

        $messages[] = "Demo Student 2 (Testing Ineligible criteria) created: <strong>priya.patel@college.edu</strong> (Password: <strong>student123</strong>)";
    }

    // 8. Create Lock File to prevent unauthorized re-runs
    file_put_contents(LOCK_FILE, "Installed on " . date('Y-m-d H:i:s') . "\n");
    $messages[] = "Security lock file <code>config/database.installed</code> created.";

} catch (Exception $e) {
    $success = false;
    $messages[] = "Error during setup: " . $e->getMessage();
}

// Check if running from CLI
if (php_sapi_name() === 'cli') {
    echo "\n=== Smart Student Placement Portal Database Setup ===\n";
    foreach ($messages as $msg) {
        echo "- " . strip_tags($msg) . "\n";
    }
    echo $success ? "\n[SUCCESS] Setup Completed and Locked Successfully!\n" : "\n[ERROR] Setup Failed.\n";
    exit($success ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Smart Student Placement Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .setup-card { max-width: 650px; margin: 50px auto; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .card-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-radius: 12px 12px 0 0 !important; padding: 25px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card setup-card">
        <div class="card-header text-center">
            <h3 class="mb-1"><i class="bi bi-database-check me-2"></i>Database Setup & Installer</h3>
            <p class="mb-0 text-white-50">Smart Student Management & Placement Portal</p>
        </div>
        <div class="card-body p-4">
            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center mb-4">
                    <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Database Initialized & Locked Successfully!</h5>
                        <p class="mb-0">All tables, constraints, master skills, and demo users are ready. Installer is now locked for security.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Setup Encountered an Issue</h5>
                        <p class="mb-0">Please check your MySQL server status in XAMPP.</p>
                    </div>
                </div>
            <?php endif; ?>

            <h6 class="text-uppercase text-muted fw-bold mb-3 fs-7">Installation Log</h6>
            <ul class="list-group mb-4">
                <?php foreach ($messages as $msg): ?>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="bi bi-arrow-right-short text-primary fs-4 me-2"></i>
                        <span><?= $msg ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="card bg-light border-0 p-3 mb-4">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-key-fill text-warning me-2"></i>Default Credentials</h6>
                <div class="row">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <div class="p-2 border rounded bg-white">
                            <span class="badge bg-danger mb-1">Admin Portal</span>
                            <div class="small"><strong>Email:</strong> admin@college.edu</div>
                            <div class="small"><strong>Password:</strong> admin123</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 border rounded bg-white">
                            <span class="badge bg-primary mb-1">Student Portal</span>
                            <div class="small"><strong>Email:</strong> rahul.sharma@college.edu</div>
                            <div class="small"><strong>Password:</strong> student123</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="index.php" class="btn btn-primary px-4 py-2 me-2">
                    <i class="bi bi-house-door-fill me-1"></i> Visit Homepage
                </a>
                <a href="auth/login.php" class="btn btn-success px-4 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Go to Login
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
