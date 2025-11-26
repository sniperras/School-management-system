<?php
declare(strict_types=1);

// Start output buffering — MUST be first
ob_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// Admin only
if (!is_logged_in() || current_user_role() !== 'admin') {
    die('Access denied.');
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Invalid application ID.');
}

$id = (int)$_GET['id'];

// ==================================================================
// MODE 1: SINGLE DOCUMENT DOWNLOAD (e.g. ?id=5&single=grade_12_doc)
// ==================================================================
if (isset($_GET['single'])) {
    $field = $_GET['single'];

    // Whitelist of allowed fields
    $allowed = [
        'passport_photo'  => 'Passport_Photo.jpg',
        'grade_10_doc'    => 'Grade_10_Certificate.pdf',
        'grade_12_doc'    => 'Grade_12_Certificate.pdf',
        'transcript_doc'  => 'Official_Transcript.pdf',
        'bachelor_degree' => 'Bachelor_Degree_Certificate.pdf',
    ];

    if (!array_key_exists($field, $allowed)) {
        die('Invalid document requested.');
    }

    $stmt = $pdo->prepare("SELECT `$field` FROM applications WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || empty($row[$field])) {
        die('Document not found or empty.');
    }

    $data = $row[$field];
    $filename = $allowed[$field];

    // Detect real MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($data);

    // Force correct extension for images
    if (str_starts_with($mime, 'image/')) {
        $ext = ($mime === 'image/jpeg') ? 'jpg' : 'png';
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $ext;
    }

    ob_clean();
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: private, max-age=0');
    echo $data;
    exit;
}

// ==================================================================
// MODE 2: FULL PDF PACKAGE (default behavior)
// ==================================================================

$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die('Application not found.');
}

require_once __DIR__ . '/../vendor/autoload.php';
use setasign\Fpdi\Tcpdf\Fpdi;

$pdf = new Fpdi('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetMargins(15, 15, 15);
$pdf->SetCreator('School Management System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Application ' . $app['application_id']);
$pdf->SetSubject('Complete Application Package');

function addTitle($pdf, $text) {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(59, 130, 246);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, $text, 0, 1, 'C', true);
    $pdf->Ln(8);
    $pdf->SetTextColor(0, 0, 0);
}

// Build PDF — your beautiful layout
$pdf->AddPage();
addTitle($pdf, 'APPLICATION PACKAGE');

$pdf->SetFont('helvetica', '', 12);
$pdf->SetFont('', 'B', 14);
$pdf->Cell(0, 10, 'Application ID: ' . htmlspecialchars($app['application_id']), 0, 1);
$pdf->SetFont('', '', 12);
$pdf->Cell(0, 8, 'Applied on: ' . date('d F Y, h:i A', strtotime($app['applied_at'])), 0, 1);
$pdf->Ln(10);

// Personal Information
$pdf->SetFont('', 'B', 13);
$pdf->Cell(0, 10, 'Personal Information', 0, 1);
$pdf->SetFont('', '', 11);

$data = [
    'Full Name'       => trim($app['first_name'] . ' ' . ($app['middle_name'] ?? '') . ' ' . $app['last_name']),
    'Mother\'s Name'  => $app['mother_name'] ?? '—',
    'Date of Birth'   => date('d F Y', strtotime($app['birth_date'])),
    'Gender'          => ucfirst($app['gender']),
    'Phone'           => $app['phone'],
    'Email'           => $app['email'] ?? '—',
    'Address'         => $app['address'] ?? '—',
];

foreach ($data as $label => $value) {
    $pdf->SetFont('', 'B');
    $pdf->Cell(50, 8, $label . ':');
    $pdf->SetFont('');
    $pdf->MultiCell(0, 8, $value, 0, 'L');
}
$pdf->Ln(8);

// Academic Information
$pdf->SetFont('', 'B', 13);
$pdf->Cell(0, 10, 'Academic Information', 0, 1);
$pdf->SetFont('', '', 11);

$academic = [
    'Program'    => $app['program'],
    'Department' => $app['department'],
    'Study Mode' => $app['study_mode'],
];

if ($app['program'] !== 'Bachelor') {
    $academic['Previous University'] = $app['previous_university'] ?? '—';
    $academic['Bachelor CGPA']       = $app['bachelor_cgpa'] ? number_format((float)$app['bachelor_cgpa'], 2) : '—';
}

foreach ($academic as $label => $value) {
    $pdf->SetFont('', 'B');
    $pdf->Cell(60, 8, $label . ':');
    $pdf->SetFont('');
    $pdf->Cell(0, 8, $value, 0, 1);
}
$pdf->Ln(10);

// Passport Photo
if (!empty($app['passport_photo'])) {
    $photoPath = tempnam(sys_get_temp_dir(), 'photo_') . '.jpg';
    file_put_contents($photoPath, $app['passport_photo']);
    $pdf->SetFont('', 'B', 13);
    $pdf->Cell(0, 10, 'Passport Photo', 0, 1);
    $pdf->Image($photoPath, 80, $pdf->GetY(), 50, 60, '', '', 'C');
    $pdf->Ln(65);
    @unlink($photoPath);
}

// Uploaded Documents
$documents = [
    'Grade 10 Certificate'     => $app['grade_10_doc'],
    'Grade 12 Certificate'     => $app['grade_12_doc'],
    'Official Transcript'      => $app['transcript_doc'],
    'Bachelor Degree (if any)' => $app['bachelor_degree'],
];

foreach ($documents as $title => $binary) {
    if (empty($binary)) continue;

    $tmpFile = tempnam(sys_get_temp_dir(), 'doc_');
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->buffer($binary);
    $ext     = ($mime === 'application/pdf') ? 'pdf' : 'jpg';
    $filePath = $tmpFile . '.' . $ext;
    file_put_contents($filePath, $binary);

    $pdf->AddPage();
    addTitle($pdf, $title);

    try {
        if ($mime === 'application/pdf') {
            $pageCount = $pdf->setSourceFile($filePath);
            for ($i = 1; $i <= $pageCount; $i++) {
                if ($i > 1) $pdf->AddPage();
                $tpl = $pdf->importPage($i);
                $pdf->useTemplate($tpl, 0, 0, 210);
            }
        } else {
            $pdf->Image($filePath, 15, 40, 180, 0, '', '', 'C');
        }
    } catch (Exception $e) {
        $pdf->SetFont('', '', 12);
        $pdf->Cell(0, 10, 'Warning: Could not embed this document.', 0, 1, 'C');
    }
    @unlink($filePath);
}

// Footer
$pdf->SetY(-30);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Generated on ' . date('d M Y \a\t H:i') . ' — School Management System', 0, 0, 'C');

// ==================================================================
// OUTPUT FULL PDF PACKAGE
// ==================================================================

ob_clean();

$safeId   = preg_replace('/[^A-Za-z0-9_-]/', '_', $app['application_id']);
$filename = "Application_{$safeId}_Complete_Package.pdf";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

echo $pdf->Output('', 'S');
log_action($pdo, $_SESSION['user_id'] ?? null, "download documents ID {$filename}");
exit;