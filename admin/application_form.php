<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$success = false;
$application_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        // Basic fields
        $first_name     = trim($_POST['first_name'] ?? '');
        $middle_name    = trim($_POST['middle_name'] ?? '');
        $last_name      = trim($_POST['last_name'] ?? '');
        $mother_name    = trim($_POST['mother_name'] ?? '');
        $birth_date     = $_POST['birth_date'] ?? '';
        $gender         = $_POST['gender'] ?? '';
        $fayda_id       = trim($_POST['fayda_id'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $program        = $_POST['program'] ?? '';
        $department     = $_POST['department'] ?? '';
        $study_mode     = $_POST['study_mode'] ?? '';
        $previous_school = trim($_POST['previous_school'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $previous_university = trim($_POST['previous_university'] ?? '');
        $bachelor_cgpa  = $_POST['bachelor_cgpa'] ?? '';

        // File uploads (BLOB)
        $grade_12_doc = null;
        $grade_10_doc = null;
        $transcript_doc = null;
        $bachelor_degree = null;
        $passport_photo = null;

        // Validation
        if (empty($first_name) || empty($last_name) || empty($birth_date)) $errors[] = 'Child name and birth date required.';
        if (!in_array($program, ['Bachelor','Masters','PhD'])) $errors[] = 'Select valid program.';
        if (empty($department)) $errors[] = 'Choose department.';
        if (empty($phone)) $errors[] = 'Phone number required.';

        // File validation helper
        function validateUpload($file, $max_mb = 10, $types = []) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
            if ($file['error'] !== UPLOAD_ERR_OK) return false;
            if ($file['size'] > $max_mb * 1024 * 1024) return false;
            if (!empty($types)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $types)) return false;
            }
            return file_get_contents($file['tmp_name']);
        }

        // Process files
        if ($program === 'Bachelor') {
            $grade_12_doc = validateUpload($_FILES['grade_12_doc'], 10, ['application/pdf', 'image/jpeg', 'image/png']);
            $grade_10_doc = validateUpload($_FILES['grade_10_doc'], 10, ['application/pdf', 'image/jpeg', 'image/png']);
            if (!$grade_12_doc || !$grade_10_doc) $errors[] = 'Grade 12 & 10 documents required (PDF/Image, max 10MB).';
        }

        $transcript_doc = validateUpload($_FILES['transcript_doc'], 10, ['application/pdf']);
        $passport_photo = validateUpload($_FILES['passport_photo'], 5, ['image/jpeg', 'image/png']);
        if (!$passport_photo) $errors[] = 'Passport photo required (JPG/PNG).';

        if ($program === 'Masters') {
            $bachelor_degree = validateUpload($_FILES['bachelor_degree'], 10, ['application/pdf']);
            if (!$bachelor_degree || empty($previous_university) || empty($bachelor_cgpa)) {
                $errors[] = 'Bachelor degree, university name, and CGPA required for Masters.';
            }
        }

        // Generate Unique Application ID
        $initials = strtoupper(substr($first_name, 0, 3) . substr($last_name, 0, 3));
        $year = date('Y', strtotime($birth_date));
        $random = sprintf("%04d", mt_rand(0, 9999));
        $application_id = "$initials-$year-$random";

        // Check duplicate application
        if (empty($errors)) {
            global $pdo;
            $check = $pdo->prepare("SELECT id FROM applications WHERE first_name = ? AND last_name = ? AND birth_date = ?");
            $check->execute([$first_name, $last_name, $birth_date]);
            if ($check->fetch()) {
                $errors[] = 'This child has already applied.';
            }
        }

        // Save to DB
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO applications (
                        application_id, first_name, middle_name, last_name, mother_name, birth_date, gender, fayda_id,
                        phone, email, program, department, study_mode, previous_school, address,
                        previous_university, bachelor_cgpa,
                        grade_12_doc, grade_10_doc, transcript_doc, bachelor_degree, passport_photo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $application_id, $first_name, $middle_name, $last_name, $mother_name, $birth_date, $gender, $fayda_id,
                    $phone, $email, $program, $department, $study_mode, $previous_school, $address,
                    $previous_university, $bachelor_cgpa,
                    $grade_12_doc, $grade_10_doc, $transcript_doc, $bachelor_degree, $passport_photo
                ]);
                log_action($pdo, $_SESSION['user_id'] ?? null, "application form {$application_id}");
                $success = true;
            } catch (Exception $e) {
                $errors[] = 'Application failed. Please try again.';
            }
        }
    }
}
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Start Application | Adama Science & Technology University</title>

