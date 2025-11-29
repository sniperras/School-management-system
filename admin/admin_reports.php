<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// === BULK DOWNLOADS ===
if (isset($_GET['download_all_teachers'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="all_teachers_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ALL TEACHERS REPORT', date('d M Y')]);
    fputcsv($out, ['ID','Name','Department','Attendance','Exams Created','Exams Approved','Marks Entered','Notices Sent']);
    $stmt = $pdo->query("SELECT t.teacher_id, CONCAT(t.first_name,' ',COALESCE(t.middle_name,' '),' ',t.last_name), t.department,
           COALESCE(a.cnt,0), COALESCE(e.cnt,0), COALESCE(ap.cnt,0), COALESCE(m.cnt,0), COALESCE(n.cnt,0)
           FROM teachers t
           LEFT JOIN (SELECT marked_by,COUNT(*) cnt FROM attendance GROUP BY marked_by) a ON a.marked_by=t.id
           LEFT JOIN (SELECT created_by,COUNT(*) cnt FROM exams GROUP BY created_by) e ON e.created_by=t.id
           LEFT JOIN (SELECT teacher_id,COUNT(*) cnt FROM exam_approvals WHERE approved=1 GROUP BY teacher_id) ap ON ap.teacher_id=t.id
           LEFT JOIN (SELECT entered_by,COUNT(DISTINCT exam_id) cnt FROM student_marks GROUP BY entered_by) m ON m.entered_by=t.id
           LEFT JOIN (SELECT created_by,COUNT(*) cnt FROM news GROUP BY created_by) n ON n.created_by=t.id");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) fputcsv($out, $row);
    exit;
}

if (isset($_GET['download_all_students'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="all_students_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ALL STUDENTS REPORT', date('d M Y')]);
    fputcsv($out, ['ID','Name','Year','Section','Attendance %','Overall %','Present','Total Days']);
    $stmt = $pdo->query("SELECT s.student_id, CONCAT(s.first_name,' ',COALESCE(s.middle_name,' '),' ',s.last_name),
           s.current_year, s.section,
           ROUND(COALESCE(p.cnt/NULLIF(t.cnt,0),0)*100,1),
           ROUND(COALESCE(o.total/NULLIF(p2.total,0),0)*100,1),
           COALESCE(p.cnt,0), COALESCE(t.cnt,0)
           FROM students s
           LEFT JOIN (SELECT student_id,COUNT(*) cnt FROM attendance WHERE status='Present' GROUP BY student_id) p ON p.student_id=s.id
           LEFT JOIN (SELECT student_id,COUNT(*) cnt FROM attendance GROUP BY student_id) t ON t.student_id=s.id
           LEFT JOIN (SELECT student_id,SUM(marks_obtained) total FROM student_marks GROUP BY student_id) o ON o.student_id=s.id
           LEFT JOIN (SELECT sm.student_id,SUM(e.total_marks) total FROM student_marks sm JOIN exams e ON sm.exam_id=e.id GROUP BY sm.student_id) p2 ON p2.student_id=s.id");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) fputcsv($out, $row);
    exit;
}

// === GET DATA ===
$all_teachers = $pdo->query("SELECT t.id, t.teacher_id, CONCAT(t.first_name,' ',COALESCE(t.middle_name,' '),' ',t.last_name) AS name, t.department, t.photo,
       COALESCE(a.cnt,0) attendance, COALESCE(e.cnt,0) exams, COALESCE(ap.cnt,0) approved, COALESCE(m.cnt,0) marks, COALESCE(n.cnt,0) notices
       FROM teachers t
       LEFT JOIN (SELECT marked_by,COUNT(*) cnt FROM attendance GROUP BY marked_by) a ON a.marked_by=t.id
       LEFT JOIN (SELECT created_by,COUNT(*) cnt FROM exams GROUP BY created_by) e ON e.created_by=t.id
       LEFT JOIN (SELECT teacher_id,COUNT(*) cnt FROM exam_approvals WHERE approved=1 GROUP BY teacher_id) ap ON ap.teacher_id=t.id
       LEFT JOIN (SELECT entered_by,COUNT(DISTINCT exam_id) cnt FROM student_marks GROUP BY entered_by) m ON m.entered_by=t.id
       LEFT JOIN (SELECT created_by,COUNT(*) cnt FROM news GROUP BY created_by) n ON n.created_by=t.id")->fetchAll(PDO::FETCH_ASSOC);

$all_students = $pdo->query("SELECT s.id, s.student_id, CONCAT(s.first_name,' ',COALESCE(s.middle_name,' '),' ',s.last_name) AS name,
       s.current_year, s.section, s.passport_photo,
       ROUND(COALESCE(p.cnt/NULLIF(t.cnt,0),0)*100,1) att_perc
       FROM students s
       LEFT JOIN (SELECT student_id,COUNT(*) cnt FROM attendance WHERE status='Present' GROUP BY student_id) p ON p.student_id=s.id
       LEFT JOIN (SELECT student_id,COUNT(*) cnt FROM attendance GROUP BY student_id) t ON t.student_id=s.id")->fetchAll(PDO::FETCH_ASSOC);

// === SELECTED TEACHER / STUDENT ===
$selected_teacher = $selected_student = null;
$current_tab = 'teachers';

if (isset($_GET['teacher_id'])) {
    $tid = (int)$_GET['teacher_id'];
    $current_tab = 'teachers';
    $selected_teacher = $pdo->query("SELECT t.*, CONCAT(t.first_name,' ',COALESCE(t.middle_name,' '),' ',t.last_name) AS full_name,
           COALESCE(a.cnt,0) attendance_marked, COALESCE(e.cnt,0) exams_created, COALESCE(ap.cnt,0) exams_approved,
           COALESCE(m.cnt,0) marks_entered, COALESCE(n.cnt,0) notices_sent
           FROM teachers t
           LEFT JOIN (SELECT marked_by,COUNT(*) cnt FROM attendance WHERE marked_by=$tid) a ON 1=1
           LEFT JOIN (SELECT created_by,COUNT(*) cnt FROM exams WHERE created_by=$tid) e ON 1=1
           LEFT JOIN (SELECT teacher_id,COUNT(*) cnt FROM exam_approvals WHERE teacher_id=$tid AND approved=1) ap ON 1=1
           LEFT JOIN (SELECT entered_by,COUNT(DISTINCT exam_id) cnt FROM student_marks WHERE entered_by=$tid) m ON 1=1
           LEFT JOIN (SELECT created_by,COUNT(*) cnt FROM news WHERE created_by=$tid) n ON 1=1
           WHERE t.id=$tid")->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['student_id'])) {
    $sid = (int)$_GET['student_id'];
    $current_tab = 'students';
    $selected_student = $pdo->query("SELECT s.*, CONCAT(s.first_name,' ',COALESCE(s.middle_name,' '),' ',s.last_name) AS full_name,
           COALESCE(p.cnt,0) days_present, COALESCE(t.cnt,0) total_days,
           ROUND(COALESCE(p.cnt/NULLIF(t.cnt,0),0)*100,1) attendance_percent,
           COALESCE(o.total_obtained,0) marks_obtained, COALESCE(pos.total_possible,0) possible_marks,
           ROUND(COALESCE(o.total_obtained/NULLIF(pos.total_possible,0),0)*100,1) overall_percent
           FROM students s
           LEFT JOIN (SELECT student_id,COUNT(*) cnt FROM attendance WHERE status='Present' AND student_id=$sid) p ON 1=1
           LEFT JOIN (SELECT student_id,COUNT(*) cnt FROM attendance WHERE student_id=$sid) t ON 1=1
           LEFT JOIN (SELECT student_id,SUM(marks_obtained) total_obtained FROM student_marks WHERE student_id=$sid) o ON 1=1
           LEFT JOIN (SELECT sm.student_id,SUM(e.total_marks) total_possible FROM student_marks sm JOIN exams e ON sm.exam_id=e.id WHERE sm.student_id=$sid) pos ON 1=1
           WHERE s.id=$sid")->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Reports • SMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { --deepblue: #4682A9; --lightblue: #91C8E4; --midblue: #749BC2; --cream: #FFFBDE; }
    body { background: var(--cream); }
    .glass { background: rgba(255,255,255,0.96); backdrop-filter: blur(12px); border: 1px solid #4682a930; }
    .card-hover:hover { transform: translateY(-12px); box-shadow: 0 25px 50px rgba(70,130,169,0.25); transition: all 0.4s; }
    .tab-active { background: var(--deepblue); color: white; }
    @media print { .no-print { display: none; } body { background: white; } }
  </style>
</head>
<body class="font-sans text-gray-800">

<!-- Header -->
<header class="bg-gradient-to-r from-[var(--deepblue)] to-[var(--midblue)] text-white shadow-2xl no-print">
  <div class="max-w-7xl mx-auto px-6 py-8 flex justify-between items-center">
    <div class="flex items-center gap-6">
     <div>
        <h1 class="text-5xl font-extrabold">Admin Reports Center</h1>
        <p class="text-xl">Complete Teacher & Student Analytics</p>
      </div>
    </div>
    <a href="../admin/dashboard.php" class="bg-white text-[var(--deepblue)] px-10 py-5 rounded-xl font-bold text-xl hover:bg-[var(--lightblue)] transition shadow-xl">
      Dashboard
    </a>
  </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-12">

  <!-- Bulk Actions -->
  <div class="text-center space-x-6 mb-12 no-print">
    <a href="?download_all_teachers=1" class="inline-block bg-gradient-to-r from-[var(--deepblue)] to-[var(--midblue)] hover:from-[var(--midblue)] hover:to-[var(--deepblue)] text-white text-2xl font-bold px-14 py-8 rounded-3xl shadow-2xl transform hover:scale-105 transition">
      All Teachers (CSV)
    </a>
    <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-800 text-white text-2xl font-bold px-14 py-8 rounded-3xl shadow-2xl">
      Print This Page
    </button>
    <a href="?download_all_students=1" class="inline-block bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-teal-700 hover:to-emerald-600 text-white text-2xl font-bold px-14 py-8 rounded-3xl shadow-2xl transform hover:scale-105 transition">
      All Students (CSV)
    </a>
  </div>

  <!-- Search & Filter -->
  <div class="glass rounded-3xl p-6 mb-10 shadow-xl no-print">
    <div class="flex flex-wrap gap-4 items-center">
      <input type="text" id="search" placeholder="Search by name or ID..." class="flex-1 px-6 py-4 rounded-xl border border-[var(--midblue)] focus:outline-none focus:border-[var(--deepblue)] text-lg">
      <select id="filter" class="px-6 py-4 rounded-xl border border-[var(--midblue)] focus:outline-none">
        <option value="">All</option>
        <option value="teachers">Teachers Only</option>
        <option value="students">Students Only</option>
      </select>
    </div>
  </div>

  <!-- Tabs -->
  <div class="flex gap-6 text-2xl font-bold mb-10 justify-center no-print">
    <button onclick="showTab('teachers')" id="tab-teachers" class="px-12 py-5 rounded-t-2xl <?= $current_tab==='teachers'?'tab-active':'bg-gray-200 hover:bg-[var(--lightblue)]' ?>">Teachers</button>
    <button onclick="showTab('students')" id="tab-students" class="px-12 py-5 rounded-t-2xl <?= $current_tab==='students'?'tab-active':'bg-gray-200 hover:bg-[var(--lightblue)]' ?>">Students</button>
  </div>

  <!-- TEACHERS SECTION -->
  <div id="teachers-section" class="<?= $current_tab==='students'?'hidden':'' ?>">
    <div class="glass rounded-3xl p-10 mb-12 shadow-2xl">
      <h2 class="text-4xl font-bold text-[var(--deepblue)] text-center mb-8">Teacher Performance Comparison</h2>
      <canvas id="teacherChart" height="100"></canvas>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10" id="teacher-grid">
      <?php foreach ($all_teachers as $t): ?>
        <div class="teacher-card glass rounded-3xl overflow-hidden shadow-xl card-hover border-t-8 border-[var(--deepblue)]" data-name="<?= strtolower($t['name']) ?>" data-id="<?= $t['teacher_id'] ?>">
          <div class="bg-gradient-to-br from-[var(--deepblue)] to-[var(--midblue)] p-8 text-white text-center">
            <?php if ($t['photo']): ?>
              <img src="data:image/jpeg;base64,<?= base64_encode($t['photo']) ?>" class="w-32 h-32 rounded-full mx-auto border-4 border-white">
            <?php else: ?>
              <div class="w-32 h-32 rounded-full mx-auto bg-white/30 flex items-center justify-center text-6xl font-bold"><?= strtoupper(substr($t['name'],0,2)) ?></div>
            <?php endif; ?>
            <h3 class="text-2xl font-bold mt-4"><?= htmlspecialchars($t['name']) ?></h3>
            <p class="opacity-90"><?= $t['teacher_id'] ?></p>
          </div>
          <div class="p-6 space-y-3 text-lg">
            <div class="flex justify-between"><span>Attendance:</span> <strong><?= $t['attendance'] ?></strong></div>
            <div class="flex justify-between"><span>Exams:</span> <strong><?= $t['exams'] ?></strong></div>
            <div class="flex justify-between"><span>Approved:</span> <strong><?= $t['approved'] ?></strong></div>
            <div class="flex justify-between"><span>Marks:</span> <strong><?= $t['marks'] ?></strong></div>
            <div class="flex justify-between"><span>Notices:</span> <strong><?= $t['notices'] ?></strong></div>
          </div>
          <div class="p-4 bg-[var(--lightblue)]/30 border-t">
            <a href="?teacher_id=<?= $t['id'] ?>" class="block text-center bg-[var(--deepblue)] text-white py-3 rounded-xl font-bold hover:bg-[var(--midblue)] transition">
              View Full Report
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($selected_teacher): ?>
      <div class="mt-20 glass rounded-3xl p-12 shadow-2xl border-t-12 border-[var(--deepblue)]">
        <div class="text-center mb-8 no-print">
          <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-800 text-white px-10 py-4 rounded-xl text-xl font-bold">
            Print Report
          </button>
        </div>
        <h2 class="text-5xl font-bold text-[var(--deepblue)] text-center mb-10">Teacher Report: <?= htmlspecialchars($selected_teacher['full_name']) ?></h2>
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 text-center">
          <div class="p-10 bg-[var(--lightblue)]/20 rounded-3xl"><h3 class="text-6xl font-bold text-[var(--deepblue)]"><?= $selected_teacher['attendance_marked'] ?></h3><p class="text-xl mt-2">Attendance Marked</p></div>
          <div class="p-10 bg-blue-50 rounded-3xl"><h3 class="text-6xl font-bold text-blue-700"><?= $selected_teacher['exams_created'] ?></h3><p class="text-xl mt-2">Exams Created</p></div>
          <div class="p-10 bg-green-50 rounded-3xl"><h3 class="text-6xl font-bold text-green-700"><?= $selected_teacher['exams_approved'] ?></h3><p class="text-xl mt-2">Exams Approved</p></div>
          <div class="p-10 bg-purple-50 rounded-3xl"><h3 class="text-6xl font-bold text-purple-700"><?= $selected_teacher['marks_entered'] ?></h3><p class="text-xl mt-2">Marks Entered</p></div>
          <div class="p-10 bg-pink-50 rounded-3xl"><h3 class="text-6xl font-bold text-pink-700"><?= $selected_teacher['notices_sent'] ?></h3><p class="text-xl mt-2">Notices Sent</p></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- STUDENTS SECTION -->
  <div id="students-section" class="<?= $current_tab==='teachers'?'hidden':'' ?>">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10" id="student-grid">
      <?php foreach ($all_students as $s): 
        $color = $s['att_perc'] >= 90 ? 'emerald' : ($s['att_perc'] >= 75 ? 'blue' : 'red');
      ?>
        <div class="student-card glass rounded-3xl overflow-hidden shadow-xl card-hover border-t-8 border-<?= $color ?>-600" data-name="<?= strtolower($s['name']) ?>" data-id="<?= $s['student_id'] ?>">
          <div class="bg-gradient-to-br from-<?= $color ?>-600 to-<?= $color ?>-800 p-8 text-white text-center">
            <?php if ($s['passport_photo']): ?>
              <img src="data:image/jpeg;base64,<?= base64_encode($s['passport_photo']) ?>" class="w-28 h-28 rounded-full mx-auto border-4 border-white">
            <?php else: ?>
              <div class="w-28 h-28 rounded-full mx-auto bg-white/30 flex items-center justify-center text-5xl font-bold"><?= strtoupper(substr($s['name'],0,2)) ?></div>
            <?php endif; ?>
            <h3 class="text-xl font-bold mt-4"><?= htmlspecialchars($s['name']) ?></h3>
            <p class="opacity-90"><?= $s['student_id'] ?> • <?= $s['current_year'] ?>-<?= $s['section'] ?></p>
          </div>
          <div class="p-6 text-center">
            <div class="text-5xl font-bold text-<?= $color ?>-600"><?= $s['att_perc'] ?>%</div>
            <p class="text-lg">Attendance</p>
            <a href="?student_id=<?= $s['id'] ?>" class="mt-4 inline-block bg-<?= $color ?>-600 text-white py-3 px-8 rounded-xl font-bold hover:bg-<?= $color ?>-700 transition">
              View Full Report
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($selected_student): ?>
      <div class="mt-20 glass rounded-3xl p-12 shadow-2xl border-t-12 border-emerald-600">
        <div class="text-center mb-8 no-print">
          <button onclick="window.print()" class="bg-emerald-700 hover:bg-emerald-800 text-white px-10 py-4 rounded-xl text-xl font-bold">
            Print Student Report
          </button>
        </div>
        <h2 class="text-5xl font-bold text-emerald-700 text-center mb-10">Student Report: <?= htmlspecialchars($selected_student['full_name']) ?></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div class="p-10 bg-emerald-50 rounded-3xl"><h3 class="text-6xl font-bold text-emerald-700"><?= $selected_student['attendance_percent'] ?>%</h3><p>Attendance</p></div>
          <div class="p-10 bg-blue-50 rounded-3xl"><h3 class="text-6xl font-bold text-blue-700"><?= $selected_student['overall_percent'] ?>%</h3><p>Academic</p></div>
          <div class="p-10 bg-purple-50 rounded-3xl"><h3 class="text-6xl font-bold text-purple-700"><?= $selected_student['days_present'] ?></h3><p>Days Present</p></div>
          <div class="p-10 bg-orange-50 rounded-3xl"><h3 class="text-6xl font-bold text-orange-700"><?= $selected_student['total_days'] ?></h3><p>Total Days</p></div>
        </div>
      </div>
    <?php endif; ?>
  </div>

</main>

<script>
function showTab(tab) {
  const teachers = document.getElementById('teachers-section');
  const students = document.getElementById('students-section');
  const tabTeachers = document.getElementById('tab-teachers');
  const tabStudents = document.getElementById('tab-students');
  
  if (tab === 'teachers') {
    teachers.classList.remove('hidden');
    students.classList.add('hidden');
    tabTeachers.classList.add('tab-active'); tabTeachers.classList.remove('bg-gray-200');
    tabStudents.classList.remove('tab-active'); tabStudents.classList.add('bg-gray-200');
  } else {
    teachers.classList.add('hidden');
    students.classList.remove('hidden');
    tabTeachers.classList.remove('tab-active'); tabTeachers.classList.add('bg-gray-200');
    tabStudents.classList.add('tab-active'); tabStudents.classList.remove('bg-gray-200');
  }
}

// Search & Filter
document.getElementById('search').addEventListener('input', filterCards);
document.getElementById('filter').addEventListener('change', filterCards);

function filterCards() {
  const query = document.getElementById('search').value.toLowerCase();
  const filter = document.getElementById('filter').value;
  
  document.querySelectorAll('.teacher-card, .student-card').forEach(card => {
    const name = card.dataset.name;
    const id = card.dataset.id;
    const isTeacher = card.classList.contains('teacher-card');
    const isStudent = card.classList.contains('student-card');
    
    const matchesSearch = name.includes(query) || id.includes(query);
    const matchesFilter = filter === '' || (filter === 'teachers' && isTeacher) || (filter === 'students' && isStudent);
    
    card.style.display = matchesSearch && matchesFilter ? 'block' : 'none';
  });
}

// Teacher Chart with Notices
new Chart(document.getElementById('teacherChart'), {
  type: 'bar',
  data: {
    labels: [<?php foreach($all_teachers as $t) echo "'".addslashes($t['name'])."',"; ?>],
    datasets: [
      { label: 'Attendance', data: [<?php foreach($all_teachers as $t) echo $t['attendance'].','; ?>], backgroundColor: '#4682A9' },
      { label: 'Exams Created', data: [<?php foreach($all_teachers as $t) echo $t['exams'].','; ?>], backgroundColor: '#749BC2' },
      { label: 'Exams Approved', data: [<?php foreach($all_teachers as $t) echo $t['approved'].','; ?>], backgroundColor: '#10b981' },
      { label: 'Marks Entered', data: [<?php foreach($all_teachers as $t) echo $t['marks'].','; ?>], backgroundColor: '#8b5cf6' },
      { label: 'Notices Sent', data: [<?php foreach($all_teachers as $t) echo $t['notices'].','; ?>], backgroundColor: '#ec4899' }
    ]
  },
  options: { responsive: true, plugins: { legend: { position: 'top' } } }
});
</script>
</body>
</html>