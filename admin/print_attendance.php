<?php
require_once __DIR__ . '/../includes/db.php';
$date = $_GET['date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'student';

if ($type == 'student') {
    $stmt = $pdo->prepare("SELECT sa.*, s.student_id, s.first_name, s.last_name, s.current_year, s.program, sec.section_name, c.class_name 
                           FROM student_attendance sa 
                           JOIN students s ON sa.student_id = s.id 
                           JOIN sections sec ON sa.section_id = sec.id 
                           JOIN classes c ON sec.class_id = c.id 
                           WHERE DATE(sa.attendance_date) = ?");
    $title = "Student Attendance Report";
} else {
    $stmt = $pdo->prepare("SELECT ta.*, t.first_name, t.last_name, t.teacher_id 
                           FROM teacher_attendance ta 
                           JOIN teachers t ON ta.teacher_id = t.id 
                           WHERE DATE(ta.attendance_date) = ?");
    $title = "Teacher Attendance Report";
}
$stmt->execute([$date]);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?> - <?= date('d M Y', strtotime($date)) ?></title>
    <style>
        body { font-family: Arial; padding: 40px; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        img { height: 80px; }
        @media print { body { padding: 10px; } }
    </style>
</head>
<body>
    <h1>School Name</h1>
    <h2><?= $title ?><br><?= date('d F Y', strtotime($date)) ?></h2>
    <p>Total Records: <strong><?= count($rows) ?></strong></p>
    <table>
        <?php if ($type == 'student'): ?>
            <tr><th>ID</th><th>Name</th><th>Year</th><th>Section</th><th>Course</th><th>Signature</th></tr>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?= $r['student_id'] ?></td>
                <td><?= $r['first_name'].' '.$r['last_name'] ?></td>
                <td><?= $r['current_year'] ?> - <?= $r['program'] ?></td>
                <td><?= $r['class_name'] ?> - <?= $r['section_name'] ?></td>
                <td><?= $r['course_name'] ?></td>
                <td><?php if($r['signature']): ?><img src="data:image/jpeg;base64,<?= base64_encode($r['signature']) ?>"><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><th>Teacher</th><th>Class - Section</th><th>Time</th><th>Signature</th></tr>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?= $r['first_name'].' '.$r['last_name'] ?></td>
                <td><?= $r['class_taught'] ?> - <?= $r['section'] ?></td>
                <td><?= $r['arrival_time'] ?> to <?= $r['finish_time'] ?></td>
                <td><?php if($r['signature']): ?><img src="data:image/jpeg;base64,<?= base64_encode($r['signature']) ?>"><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <script>window.print();</script>
</body>
</html>