<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

/* ==================== TEACHER LOADER (unchanged) ==================== */
$user_id = (string)($_SESSION['user_id'] ?? '');
$teacher = null;

if ($user_id && preg_match('/^tch\/\d+\/\d+$/i', $user_id)) {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE teacher_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$teacher && !empty($_SESSION['user_email'])) {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE email = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_email']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$teacher && is_numeric($user_id)) {
    $stmt = $pdo->prepare("SELECT t.* FROM teachers t JOIN users u ON u.email = t.email OR u.username = t.teacher_id WHERE u.id = ? AND u.role = 'teacher' LIMIT 1");
    $stmt->execute([(int)$user_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
}
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

/* ==================== ASSIGNED CLASSES & TODAY'S SCHEDULE ==================== */
$assigned_classes = [];
$stmt = $pdo->prepare("SELECT ca.subject_name, c.class_name, sec.section_name FROM class_assignments ca JOIN sections sec ON ca.section_id = sec.id JOIN classes c ON sec.class_id = c.id WHERE ca.teacher_id = ?");
$stmt->execute([$teacher_id]);
$assigned_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($assigned_classes)) {
    $stmt = $pdo->prepare("SELECT subject_name, class_level AS class_name, NULL AS section_name FROM teacher_subjects WHERE teacher_id = ?");
    $stmt->execute([$teacher_id]);
    $assigned_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$today = date('l');
$stmt = $pdo->prepare("SELECT t.start_time, t.end_time, t.room, c.class_name, s.subject_name FROM timetable t JOIN classes c ON t.class_id = c.id JOIN subjects s ON t.subject_id = s.id WHERE t.teacher_id = ? AND t.day = ? ORDER BY t.start_time");
$stmt->execute([$teacher_id, $today]);
$today_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== LATEST 3 NEWS FROM news TABLE (7 days only) ==================== */
$news_items = $pdo->query("
    SELECT n.*, t.first_name, t.last_name 
    FROM news n 
    LEFT JOIN teachers t ON n.created_by = t.id 
    WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY n.created_at DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Teacher Dashboard | <?= htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .card-hover:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.15)!important; transition: all 0.3s; }
    .news-card { min-height: 280px; position: relative; overflow: hidden; }
    .news-image { height: 160px; object-fit: cover; width: 100%; border-radius: 12px 12px 0 0; }
    .type-badge { position: absolute; top: 12px; right: 12px; z-index: 10; font-size: 0.8rem; padding: 6px 12px; border-radius: 50px; color: white; font-weight: bold; }
    .type-general { background: #3b82f6; }
    .type-exam { background: #10b981; }
    .type-event { background: #8b5cf6; }
    .type-holiday { background: #f59e0b; }
    .type-urgent { background: #ef4444; animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
</style>

<div class="min-h-screen bg-light">

    <!-- Header -->
    <header class="bg-primary text-white py-5 shadow-lg">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="display-5 fw-bold mb-0">
                        Welcome back, <?= htmlspecialchars($teacher['first_name'].' '.$teacher['last_name']) ?>
                    </h1>
                    <p class="mb-0 fs-5">
                        ID: <strong><?= $teacher['teacher_id'] ?></strong> | 
                        Department: <strong><?= htmlspecialchars($teacher['department'] ?? 'N/A') ?></strong>
                    </p>
                </div>
                <div class="col-auto">
                    <a href="../logout.php" class="btn btn-outline-light btn-lg">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container my-5">

        <!-- Latest 3 News (7 days only) -->
        <?php if ($news_items): ?>
        <div class="mb-5">
            <h2 class="h4 fw-bold text-primary mb-4">
                Latest Announcements 
                <span class="text-muted fs-6">(Visible for 7 days only • Showing latest 3)</span>
            </h2>
            <div class="row g-4">
                <?php foreach ($news_items as $n): 
                    $type_class = 'type-' . $n['type'];
                    $border_color = $n['type'] === 'exam' ? '#f59e0b' : ($n['type'] === 'urgent' ? '#ef4444' : '#10b981');
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card news-card border-0 shadow-lg card-hover h-100" style="border-left: 6px solid <?= $border_color ?>;">
                            <div class="type-badge <?= $type_class ?>">
                                <?= ucfirst($n['type']) ?>
                            </div>

                            <?php if ($n['image']): ?>
                                <img src="data:<?= htmlspecialchars($n['image_type']) ?>;base64,<?= base64_encode($n['image']) ?>" 
                                     alt="News Image" class="news-image">
                            <?php else: ?>
                                <div class="bg-gradient-to-br from-gray-200 to-gray-300 news-image d-flex align-items-center justify-content-center">
                                    <i class="fas fa-bullhorn text-6xl text-gray-500 opacity-50"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column p-4">
                                <h5 class="fw-bold text-dark mb-2">
                                    <?= htmlspecialchars($n['title']) ?>
                                </h5>
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($n['created_at'])) ?>
                                    <?php if ($n['first_name']): ?>
                                        • by <?= htmlspecialchars($n['first_name'].' '.$n['last_name']) ?>
                                    <?php endif; ?>
                                </p>
                                <div class="flex-grow-1 text-gray-700">
                                    <?php if ($n['type'] === 'exam' && !empty(json_decode($n['message'], true))): 
                                        // For future structured exam data
                                        echo '<p class="mb-0 text-success fw-bold">Exam Schedule Published!</p>';
                                    else: ?>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-5 bg-white rounded-3 shadow">
            <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 4rem;"></i>
            <p class="text-muted fs-4">No recent announcements</p>
        </div>
        <?php endif; ?>

        <!-- Rest of your dashboard (classes, schedule, buttons) -->
        <div class="row g-4 mt-3">
            <!-- My Assigned Classes -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-lg card-hover">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">My Assigned Classes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($assigned_classes): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($assigned_classes as $c): ?>
                            <li class="list-group-item py-3">
                                <strong class="text-primary"><?= htmlspecialchars($c['class_name'] ?? 'N/A') ?>
                                    <?php if (!empty($c['section_name'])): ?> - <?= htmlspecialchars($c['section_name']) ?><?php endif; ?>
                                </strong><br>
                                <small class="text-success fw-bold"><?= htmlspecialchars($c['subject_name'] ?? 'General') ?></small>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-center text-muted py-5">No classes assigned yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-lg card-hover">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Today's Schedule — <?= date('l, d M Y') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if ($today_schedule): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($today_schedule as $s): ?>
                            <li class="list-group-item py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-primary"><?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?></strong><br>
                                    <small><?= htmlspecialchars($s['class_name']) ?> → <?= htmlspecialchars($s['subject_name']) ?></small>
                                </div>
                                <span class="badge bg-danger fs-6 px-3 py-2">Room <?= htmlspecialchars($s['room']) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <div class="text-center py-5 text-success">
                            <i class="fas fa-coffee fa-4x mb-3"></i>
                            <p class="fs-4 fw-bold">No classes today — Enjoy!</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-5 g-4 text-center">
            <div class="col-md-3"><a href="attendance/mark_attendance.php" class="btn btn-success btn-lg w-100 py-4 shadow card-hover"><i class="fas fa-clipboard-check fa-2x mb-2"></i><br>Mark Attendance</a></div>
            <div class="col-md-3"><a href="exams/create_exam.php" class="btn btn-primary btn-lg w-100 py-4 shadow card-hover"><i class="fas fa-file-alt fa-2x mb-2"></i><br>Create Exam</a></div>
            <div class="col-md-3"><a href="exams/my_exams.php" class="btn btn-info btn-lg w-100 py-4 shadow card-hover text-white"><i class="fas fa-list-alt fa-2x mb-2"></i><br>My Exams</a></div>
            <div class="col-md-3"><a href="exams/pending_approvals.php" class="btn btn-warning btn-lg w-100 py-4 shadow card-hover text-dark"><i class="fas fa-check-double fa-2x mb-2"></i><br>Approve Exams</a></div>
            <div class="col-md-3"><a href="exams/enter_marks.php" class="btn btn-purple btn-lg w-100 py-4 shadow card-hover text-white" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9); border: none;">
                <i class="fas fa-edit fa-2x mb-2"></i><br>Enter Marks
            </a></div>
            <div class="col-md-3"><a href="announcements/create.php" class="btn btn-danger btn-lg w-100 py-4 shadow card-hover"><i class="fas fa-bullhorn fa-2x mb-2"></i><br>Send Notice</a></div>
            <div class="col-md-3"><a href="reports.php" class="btn btn-secondary btn-lg w-100 py-4 shadow card-hover"><i class="fas fa-chart-bar fa-2x mb-2"></i><br>Reports</a></div>
        </div>

    </div>
</div>