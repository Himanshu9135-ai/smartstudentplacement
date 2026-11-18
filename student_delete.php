<?php
/**
 * Admin: Delete Student Handler
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('danger', 'Invalid request method. Deletion must be submitted via POST.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

if (!validateCsrfToken()) {
    setFlash('danger', 'Security validation failed (Invalid CSRF token). Student was not deleted.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

$studentId = (int)($_POST['id'] ?? 0);
if ($studentId <= 0) {
    setFlash('danger', 'Invalid student ID.');
    header('Location: ' . BASE_URL . 'admin/students.php');
    exit;
}

try {
    // 1. Fetch user_id & resume_path
    $stmt = $pdo->prepare("SELECT user_id, resume_path, first_name, last_name, enrollment_no FROM students WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch();

    if ($student) {
        // Delete resume file from disk
        if (!empty($student['resume_path'])) {
            $resumeFile = __DIR__ . '/../' . $student['resume_path'];
            if (file_exists($resumeFile)) {
                @unlink($resumeFile);
            }
        }

        // Delete user (foreign key CASCADE deletes student & related records)
        $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = :uid");
        $stmtDel->execute([':uid' => $student['user_id']]);

        setFlash('success', 'Student ' . e($student['first_name'] . ' ' . $student['last_name']) . ' (' . e($student['enrollment_no']) . ') deleted successfully.');
    } else {
        setFlash('danger', 'Student not found.');
    }
} catch (PDOException $e) {
    error_log("Student deletion error: " . $e->getMessage());
    setFlash('danger', 'Failed to delete student due to an internal error.');
}

header('Location: ' . BASE_URL . 'admin/students.php');
exit;
