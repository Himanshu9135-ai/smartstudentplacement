<?php
/**
 * 1-Click Export to Excel / CSV Engine
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireLogin();

$type = trim($_GET['type'] ?? 'placed_students');
$timestamp = date('Y-m-d_His');

if ($type === 'placed_students') {
    if (!isAdmin()) {
        die("Access denied.");
    }

    $filename = "Placed_Students_Report_{$timestamp}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fputs($output, "\xEF\xBB\xBF");

    // Header Row
    fputcsv($output, ['S.No', 'Enrollment / Roll No', 'Student Full Name', 'Department / Branch', 'CGPA', 'Hiring Company', 'Designation', 'Package (LPA)', 'Date Placed']);

    $stmt = $pdo->query("
        SELECT s.enrollment_no, s.first_name, s.last_name, s.department, ar.cgpa,
               c.name as company_name, d.job_title, d.package_lpa, a.applied_at
        FROM applications a
        JOIN placement_drives d ON a.drive_id = d.id
        JOIN companies c ON d.company_id = c.id
        JOIN students s ON a.student_id = s.id
        LEFT JOIN academic_records ar ON s.id = ar.student_id
        WHERE a.status = 'Selected'
        ORDER BY d.package_lpa DESC, s.enrollment_no ASC
    ");

    $i = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $i++,
            $row['enrollment_no'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['department'],
            number_format((float)($row['cgpa'] ?? 0), 2),
            $row['company_name'],
            $row['job_title'],
            number_format((float)$row['package_lpa'], 2) . ' LPA',
            date('d M Y', strtotime($row['applied_at']))
        ]);
    }

    fclose($output);
    exit;

} elseif ($type === 'all_students') {
    if (!isAdmin()) {
        die("Access denied.");
    }

    $filename = "All_Registered_Students_{$timestamp}.csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, ['S.No', 'Roll No', 'Name', 'Email', 'Phone', 'Department', 'CGPA', 'Attendance %', 'Backlogs', 'Placed Offers']);

    $stmt = $pdo->query("
        SELECT s.*, u.email, ar.cgpa, ar.attendance_percentage, ar.active_backlogs,
               (SELECT COUNT(*) FROM applications WHERE student_id = s.id AND status = 'Selected') as placed_offers
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN academic_records ar ON s.id = ar.student_id
        ORDER BY s.enrollment_no ASC
    ");

    $i = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $i++,
            $row['enrollment_no'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['email'],
            $row['phone'] ?? 'N/A',
            $row['department'],
            number_format((float)($row['cgpa'] ?? 0), 2),
            number_format((float)($row['attendance_percentage'] ?? 0), 1) . '%',
            $row['active_backlogs'] ?? 0,
            $row['placed_offers']
        ]);
    }

    fclose($output);
    exit;

} elseif ($type === 'company_applicants') {
    if (!isCompany()) {
        die("Access denied.");
    }

    $companyId = (int)($_SESSION['company_id'] ?? 0);
    $filename = "Company_Applicants_{$timestamp}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, ['S.No', 'Roll No', 'Candidate Name', 'Email', 'Phone', 'Branch', 'CGPA', 'Attendance %', 'Job Title', 'Package (LPA)', 'Current Stage', 'Recruiter Remarks', 'Date Applied']);

    $stmt = $pdo->prepare("
        SELECT a.*, s.enrollment_no, s.first_name, s.last_name, s.department, s.phone,
               u.email as student_email, ar.cgpa, ar.attendance_percentage,
               d.job_title, d.package_lpa
        FROM applications a
        JOIN placement_drives d ON a.drive_id = d.id
        JOIN students s ON a.student_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN academic_records ar ON s.id = ar.student_id
        WHERE d.company_id = :cid
        ORDER BY a.applied_at DESC
    ");
    $stmt->execute([':cid' => $companyId]);

    $i = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $i++,
            $row['enrollment_no'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['student_email'],
            $row['phone'] ?? 'N/A',
            $row['department'],
            number_format((float)($row['cgpa'] ?? 0), 2),
            number_format((float)($row['attendance_percentage'] ?? 0), 1) . '%',
            $row['job_title'],
            $row['package_lpa'],
            $row['status'],
            $row['admin_notes'] ?? 'None',
            date('d M Y', strtotime($row['applied_at']))
        ]);
    }

    fclose($output);
    exit;
} else {
    die("Invalid export request.");
}
