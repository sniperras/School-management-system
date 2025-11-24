<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

/* ==================== TEACHER LOADER (100% safe) ==================== */
$user_id = (string)($_SESSION['user_id'] ?? '');
$teacher = null;

// 1. Login using teacher_id like tch/0001/25
if ($user_id && preg_match('/^tch\/\d+\/\d+$/i', $user_id)) {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. Login using email
if (!$teacher && !empty($_SESSION['user_email'])) {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_email']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3. Login using numeric user.id → link via users table
if (!$teacher && is_numeric($user_id)) {
    $stmt = $pdo->prepare("
        SELECT t.* FROM teachers t
        JOIN users u ON u.email = t.email OR u.username = t.teacher_id
        WHERE u.id = ? AND u.role = 'teacher' LIMIT 1
    ");
    $stmt->execute([(int)$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4. Final fallback
if (!$teacher && is_numeric($user_id)) {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$teacher) {
    die("<div class='text-center py-5'><h1 class='text-danger'>Teacher profile not found.</h1><a href='../logout.php' class='btn btn-primary'>Login again</a></div>");
}

$teacher_id = $teacher['id'];
$_SESSION['teacher'] = $teacher;

/* ==================== 1. ASSIGNED CLASSES ==================== */
$assigned_classes = [];

$stmt = $pdo->prepare("
    SELECT ca.subject_name, c.class_name, sec.section_name
    FROM class_assignments ca
    JOIN sections sec ON ca.section_id = sec.id
    JOIN classes c ON sec.class_id = c.id
    WHERE ca.teacher_id = ?
");
$stmt->execute([$teacher_id]);
$assigned_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback: teacher_subjects table (many projects use this)
if (empty($assigned_classes)) {
    $stmt = $pdo->prepare("
        SELECT subject_name, class_level AS class_name, NULL AS section_name
        FROM teacher_subjects 
        WHERE teacher_id = ?
    ");
    $stmt->execute([$teacher_id]);
    $assigned_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ==================== 2. TODAY'S TIMETABLE ==================== */
$today = date('l'); // Monday, Tuesday, etc.
$stmt = $pdo->prepare("
    SELECT t.start_time, t.end_time, t.room, c.class_name, s.subject_name
    FROM timetable t
    JOIN classes c ON t.class_id = c.id
    JOIN subjects s ON t.subject_id = s.id
    WHERE t.teacher_id = ? AND t.day = ?
    ORDER BY t.start_time
");
$stmt->execute([$teacher_id, $today]);
$today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== 3. ANNOUNCEMENTS (last 7 days only) ==================== */
$announcements = $pdo->query("
    SELECT title, message, created_at 
    FROM announcements 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Teacher Dashboard | <?= htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.15)!important; }
    .ann-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; }
</style>

<div class="min-h-screen bg-light">

    <!-- Header -->
    <header class="bg-primary text-white py-5 shadow-lg">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="display-5 fw-bold mb-0">
                        Welcome, <?= htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']) ?>
                    </h1>
                    <p class="mb-0 fs-5">
                        ID: <strong><?= $teacher['teacher_id'] ?></strong> | 
                        Dept: <strong><?= htmlspecialchars($teacher['department'] ?? 'N/A') ?></strong>
                    </p>
                </div>
                <div class="col-auto">
                    <a href="../logout.php" class="btn btn-outline-light btn-lg">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container my-5">

        <!-- Announcements (7 days only) -->
        <?php if ($announcements): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-lg ann-card">
                    <div class="card-header bg-white text-dark">
                        <h4>Recent Announcements (Visible 7 days only)</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($announcements as $a): ?>
                        <div class="text-white p-3 mb-3 rounded">
                            <strong><?= htmlspecialchars($a['title']) ?></strong>
                            <small class="float-end"><?= date('d M Y', strtotime($a['created_at'])) ?></small>
                            <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- My Assigned Classes -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h5>My Assigned Classes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($assigned_classes): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($assigned_classes as $c): ?>
                            <li class="list-group-item">
                                <strong>
                                    <?= htmlspecialchars($c['class_name'] ?? 'N/A') ?>
                                    <?php if (!empty($c['section_name'])): ?>
                                        - Section <?= htmlspecialchars($c['section_name']) ?>
                                    <?php endif; ?>
                                </strong><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($c['subject_name'] ?? 'N/A') ?>
                                </small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-center text-muted">No classes assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-lg">
                    <div class="card-header bg-warning text-dark">
                        <h5>Today's Schedule — <?= date('l, d M Y') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($today_schedule): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($today_schedule as $s): ?>
                            <li class="list-group-item">
                                <strong><?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?></strong>
                                <span class="badge bg-primary float-end">Room <?= htmlspecialchars($s['room']) ?></span><br>
                                <small><?= htmlspecialchars($s['class_name']) ?> → <?= htmlspecialchars($s['subject_name']) ?></small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-center text-success fs-4">No classes today — Enjoy your free day!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Quick Action Buttons -->
        <div class="row mt-5 g-4 text-center">
            <div class="col-md-3"><a href="attendance/mark_attendance.php" class="btn btn-success btn-lg w-100 py-4"><i class="fas fa-clipboard-check fa-2x"></i><br>Mark Attendance</a></div>
            <div class="col-md-3"><a href="exams/create_exam.php" class="btn btn-primary btn-lg w-100 py-4"><i class="fas fa-file-alt fa-2x"></i><br>Create Exam</a></div>
            <div class="col-md-3"><a href="exams/my_exams.php" class="btn btn-primary btn-lg w-100 py-4"><i class="fas fa-file-alt fa-2x"></i><br>My Exams</a></div>
            <div class="col-md-3"><a href="exams/pending_approvals.php" class="btn btn-warning btn-lg w-100 py-4"><i class="fas fa-check-double fa-2x"></i><br>To Approve Exams</a></div>
            <div class="col-md-3"><a href="exams/enter_marks.php" class="btn btn-info btn-lg w-100 py-4"><i class="fas fa-edit fa-2x"></i><br>Enter Marks</a></div>
            <div class="col-md-3"><a href="announcements/create.php" class="btn btn-danger btn-lg w-100 py-4"><i class="fas fa-bullhorn fa-2x"></i><br>Send Notice</a></div>
            <div class="col-md-3"><a href="reports/" class="btn btn-secondary btn-lg w-100 py-4"><i class="fas fa-chart-bar fa-2x"></i><br>Reports</a></div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>