<?php
declare(strict_types=1);

// admin/students.php - Full, improved version with robust student_id generation,
// retry on duplicate key, safe output casting, logging, and basic validation.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user = current_user();
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;

// -----------------------------
// Helper: robust student_id generator
// -----------------------------
/**
 * Generate the next student_id of the form ADMA/0001/25 for the current year.
 * Uses MAX on the numeric part; safer than COUNT().
 *
 * @param PDO $pdo
 * @return string
 */
function generate_student_id(PDO $pdo): string {
    $yearShort = date('y'); // e.g. 25
    $likePattern = "ADMA/%/{$yearShort}";

    $sql = "
        SELECT COALESCE(MAX(
            CAST(
                SUBSTRING_INDEX(SUBSTRING_INDEX(student_id, '/', 2), '/', -1)
            AS UNSIGNED)
        ), 0) AS max_seq
        FROM students
        WHERE student_id LIKE ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$likePattern]);
    $max = (int)$stmt->fetchColumn();
    $next = $max + 1;
    $seq = str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    return "ADMA/{$seq}/{$yearShort}";
}

// -----------------------------
// Initialize
// -----------------------------
$success = '';
$errorMessage = '';
$pending_apps = [];

// ==================== MANUAL REGISTRATION ====================
if (isset($_POST['manual_register'])) {
    // basic sanitization/validation
    $first_name  = trim((string)($_POST['first_name'] ?? ''));
    $middle_name = trim((string)($_POST['middle_name'] ?? ''));
    $last_name   = trim((string)($_POST['last_name'] ?? ''));
    $gender      = trim((string)($_POST['gender'] ?? ''));
    $birth_date  = trim((string)($_POST['birth_date'] ?? ''));
    $phone       = trim((string)($_POST['phone'] ?? ''));
    $email       = trim((string)($_POST['email'] ?? ''));
    $address     = trim((string)($_POST['address'] ?? ''));
    $program     = trim((string)($_POST['program'] ?? ''));
    $department  = trim((string)($_POST['department'] ?? ''));

    // Very basic required checks
    if ($first_name === '' || $last_name === '' || $gender === '' || $phone === '' || $program === '' || $department === '') {
        $errorMessage = 'Please fill required fields: First name, Last name, Gender, Phone, Program, Department.';
    } else {
        // Upload documents (store as blobs per original app)
        $passport_photo = $grade_12_doc = $transcript_doc = null;
        if (!empty($_FILES['passport_photo']['tmp_name']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
            $passport_photo = file_get_contents($_FILES['passport_photo']['tmp_name']);
        }
        if (!empty($_FILES['grade_12_doc']['tmp_name']) && $_FILES['grade_12_doc']['error'] === UPLOAD_ERR_OK) {
            $grade_12_doc = file_get_contents($_FILES['grade_12_doc']['tmp_name']);
        }
        if (!empty($_FILES['transcript_doc']['tmp_name']) && $_FILES['transcript_doc']['error'] === UPLOAD_ERR_OK) {
            $transcript_doc = file_get_contents($_FILES['transcript_doc']['tmp_name']);
        }

        $insertSql = "
            INSERT INTO students (
                student_id, first_name, middle_name, last_name, gender, birth_date,
                phone, email, address, program, department, current_year,
                passport_photo, grade_12_doc, transcript_doc, enrolled_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())
        ";

        $maxAttempts = 5;
        $attempt = 0;
        $inserted = false;

        while ($attempt < $maxAttempts && !$inserted) {
            $attempt++;
            $student_id = generate_student_id($pdo);

            try {
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    $student_id, $first_name, $middle_name, $last_name, $gender, $birth_date,
                    $phone, $email, $address, $program, $department,
                    $passport_photo, $grade_12_doc, $transcript_doc
                ]);

                $inserted = true;
                $success = "Student registered manually: <strong>" . htmlspecialchars((string)$student_id) . "</strong>";
                log_action($pdo, $userId, "Registered student (manual) {$student_id}", ['attempt' => $attempt, 'program' => $program]);

            } catch (PDOException $e) {
                $errNo = $e->errorInfo[1] ?? null;
                // duplicate key
                if ($e->getCode() === '23000' && $errNo == 1062) {
                    // small backoff then retry
                    usleep(100000 * $attempt);
                    continue;
                }
                error_log('Student manual insert failed: ' . $e->getMessage());
                $errorMessage = 'Registration failed: database error.';
                log_action($pdo, $userId, 'Failed manual registration: ' . $e->getMessage());
                break;
            }
        }

        if (!$inserted && empty($errorMessage)) {
            $errorMessage = 'Registration failed due to id generation conflict. Please try again.';
            log_action($pdo, $userId, 'Failed manual registration after retries');
        }
    }
}