<section class="py-20 bg-gradient-to-b from-blue-50 to-white min-h-screen">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-deepblue mb-4">Start Your Application Today</h1>
            <p class="text-xl text-gray-700">Secure your future at one of Ethiopia's top universities</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-white rounded-3xl shadow-2xl p-16 text-center">
                <i class="fas fa-check-circle text-9xl text-green-500 mb-8"></i>
                <h2 class="text-5xl font-bold text-deepblue mb-8">Application Submitted Successfully!</h2>
                
                <div class="bg-gradient-to-r from-purple-100 to-blue-100 rounded-3xl p-12 max-w-2xl mx-auto">
                    <p class="text-2xl mb-8">Your Application ID:</p>
                    <p class="text-6xl font-bold font-mono text-deepblue tracking-widest"><?= htmlspecialchars($application_id) ?></p>
                    <p class="text-lg text-gray-700 mt-8">
                        Use this ID to track your application status.<br>
                        We will contact you via phone/email.
                    </p>
                </div>
                <a href="../index.php" class="inline-block mt-12 bg-deepblue text-white text-2xl font-bold px-20 py-6 rounded-2xl hover:scale-105 transition shadow-xl">
                    Back to Home
                </a>
            </div>

        <?php else: ?>
            <div class="bg-white rounded-3xl shadow-2xl p-10">
                <?php if ($errors): ?>
                    <div class="mb-8 p-6 bg-red-50 border-2 border-red-300 text-red-800 rounded-2xl">
                        <?php foreach ($errors as $e): ?>
                            <div class="flex items-center gap-3"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="space-y-10">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <!-- Child Info -->
                    <div class="grid md:grid-cols-3 gap-8">
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">First Name</label>
                            <input name="first_name" type="text" required class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['first_name']??'' ?>">
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Middle Name</label>
                            <input name="middle_name" type="text" class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['middle_name']??'' ?>">
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Last Name</label>
                            <input name="last_name" type="text" required class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['last_name']??'' ?>">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <div>
                            <label class="block text-lg font-bold textdeepblue mb-3">Mother's Full Name</label>
                            <input name="mother_name" type="text" class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['mother_name']??'' ?>">
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Date of Birth</label>
                            <input name="birth_date" type="date" required class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['birth_date']??'' ?>">
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Gender</label>
                            <select name="gender" required class="w-full px-6 py-4 border-2 rounded-xl">
                                <option value="">Select</option>
                                <option value="Male" <?= ($_POST['gender']??'')==='Male'?'selected':'' ?>>Male</option>
                                <option value="Female" <?= ($_POST['gender']??'')==='Female'?'selected':'' ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Fayda ID (if available)</label>
                            <input name="fayda_id" type="text" class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['fayda_id']??'' ?>">
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Phone Number</label>
                            <input name="phone" type="tel" required class="w-full px-6 py-4 border-2 rounded-xl" value="<?= $_POST['phone']??'' ?>">
                        </div>
                    </div>

                    <!-- Program Selection -->
                    <div class="grid md:grid-cols-3 gap-8">
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Program</label>
                            <select name="program" required class="w-full px-6 py-4 border-2 rounded-xl" onchange="toggleFields()">
                                <option value="">Select Program</option>
                                <option value="Bachelor">Bachelor's Degree</option>
                                <option value="Masters">Master's Degree</option>
                                <option value="PhD">PhD</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Department</label>
                            <select name="department" required class="w-full px-6 py-4 border-2 rounded-xl">
                                <option value="">Choose Department</option>
                                <option>CSE</option><option>Electrical Engineering</option><option>Mechanical Engineering</option>
                                <option>Accounting</option><option>Marketing</option><option>Management</option>
                                <option>Civil Engineering</option><option>Architecture</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-lg font-bold text-deepblue mb-3">Study Mode</label>
                            <select name="study_mode" required class="w-full px-6 py-4 border-2 rounded-xl">
                                <option value="Regular (Day)">Regular (Day)</option>
                                <option value="Night">Night</option>
                                <option value="Weekend">Weekend</option>
                                <option value="Distance">Distance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Conditional Fields -->
                    <div id="bachelorFields" class="hidden space-y-6 border-t pt-8">
                        <h3 class="text-2xl font-bold text-deepblue">Grade 12 & 10 Documents (Required)</h3>
                        <div class="grid md:grid-cols-2 gap-8">
                            <div>
                                <label>Grade 12 Certificate / Entrance Exam Result</label>
                                <input name="grade_12_doc" type="file" accept=".pdf,.jpg,.png" required class="w-full">
                            </div>
                            <div>
                                <label>Grade 10 Matric Certificate</label>
                                <input name="grade_10_doc" type="file" accept=".pdf,.jpg,.png" required class="w-full">
                            </div>
                        </div>
                    </div>

                    <div id="mastersFields" class="hidden space-y-6 border-t pt-8">
                        <h3 class="text-2xl font-bold text-deepblue">Previous Education (Masters)</h3>
                        <div class="grid md:grid-cols-3 gap-8">
                            <div>
                                <label>Bachelor Degree Certificate (PDF)</label>
                                <input name="bachelor_degree" type="file" accept=".pdf">
                            </div>
                            <div>
                                <label>Previous University/College</label>
                                <input name="previous_university" type="text" class="w-full px-6 py-4 border-2 rounded-xl">
                            </div>
                            <div>
                                <label>Bachelor CGPA (e.g. 3.75)</label>
                                <input name="bachelor_cgpa" type="number" step="0.01" min="2.0" max="4.0" class="w-full px-6 py-4 border-2 rounded-xl">
                            </div>
                        </div>
                    </div>

                    <!-- Common Uploads -->
                    <div class="grid md:grid-cols-2 gap-8 border-t pt-8">
                        <div>
                            <label>Official Transcript (All semesters - PDF, max 10MB)</label>
                            <input name="transcript_doc" type="file" accept=".pdf" class="w-full">
                        </div>
                        <div>
                            <label>Passport Size Photo (JPG/PNG)</label>
                            <input name="passport_photo" type="file" accept="image/*" required class="w-full">
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="submit" class="bg-gradient-to-r from-deepblue to-purple-600 text-white font-bold text-2xl px-24 py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function toggleFields() {
    const program = document.querySelector('[name="program"]').value;
    document.getElementById('bachelorFields').classList.toggle('hidden', program !== 'Bachelor');
    document.getElementById('mastersFields').classList.toggle('hidden', program !== 'Masters');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>