<?php
/**
 * Official Placement Selection & Offer Letter Generator
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireAdmin();

$applicationId = (int)($_GET['id'] ?? 0);
if ($applicationId <= 0) {
    die("Invalid application ID.");
}

$stmt = $pdo->prepare("
    SELECT a.*, s.enrollment_no, s.first_name, s.last_name, s.department, s.phone,
           u.email,
           c.name as company_name, c.location as company_location, c.website as company_website,
           d.job_title, d.package_lpa, d.drive_date
    FROM applications a
    JOIN placement_drives d ON a.drive_id = d.id
    JOIN companies c ON d.company_id = c.id
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE a.id = :id AND a.status = 'Selected'
    LIMIT 1
");
$stmt->execute([':id' => $applicationId]);
$offer = $stmt->fetch();

if (!$offer) {
    die("Error: Offer Letter can only be generated for candidates with 'Selected' status.");
}

$refNo = "TPO/OFFER/" . date('Y') . "/" . str_pad($offer['id'], 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offer Letter - <?= e($offer['first_name'] . ' ' . $offer['last_name']) ?> (<?= e($offer['enrollment_no']) ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; }
        .letter-container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 50px 60px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-top: 6px solid #2563eb;
        }
        .header-logo { border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .salutation { margin: 25px 0 15px; font-weight: 600; }
        .letter-body { line-height: 1.8; font-size: 15px; }
        .meta-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .meta-table td { padding: 8px 12px; border: 1px solid #cbd5e1; }
        .meta-table td.label { background: #f1f5f9; font-weight: 600; width: 35%; }
        .signatures { margin-top: 60px; display: flex; justify-content: space-between; }
        .sig-box { text-align: center; width: 200px; border-top: 1px solid #94a3b8; padding-top: 8px; font-size: 13px; }
        @media print {
            body { background: #fff; }
            .letter-container { box-shadow: none; margin: 0; padding: 0; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center no-print my-3">
    <button onclick="window.print()" class="btn btn-primary px-4 fw-bold me-2">
        <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </button>
    <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Reports
    </a>
</div>

<div class="letter-container">
    <div class="header-logo d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-primary mb-1"><i class="bi bi-mortarboard-fill me-2"></i>GOVERNMENT POLYTECHNIC</h4>
            <h6 class="text-muted mb-0">Training & Placement Cell (T&P Department)</h6>
        </div>
        <div class="text-end">
            <span class="badge bg-success fs-6"><i class="bi bi-patch-check-fill me-1"></i> Official Selection</span>
            <div class="small text-muted mt-1">Ref: <strong><?= $refNo ?></strong></div>
        </div>
    </div>

    <div class="d-flex justify-content-between text-muted small mb-4">
        <div><strong>Date:</strong> <?= date('F d, Y') ?></div>
        <div><strong>Recruitment Drive:</strong> <?= formatPortalDate($offer['drive_date']) ?></div>
    </div>

    <div class="mb-3">
        <strong>To,</strong><br>
        <strong><?= e($offer['first_name'] . ' ' . $offer['last_name']) ?></strong><br>
        Roll No: <strong><?= e($offer['enrollment_no']) ?></strong><br>
        Department: <?= e($offer['department']) ?><br>
        Email: <?= e($offer['email']) ?>
    </div>

    <p class="salutation">Dear <?= e($offer['first_name']) ?>,</p>

    <div class="letter-body">
        <p>
            On behalf of the <strong>Training and Placement Cell</strong> and <strong><?= e($offer['company_name']) ?></strong>, 
            we are pleased to officially congratulate you on your outstanding performance in the campus recruitment drive. 
            You have successfully cleared all assessment rounds and interview evaluations.
        </p>
        <p>
            We are delighted to extend this formal <strong>Letter of Intent / Campus Placement Offer</strong> for the designation detailed below:
        </p>

        <table class="meta-table">
            <tr>
                <td class="label">Recruiting Organization</td>
                <td><strong><?= e($offer['company_name']) ?></strong> (<?= e($offer['company_location']) ?>)</td>
            </tr>
            <tr>
                <td class="label">Offered Designation</td>
                <td><strong><?= e($offer['job_title']) ?></strong></td>
            </tr>
            <tr>
                <td class="label">Annual Compensation (CTC)</td>
                <td><strong class="text-success">₹ <?= number_format((float)$offer['package_lpa'], 2) ?> LPA</strong></td>
            </tr>
            <tr>
                <td class="label">Candidate Verification</td>
                <td>Verified by TPO Placement Portal</td>
            </tr>
        </table>

        <p class="mt-3">
            Your detailed appointment letter with joining instructions and onboard documentation guidelines will be provided by <strong><?= e($offer['company_name']) ?></strong> HR department prior to your graduation date.
        </p>
        <p>
            Please accept our heartiest congratulations on your achievement. We wish you immense success in your professional career.
        </p>
    </div>

    <div class="signatures">
        <div class="sig-box">
            <strong>Head, T&P Cell</strong><br>
            Campus Placement Officer
        </div>
        <div class="sig-box">
            <strong>Campus HR Manager</strong><br>
            <?= e($offer['company_name']) ?>
        </div>
    </div>
</div>

</body>
</html>