// ==================== FROM ACCEPTED APPLICATION ====================
if (isset($_POST['register_from_app'])) {
    $app_id = (int)($_POST['app_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ? AND status = 'accepted'");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($app) {
        $insertSql = "
            INSERT INTO students (
                student_id, first_name, middle_name, last_name, gender, birth_date,
                phone, email, address, program, department, current_year,
                passport_photo, grade_10_doc, grade_12_doc, transcript_doc, bachelor_degree, enrolled_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, NOW())
        ";

        $maxAttempts = 5;
        $attempt = 0;
        $inserted = false;

        while ($attempt < $maxAttempts && !$inserted) {
            $attempt++;
            $student_id = generate_student_id($pdo);

            try {
                $stmtIns = $pdo->prepare($insertSql);
                $stmtIns->execute([
                    $student_id,
                    $app['first_name'], $app['middle_name'] ?? null, $app['last_name'],
                    $app['gender'], $app['birth_date'], $app['phone'], $app['email'] ?? null, $app['address'] ?? null,
                    $app['program'], $app['department'],
                    $app['passport_photo'] ?? null, $app['grade_10_doc'] ?? null, $app['grade_12_doc'] ?? null,
                    $app['transcript_doc'] ?? null, $app['bachelor_degree'] ?? null
                ]);

                $pdo->prepare("UPDATE applications SET status = 'registered' WHERE id = ?")->execute([$app_id]);
                $inserted = true;
                $success = "Student registered from application: <strong>" . htmlspecialchars((string)$student_id) . "</strong>";
                log_action($pdo, $userId, "Registered student from application {$app_id} -> {$student_id}", ['attempt' => $attempt]);

            } catch (PDOException $e) {
                $errNo = $e->errorInfo[1] ?? null;
                if ($e->getCode() === '23000' && $errNo == 1062) {
                    usleep(100000 * $attempt);
                    continue;
                }
                error_log('Register-from-app failed: ' . $e->getMessage());
                $errorMessage = 'Registration failed: database error.';
                log_action($pdo, $userId, 'Failed to register from application: ' . $e->getMessage());
                break;
            }
        }

        if (!$inserted && empty($errorMessage)) {
            $errorMessage = 'Registration failed due to id generation conflict. Please try again.';
            log_action($pdo, $userId, "Failed to register application {$app_id} after retries");
        }
    } else {
        $errorMessage = 'Application not found or not accepted.';
    }
}

// ==================== PROMOTE ====================
if (isset($_POST['promote'])) {
    $id = (int)($_POST['student_id'] ?? 0);
    $stmtProm = $pdo->prepare("SELECT current_year FROM students WHERE id = ?");
    $stmtProm->execute([$id]);
    $current = $stmtProm->fetchColumn();
    if ($current !== false && (int)$current < 6) {
        $pdo->prepare("UPDATE students SET current_year = current_year + 1 WHERE id = ?")->execute([$id]);
        log_action($pdo, $userId, "Promoted student id {$id}");
        $success = 'Student promoted successfully.';
    } else {
        $errorMessage = 'Cannot promote this student.';
    }
}

// ==================== SEARCH & FILTER ====================
$search  = trim((string)($_GET['search'] ?? ''));
$program = trim((string)($_GET['program'] ?? ''));
$year    = trim((string)($_GET['year'] ?? ''));

$where = []; $params = [];
if ($search !== '') {
    $where[] = "(student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}
if ($program !== '') { $where[] = "program = ?"; $params[] = $program; }
if ($year !== '')    { $where[] = "current_year = ?"; $params[] = $year; }

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM students $whereClause ORDER BY enrolled_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================== STATS ====================
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN current_year = 1 THEN 1 ELSE 0 END) as year1,
        SUM(CASE WHEN current_year = 2 THEN 1 ELSE 0 END) as year2,
        SUM(CASE WHEN current_year = 3 THEN 1 ELSE 0 END) as year3,
        SUM(CASE WHEN current_year = 4 THEN 1 ELSE 0 END) as year4,
        SUM(CASE WHEN current_year = 5 THEN 1 ELSE 0 END) as year5,
        SUM(CASE WHEN current_year >= 6 THEN 1 ELSE 0 END) as graduated
    FROM students
")->fetch(PDO::FETCH_ASSOC);

// Log viewing the students management page
log_action($pdo, $userId, 'Viewed Manage Students page');

// ==================== PENDING ACCEPTED APPS ====================
$pending_apps = $pdo->query("SELECT id, application_id, first_name, last_name, program FROM applications WHERE status = 'accepted'")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Manage Students | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- HEADER -->
<header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Manage Students</h1>
            <p class="text-lg opacity-90">Total: <strong><?= htmlspecialchars((string)($stats['total'] ?? '0')) ?></strong> students</p>
        </div>
        <a href="dashboard.php" class="bg-white text-deepblue px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
            Back to Dashboard
        </a>
    </div>
</header>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Success / Error Message -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 text-center text-lg font-bold">
                <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-8 text-center text-lg font-bold">
                <?= htmlspecialchars((string)$errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-xl p-2 mb-8 inline-flex">
            <button onclick="openTab('manual')" id="btn-manual" class="px-8 py-3 rounded-lg font-bold bg-deepblue text-white">Manual Registration</button>
            <button onclick="openTab('fromapp')" id="btn-fromapp" class="px-8 py-3 rounded-lg font-bold bg-gray-200 text-gray-700 hover:bg-gray-300">From Applications</button>
        </div>

        <!-- MANUAL REGISTRATION TAB -->
        <div id="manual" class="bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Manual Student Registration</h2>
            <form method="post" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-6">
                <input type="text" name="first_name" placeholder="First Name" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="middle_name" placeholder="Middle Name (optional)" class="px-4 py-3 border rounded-lg">
                <input type="text" name="last_name" placeholder="Last Name" required class="px-4 py-3 border rounded-lg">
                <select name="gender" required class="px-4 py-3 border rounded-lg">
                    <option value="">Select Gender</option>
                    <option>Male</option>
                    <option>Female</option>
                </select>
                <input type="date" name="birth_date" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="phone" placeholder="Phone Number" required class="px-4 py-3 border rounded-lg">
                <input type="email" name="email" placeholder="Email (optional)" class="px-4 py-3 border rounded-lg">
                <textarea name="address" placeholder="Full Address" class="md:col-span-2 px-4 py-3 border rounded-lg" rows="3"></textarea>

                <select name="program" required class="px-4 py-3 border rounded-lg">
                    <option value="">Select Program</option>
                    <option>Bachelor</option>
                    <option>Masters</option>
                    <option>PhD</option>
                </select>
                <input type="text" name="department" placeholder="Department" required class="px-4 py-3 border rounded-lg">

                <div class="md:col-span-2">
                    <p class="font-bold mb-4">Upload Documents (Optional)</p>
                    <div class="grid md:grid-cols-3 gap-6">
                        <input type="file" name="passport_photo" accept="image/*" class="file-input">
                        <input type="file" name="grade_12_doc" accept=".pdf,image/*" class="file-input">
                        <input type="file" name="transcript_doc" accept=".pdf,image/*" class="file-input">
                    </div>
                </div>

                <div class="md:col-span-2 text-center">
                    <button name="manual_register" class="bg-gradient-to-r from-green-600 to-green-700 text-white font-bold text-xl px-16 py-5 rounded-xl hover:shadow-2xl transition">
                        Register Student Now
                    </button>
                </div>
            </form>
        </div>

        <!-- FROM APPLICATIONS TAB -->
        <div id="fromapp" class="hidden bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Register from Accepted Applications</h2>
            <?php if (!empty($pending_apps)): ?>
            <form method="post" class="flex gap-6 items-end max-w-2xl">
                <select name="app_id" required class="flex-1 px-5 py-4 border-2 rounded-xl text-lg">
                    <option value="">Choose an accepted applicant...</option>
                    <?php foreach ($pending_apps as $a): ?>
                        <option value="<?= (int)$a['id'] ?>">
                            <?= htmlspecialchars((string)($a['application_id'] . ' — ' . $a['first_name'] . ' ' . $a['last_name'] . ' (' . $a['program'] . ')')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button name="register_from_app" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold px-12 py-4 rounded-xl hover:shadow-xl transition">
                    Register Student
                </button>
            </form>
            <?php else: ?>
                <p class="text-center text-gray-600 py-10 text-xl">No accepted applications available.</p>
            <?php endif; ?>
        </div>

        <!-- STATS CARDS -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-6 mb-10">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-deepblue">
                <p class="text-gray-600">Total</p>
                <p class="text-4xl font-bold text-deepblue"><?= htmlspecialchars((string)($stats['total'] ?? '0')) ?></p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-blue-600">
                <p class="text-gray-600">Year 1</p>
                <p class="text-3xl font-bold text-blue-700"><?= htmlspecialchars((string)($stats['year1'] ?? '0')) ?></p>
            </div>
            <div class="bg-indigo-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-indigo-600">
                <p class="text-gray-600">Year 2</p>
                <p class="text-3xl font-bold text-indigo-700"><?= htmlspecialchars((string)($stats['year2'] ?? '0')) ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-purple-600">
                <p class="text-gray-600">Year 3</p>
                <p class="text-3xl font-bold text-purple-700"><?= htmlspecialchars((string)($stats['year3'] ?? '0')) ?></p>
            </div>
            <div class="bg-amber-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-amber-600">
                <p class="text-gray-600">Year 4</p>
                <p class="text-3xl font-bold text-amber-700"><?= htmlspecialchars((string)($stats['year4'] ?? '0')) ?></p>
            </div>
            <div class="bg-green-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-green-600">
                <p class="text-gray-600">Graduated</p>
                <p class="text-3xl font-bold text-green-700"><?= htmlspecialchars((string)($stats['graduated'] ?? '0')) ?></p>
            </div>
        </div>

        <!-- STUDENTS TABLE -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-deepblue text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Student ID</th>
                            <th class="px-6 py-4 text-left">Name</th>
                            <th class="px-6 py-4 text-left">Program</th>
                            <th class="px-6 py-4 text-left">Year</th>
                            <th class="px-6 py-4 text-left">Department</th>
                            <th class="px-6 py-4 text-left">Enrolled</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($students as $s): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-5 font-mono font-bold text-deepblue"><?= htmlspecialchars((string)($s['student_id'] ?? '')) ?></td>
                            <td class="px-6 py-5 font-semibold"><?= htmlspecialchars((string)(($s['first_name'] ?? '') . ' ' . (!empty($s['middle_name']) ? ($s['middle_name'] . ' ') : '') . ($s['last_name'] ?? ''))) ?></td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    <?= (($s['program'] ?? '') === 'Bachelor') ? 'bg-blue-100 text-blue-800' : ((($s['program'] ?? '') === 'Masters') ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800') ?>">
                                    <?= htmlspecialchars((string)($s['program'] ?? '')) ?>
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center font-bold <?= (((int)($s['current_year'] ?? 0)) >= 6) ? 'text-green-600' : '' ?>">
                                <?= (((int)($s['current_year'] ?? 0)) >= 6) ? 'Graduated' : 'Year ' . htmlspecialchars((string)($s['current_year'] ?? '1')) ?>
                            </td>
                            <td class="px-6 py-5"><?= htmlspecialchars((string)($s['department'] ?? '')) ?></td>
                            <td class="px-6 py-5 text-sm text-gray-600"><?= !empty($s['enrolled_at']) ? htmlspecialchars((string)date('M j, Y', strtotime((string)$s['enrolled_at']))) : '-' ?></td>
                            <td class="px-6 py-5 text-center">
                                <?php if (((int)($s['current_year'] ?? 0)) < 6): ?>
                                <form method="post" class="inline">
                                    <input type="hidden" name="student_id" value="<?= (int)($s['id'] ?? 0) ?>">
                                    <button name="promote" onclick="return confirm('Promote to next year?')" 
                                            class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                                        Promote
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                        <tr>
                            <td class="px-6 py-6 text-center" colspan="7">No students found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(tab) {
    document.getElementById('manual').classList.toggle('hidden', tab !== 'manual');
    document.getElementById('fromapp').classList.toggle('hidden', tab !== 'fromapp');

    document.getElementById('btn-manual').classList.toggle('bg-deepblue', tab === 'manual');
    document.getElementById('btn-manual').classList.toggle('text-white', tab === 'manual');
    document.getElementById('btn-manual').classList.toggle('bg-gray-200', tab !== 'manual');
    document.getElementById('btn-manual').classList.toggle('text-gray-700', tab !== 'manual');

    document.getElementById('btn-fromapp').classList.toggle('bg-deepblue', tab === 'fromapp');
    document.getElementById('btn-fromapp').classList.toggle('text-white', tab === 'fromapp');
    document.getElementById('btn-fromapp').classList.toggle('bg-gray-200', tab !== 'fromapp');
    document.getElementById('btn-fromapp').classList.toggle('text-gray-700', tab !== 'fromapp');
}

// preserve tab via query param if present
(function(){
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab') || 'manual';
    openTab(tab);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
