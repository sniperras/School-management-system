<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

// ============================================
// UNIVERSAL TEACHER PROFILE LOADER (2025+)
// ============================================
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("<h2 style='text-align:center;padding:100px;color:red;'>Session Expired</h2>");
}

$teacher = null;

// METHOD 1: Most common — user logged in with teacher_id (tch/0001/25) as username
if (is_string($user_id) && preg_match('/^tch\/\d+\/\d+$/i', $user_id)) {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// METHOD 2: User logged in with email or username → find via email match
if (!$teacher) {
    $current_user_email = $_SESSION['user_email'] ?? null;
    if ($current_user_email) {
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ? LIMIT 1");
        $stmt->execute([$current_user_email]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// METHOD 3: Fallback — try linking via users.student_id → but for teachers, use teacher_id field
// (Some systems store teacher_id in users table — yours doesn't, but this is safe)
if (!$teacher && !empty($_SESSION['user_id']) && is_string($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    if (preg_match('/^tch\/\d+\/\d+$/i', $uid)) {
        $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ?");
        $stmt->execute([$uid]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// FINAL FALLBACK: If admin accidentally used numeric ID in session
if (!$teacher && is_numeric($user_id)) {
    $stmt = $pdo->prepare("
        SELECT t.* FROM teachers t 
        JOIN users u ON u.email = t.email OR u.username = t.teacher_id 
        WHERE u.id = ? AND u.role = 'teacher'
    ");
    $stmt->execute([(int)$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// STILL NO TEACHER? Show helpful error
if (!$teacher) {
    echo "<div style='max-width:700px;margin:80px auto;padding:40px;background:#fff;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);text-align:center;font-family:Arial,sans-serif;'>
        <h1 style='color:#e74c3c;font-size:3em;margin-bottom:20px;'>Teacher Profile Not Found</h1>
        <hr style='margin:30px 0;'>
        <p><strong>Logged in as:</strong> " . htmlspecialchars($_SESSION['user_email'] ?? 'Unknown') . "</p>
        <p><strong>Session ID:</strong> " . htmlspecialchars($user_id) . "</p>
        <p>This account exists in the <code>users</code> table, but no matching record in <code>teachers</code> table.</p>
        <p><strong>Solution:</strong> Ask admin to check that your email <code>" . htmlspecialchars($_SESSION['user_email'] ?? '') . "</code> exists in the <code>teachers</code> table.</p>
        <br>
        <a href='../logout.php' style='background:#3498db;color:white;padding:15px 40px;border-radius:50px;text-decoration:none;font-size:1.2em;'>Back to Login</a>
    </div>";
    exit;
}

// SUCCESS! Store teacher info in session for future use (optional)
$_SESSION['teacher'] = $teacher;
$_SESSION['teacher_id'] = $teacher['id'];
$_SESSION['teacher_code'] = $teacher['teacher_id'];

?>

<!-- Rest of your dashboard (now works perfectly) -->
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Teacher Dashboard - <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">
                Welcome, <?= htmlspecialchars($teacher['first_name'] . ' ' . ($teacher['middle_name'] ? $teacher['middle_name'].' ' : '') . $teacher['last_name']) ?>
            </h1>
            <p class="text-xl opacity-90">
                Teacher ID: <strong><?= htmlspecialchars($teacher['teacher_id']) ?></strong>
                | Department: <?= htmlspecialchars($teacher['department'] ?? 'N/A') ?>
            </p>
        </div>
        <div class="flex gap-4">
            <a href="attendance.php" class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition">
                Mark Attendance
            </a>
            <a href="../logout.php" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition">
                Logout
            </a>
        </div>
    </div>
</header>

<!-- Rest of your beautiful dashboard goes here... -->
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-10">
        <h2 class="text-4xl font-bold text-center text-deepblue mb-10">Teacher Dashboard</h2>
        <p class="text-center text-gray-600 text-xl">Welcome back! You're successfully logged in.</p>
        <!-- Add your cards, forms, etc. here -->
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>