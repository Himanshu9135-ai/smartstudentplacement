<?php
/**
 * Admin: Application Status Processing Handler
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/drives.php');
    exit;
}

if (!validateCsrfToken()) {
    setFlash('danger', 'Security validation failed (Invalid CSRF token).');
    header('Location: ' . BASE_URL . 'admin/drives.php');
    exit;
}

$appId   = (int)($_POST['application_id'] ?? 0);
$driveId = (int)($_POST['drive_id'] ?? 0);
$status  = trim($_POST['status'] ?? 'Applied');
$notes   = trim($_POST['admin_notes'] ?? '');

$allowedStatuses = ['Applied', 'Shortlisted', 'Interview', 'Selected', 'Rejected'];

if ($appId > 0 && in_array($status, $allowedStatuses)) {
    try {
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET status = :status, admin_notes = :notes 
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':notes'  => $notes,
            ':id'     => $appId
        ]);

        setFlash('success', 'Application status updated to "' . e($status) . '" successfully!');
    } catch (PDOException $e) {
        error_log("Application status update error: " . $e->getMessage());
        setFlash('danger', 'Failed to update application due to an internal error.');
    }
} else {
    setFlash('danger', 'Invalid application or status value.');
}

if ($driveId > 0) {
    header('Location: ' . BASE_URL . 'admin/drive_view.php?id=' . $driveId);
} else {
    header('Location: ' . BASE_URL . 'admin/drives.php');
}
exit;
