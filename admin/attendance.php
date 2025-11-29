<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = $error = '';

// ==================== RECORD ATTENDANCE (same as before) ====================
if (isset($_POST['record_teacher_attendance'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $class_taught = trim($_POST['class_taught']);
    $section = trim($_POST['section']);
    $arrival_time = $_POST['arrival_time'];
    $finish_time = $_POST['finish_time'];
    $attendance_date = $_POST['attendance_date'];
    $signature = !empty($_FILES['signature']['tmp_name']) ? file_get_contents($_FILES['signature']['tmp_name']) : null;

    $stmt = $pdo->prepare("INSERT INTO teacher_attendance (teacher_id, class_taught, section, arrival_time, finish_time, attendance_date, signature) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$teacher_id, $class_taught, $section, $arrival_time, $finish_time, $attendance_date, $signature]);
    $success = "Teacher attendance recorded!";
}

if (isset($_POST['record_student_attendance'])) {
    $student_id = (int)$_POST['student_id'];
    $section_id = (int)$_POST['section_id'];
    $course_name = trim($_POST['course_name']);
    $attendance_date = $_POST['attendance_date'];
    $signature = !empty($_FILES['signature']['tmp_name']) ? file_get_contents($_FILES['signature']['tmp_name']) : null;

    $check = $pdo->prepare("SELECT id FROM student_attendance WHERE student_id = ? AND section_id = ? AND DATE(attendance_date) = ?");
    $check->execute([$student_id, $section_id, $attendance_date]);
    if ($check->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO student_attendance (student_id, section_id, course_name, attendance_date, signature) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $section_id, $course_name, $attendance_date, $signature]);
        log_action($pdo, $_SESSION['user_id'] ?? null, "Student attendance recorded {$student_id}");
        $success = "Student attendance recorded!";
    } else {
        $error = "Already recorded!";
    }
}

// Get filter date
$date_filter = $_GET['date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'student';

// Load data for forms
$teachers = $pdo->query("SELECT id, teacher_id, first_name, last_name FROM teachers ORDER BY first_name")->fetchAll();
$students = $pdo->query("SELECT s.id, s.student_id, s.first_name, s.last_name, s.current_year, s.program FROM students s ORDER BY s.current_year, s.first_name")->fetchAll();
$sections = $pdo->query("SELECT s.id, s.section_name, c.class_name FROM sections s JOIN classes c ON s.class_id = c.id ORDER BY c.class_name")->fetchAll();

// Reports - NOW REFRESH PROPERLY
$student_att = $pdo->prepare("
    SELECT sa.*, s.student_id, s.first_name, s.last_name, s.current_year, s.program,
           sec.section_name, c.class_name
    FROM student_attendance sa
    JOIN students s ON sa.student_id = s.id
    JOIN sections sec ON sa.section_id = sec.id
    JOIN classes c ON sec.class_id = c.id
    WHERE DATE(sa.attendance_date) = ?
    ORDER BY c.class_name, sec.section_name, s.first_name
");
$student_att->execute([$date_filter]);
$student_rows = $student_att->fetchAll();

$teacher_att = $pdo->prepare("
    SELECT ta.*, t.teacher_id, t.first_name, t.last_name 
    FROM teacher_attendance ta
    JOIN teachers t ON ta.teacher_id = t.id
    WHERE DATE(ta.attendance_date) = ?
    ORDER BY ta.arrival_time
");
$teacher_att->execute([$date_filter]);
$teacher_rows = $teacher_att->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Attendance Management | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
        <h1 class="text-3xl font-bold">Attendance Management</h1>
        <a href="dashboard.php" class="bg-white text-deepblue px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
            Back to Dashboard
        </a>
    </div>
</header>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-10">

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 text-center text-lg font-bold">
                <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-8 text-center font-bold">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-xl p-2 mb-8 inline-flex">
            <a href="?type=teacher&date=<?= $date_filter ?>" class="px-8 py-3 rounded-lg font-bold <?= $report_type=='teacher'?'bg-deepblue text-white':'bg-gray-200 hover:bg-gray-300' ?>">Teacher</a>
            <a href="?type=student&date=<?= $date_filter ?>" class="px-8 py-3 rounded-lg font-bold <?= $report_type=='student'?'bg-deepblue text-white':'bg-gray-200 hover:bg-gray-300' ?>">Student</a>
        </div>

        <!-- Forms (same as before) -->
        <div id="teacher-form" class="<?= $report_type=='teacher'?'block':'hidden' ?> bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Record Teacher Attendance</h2>
            <form method="post" enctype="multipart/form-data" class="grid md:grid-cols-3 gap-6">
                <select name="teacher_id" required class="px-4 py-3 border rounded-lg"><option value="">Select Teacher</option><?php foreach($teachers as $t):?><option value="<?=$t['id']?>"><?=$t['teacher_id']?> — <?=$t['first_name']?> <?=$t['last_name']?></option><?php endforeach;?></select>
                <input type="text" name="class_taught" placeholder="Class Taught" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="section" placeholder="Section" required class="px-4 py-3 border rounded-lg">
                <input type="time" name="arrival_time" required class="px-4 py-3 border rounded-lg">
                <input type="time" name="finish_time" required class="px-4 py-3 border rounded-lg">
                <input type="date" name="attendance_date" value="<?=date('Y-m-d')?>" required class="px-4 py-3 border rounded-lg">
                <div class="md:col-span-3"><input type="file" name="signature" accept="image/*" required class="block w-full"></div>
                <div class="md:col-span-3 text-center">
                    <button name="record_teacher_attendance" class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white font-bold text-xl px-16 py-5 rounded-xl hover:shadow-2xl">
                        Record Teacher Attendance
                    </button>
                </div>
            </form>
        </div>

        <div id="student-form" class="<?= $report_type=='student'?'block':'hidden' ?> bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Record Student Attendance</h2>
            <form method="post" enctype="multipart/form-data" class="grid md:grid-cols-3 gap-6">
                <select name="student_id" required class="px-4 py-3 border rounded-lg"><option value="">Select Student</option><?php foreach($students as $s):?><option value="<?=$s['id']?>"><?=$s['student_id']?> — <?=$s['first_name']?> <?=$s['last_name']?> (Year <?=$s['current_year']?>)</option><?php endforeach;?></select>
                <select name="section_id" required class="px-4 py-3 border rounded-lg"><option value="">Select Section</option><?php foreach($sections as $sec):?><option value="<?=$sec['id']?>"><?=$sec['class_name']?> - <?=$sec['section_name']?></option><?php endforeach;?></select>
                <input type="text" name="course_name" placeholder="Course Name" required class="px-4 py-3 border rounded-lg">
                <input type="date" name="attendance_date" value="<?=date('Y-m-d')?>" required class="px-4 py-3 border rounded-lg">
                <div class="md:col-span-3"><input type="file" name="signature" accept="image/*" required class="block w-full"></div>
                <div class="md:col-span-3 text-center">
                    <button name="record_student_attendance" class="bg-gradient-to-r from-green-600 to-emerald-700 text-white font-bold text-xl px-16 py-5 rounded-xl hover:shadow-2xl">
                        Record Student Attendance
                    </button>
                </div>
            </form>
        </div>

        <!-- REPORT WITH PRINT BUTTON -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-deepblue">
                        <?= $report_type == 'teacher' ? 'Teacher' : 'Student' ?> Attendance Report
                    </h2>
                    <p class="text-lg text-gray-700">Date: <strong><?= date('d M Y', strtotime($date_filter)) ?></strong></p>
                </div>
                <div class="flex gap-4">
                    <form method="get" class="flex gap-3">
                        <input type="date" name="date" value="<?= $date_filter ?>" required class="px-4 py-2 border rounded-lg">
                        <input type="hidden" name="type" value="<?= $report_type ?>">
                        <button class="bg-deepblue text-white px-6 py-2 rounded-lg hover:bg-blue-800">View</button>
                    </form>
                    <a href="print_attendance.php?type=<?= $report_type ?>&date=<?= $date_filter ?>" target="_blank"
                       class="bg-red-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-red-700 flex items-center gap-2">
                        Print Report
                    </a>
                </div>
            </div>

            <div class="p-6">
                <?php if ($report_type == 'student'): ?>
                    <?php if ($student_rows): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-deepblue text-white">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Student ID</th>
                                        <th class="px-6 py-4 text-left">Name</th>
                                        <th class="px-6 py-4 text-left">Year/Program</th>
                                        <th class="px-6 py-4 text-left">Section</th>
                                        <th class="px-6 py-4 text-left">Course</th>
                                        <th class="px-6 py-4 text-left">Signature</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($student_rows as $r): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-mono"><?= $r['student_id'] ?></td>
                                        <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
                                        <td class="px-6 py-4">Year <?= $r['current_year'] ?> - <?= $r['program'] ?></td>
                                        <td class="px-6 py-4"><?= $r['class_name'] ?> - <?= $r['section_name'] ?></td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($r['course_name']) ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($r['signature']): ?>
                                                <img src="data:image/jpeg;base64,<?= base64_encode($r['signature']) ?>" class="h-16 rounded border cursor-zoom-in" onclick="window.open(this.src)">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-12 text-xl">No student attendance recorded for this date.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <?php if ($teacher_rows): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-deepblue text-white">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Teacher</th>
                                        <th class="px-6 py-4 text-left">Class - Section</th>
                                        <th class="px-6 py-4 text-left">Time</th>
                                        <th class="px-6 py-4 text-left">Signature</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($teacher_rows as $r): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
                                        <td class="px-6 py-4"><?= $r['class_taught'] ?> - <?= $r['section'] ?></td>
                                        <td class="px-6 py-4"><?= $r['arrival_time'] ?> to <?= $r['finish_time'] ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($r['signature']): ?>
                                                <img src="data:image/jpeg;base64,<?= base64_encode($r['signature']) ?>" class="h-16 rounded border cursor-zoom-in" onclick="window.open(this.src)">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-12 text-xl">No teacher attendance recorded.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
