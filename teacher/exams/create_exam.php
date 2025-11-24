<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacher = $_SESSION['teacher'];
$teacher_id = $teacher['id'];
$department = $teacher['department'];
$message = '';

// Handle form submission
if (isset($_POST['submit'])) {
    $exam_name     = trim($_POST['exam_name']);
    $class_id      = $_POST['class_id'];
    $exam_date     = $_POST['exam_date'];
    $total_marks   = (int)$_POST['total_marks'];
    $exam_type     = $_POST['exam_type'];

    // Validate total marks
    if ($total_marks < 1 || $total_marks > 500) {
        $message = "<div class='alert alert-danger'>Total marks must be between 1 and 500</div>";
    } else {
        // Get class info for display
        $stmt = $pdo->prepare("SELECT CONCAT(class_name, ' - ', section) AS display_name, class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class_info) {
            $message = "<div class='alert alert-danger'>Invalid class selected!</div>";
        } else {
            $display_name = $class_info['display_name'];
            $class_name_only = $class_info['class_name'];

            // Handle PDF → store as BLOB
            if (isset($_FILES['exam_pdf']) && $_FILES['exam_pdf']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['exam_pdf'];

                if ($file['size'] > 10 * 1024 * 1024) {
                    $message = "<div class='alert alert-danger'>File too large (max 10MB)</div>";
                } elseif (!in_array($file['type'], ['application/pdf']) || mime_content_type($file['tmp_name']) !== 'application/pdf') {
                    $message = "<div class='alert alert-danger'>Only PDF files are allowed!</div>";
                } else {
                    $pdf_blob = file_get_contents($file['tmp_name']);

                    // Insert exam with all new fields
                    $stmt = $pdo->prepare("INSERT INTO exams 
                        (exam_name, class_name, class_section_display, exam_date, total_marks, exam_type, exam_file, created_by, status, published, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, NOW())");

                    $success = $stmt->execute([
                        $exam_name,
                        $class_name_only,
                        $display_name,
                        $exam_date,
                        $total_marks,
                        $exam_type,
                        $pdf_blob,
                        $teacher_id
                    ]);

                    if ($success) {
                        $exam_id = $pdo->lastInsertId();

                        // Notify department teachers
                        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE department = ? AND id != ?");
                        $stmt->execute([$department, $teacher_id]);
                        $ins = $pdo->prepare("INSERT IGNORE INTO exam_approvals (exam_id, teacher_id) VALUES (?, ?)");
                        foreach ($stmt->fetchAll() as $t) {
                            $ins->execute([$exam_id, $t['id']]);
                        }

                        $message = "<div class='alert alert-success text-center py-5 shadow-lg'>
                            <i class='fas fa-check-circle fa-4x text-success mb-3'></i><br>
                            <h3>Exam Created Successfully!</h3>
                            <p><strong>$exam_name</strong><br>
                               $display_name • $exam_type • $total_marks marks • " . date('d M Y', strtotime($exam_date)) . "</p>
                            <small>Your exam has been sent for department approval.</small>
                        </div>";
                    } else {
                        $message = "<div class='alert alert-danger'>Failed to save exam. Please try again.</div>";
                    }
                }
            } else {
                $message = "<div class='alert alert-danger'>Please upload a valid PDF file!</div>";
            }
        }
    }
}

// Fetch classes with sections
$stmt = $pdo->prepare("
    SELECT id, class_name, section, 
           CONCAT(class_name, ' - ', section) AS full_name 
    FROM classes 
    ORDER BY class_name, section
");
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Exam type options
$exam_types = [
    'Quiz',
    'Weekly Test', 
    'Monthly Test',
    'Mid-Term Exam',
    'Final Exam',
    'Pre-Board Exam',
    'Unit Test',
    'Assignment'
];
?>

<?php require_once __DIR__ . '/../../includes/head.php'; ?>
<title>Create New Exam</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .glass-card { background: rgba(255,255,255,0.18); backdrop-filter: blur(15px); border-radius: 25px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
    .form-control, .form-select { border-radius: 15px; padding: 14px 20px; background: rgba(255,255,255,0.95); border: none; }
    .file-zone {
        border: 3px dashed rgba(255,255,255,0.4);
        border-radius: 20px;
        padding: 50px 20px;
        text-align: center;
        background: rgba(255,255,255,0.1);
        cursor: pointer;
        transition: all 0.3s;
    }
    .file-zone:hover { border-color: #fff; background: rgba(255,255,255,0.2); }
    .file-zone.dragover { background: rgba(255,255,255,0.3); border-color: #a8e6cf; }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center text-white mb-5">
                <h1 class="display-3 fw-bold"><i class="fas fa-file-pdf text-danger"></i> Create New Exam</h1>
                <p class="lead">Fill details and upload exam PDF for approval</p>
            </div>

            <?= $message ?>

            <div class="glass-card p-5">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label text-white fw-bold"><i class="fas fa-heading"></i> Exam Name</label>
                            <input type="text" name="exam_name" class="form-control form-control-lg" 
                                   placeholder="e.g., First Term Examination - Mathematics" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white fw-bold"><i class="fas fa-trophy"></i> Exam Type</label>
                            <select name="exam_type" class="form-select form-select-lg" required>
                                <option value="">Select Type</option>
                                <?php foreach ($exam_types as $type): ?>
                                    <option value="<?= $type ?>"><?= $type ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-md-4">
                            <label class="form-label text-white fw-bold"><i class="fas fa-calendar-alt"></i> Exam Date</label>
                            <input type="date" name="exam_date" class="form-control form-control-lg" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white fw-bold"><i class="fas fa-calculator"></i> Total Marks</label>
                            <input type="number" name="total_marks" class="form-control form-control-lg" 
                                   placeholder="e.g., 100" min="1" max="500" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white fw-bold"><i class="fas fa-users-class"></i> Class & Section</label>
                            <select name="class_id" class="form-select form-select-lg" required>
                                <option value="">Select Class & Section</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?= $cls['id'] ?>"><?= htmlspecialchars($cls['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="form-label text-white fw-bold"><i class="fas fa-cloud-upload-alt"></i> Upload Exam PDF</label>
                        <div class="file-zone" id="dropZone">
                            <i class="fas fa-cloud-upload-alt fa-4x text-white mb-3"></i>
                            <p class="text-white fw-bold">Click to upload or drag & drop</p>
                            <p class="text-white-50">PDF only • Max 10MB</p>
                            <input type="file" name="exam_pdf" id="fileInput" accept=".pdf" required style="display:none;">
                        </div>
                        <div id="fileName" class="text-white text-center mt-3 fw-bold"></div>
                    </div>

                    <div class="text-center mt-5">
                        <button type="submit" name="submit" class="btn btn-danger btn-lg px-5 shadow-lg">
                            <i class="fas fa-paper-plane"></i> Create Exam & Submit for Approval
                        </button>
                    </div>
                </form>
            </div>

            <div class="text-center mt-4">
                <a href="../teacher_dashboard.php" class="text-white fs-5"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileNameDiv = document.getElementById('fileName');

dropZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => {
    if (fileInput.files.length) {
        fileNameDiv.innerHTML = `<i class="fas fa-file-pdf text-danger"></i> Selected: <strong>${fileInput.files[0].name}</strong>`;
    }
});

['dragover', 'dragenter'].forEach(evt => {
    dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.add('dragover'); });
});
['dragleave', 'dragend', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, e => { e.preventDefault(); dropZone.classList.remove('dragover'); });
});
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        fileNameDiv.innerHTML = `<i class="fas fa-check text-success"></i> Ready: <strong>${e.dataTransfer.files[0].name}</strong>`;
    }
});
</script>