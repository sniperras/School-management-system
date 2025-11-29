<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

// Fixed: Properly load teacher name
$teacher = $_SESSION['teacher'];
$teacher_id = $teacher['id'];
$teacher_name = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['middle_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));

// === FETCH ALL STATS (same accurate queries) ===
$stats = [];

// 1. Attendance Marked
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE marked_by = ?");
$stmt->execute([$teacher_id]);
$stats['attendance_marked'] = (int)$stmt->fetchColumn();

// 2. Exams Created
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE created_by = ?");
$stmt->execute([$teacher_id]);
$stats['exams_created'] = (int)$stmt->fetchColumn();

// 3. Exams Approved (from exam_approvals)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_approvals WHERE teacher_id = ? AND approved = 1");
$stmt->execute([$teacher_id]);
$stats['exams_approved'] = (int)$stmt->fetchColumn();

// 4. Marks Entered (from student_marks)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT exam_id) FROM student_marks WHERE entered_by = ?");
$stmt->execute([$teacher_id]);
$stats['marks_entered_exams'] = (int)$stmt->fetchColumn();

// 5. Notices Sent (from news)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE created_by = ?");
$stmt->execute([$teacher_id]);
$stats['notices_sent'] = (int)$stmt->fetchColumn();

// 6. Recent Activity
$activities = $pdo->prepare("
    SELECT 'Attendance' as type, a.marked_at as date, 'Marked student attendance' as description
    FROM attendance a WHERE a.marked_by = ?
    UNION ALL
    SELECT 'Exam Created', e.created_at, CONCAT('Created: ', e.exam_name)
    FROM exams e WHERE e.created_by = ?
    UNION ALL
    SELECT 'Exam Approved', ea.approved_at, CONCAT('Approved: ', e.exam_name)
    FROM exam_approvals ea JOIN exams e ON ea.exam_id = e.id WHERE ea.teacher_id = ? AND ea.approved = 1
    UNION ALL
    SELECT 'Marks Entered', sm.entered_at, CONCAT('Entered marks for: ', e.exam_name)
    FROM student_marks sm JOIN exams e ON sm.exam_id = e.id WHERE sm.entered_by = ?
    UNION ALL
    SELECT 'Notice Sent', n.created_at, n.title
    FROM news n WHERE n.created_by = ?
    ORDER BY date DESC LIMIT 12
");
$activities->execute([$teacher_id, $teacher_id, $teacher_id, $teacher_id, $teacher_id]);
$recent_activities = $activities->fetchAll(PDO::FETCH_ASSOC);

// === CSV DOWNLOAD - NOW WITH CORRECT TEACHER NAME ===
if (isset($_GET['download_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="teacher_report_' . $teacher_id . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['TEACHER PERFORMANCE REPORT']);
    fputcsv($output, ['Teacher Name', $teacher_name]);  // FIXED: Now shows real name!
    fputcsv($output, ['Teacher ID', $teacher['teacher_id'] ?? 'N/A']);
    fputcsv($output, ['Generated On', date('d F Y, h:i A')]);
    fputcsv($output, []);
    fputcsv($output, ['Activity', 'Total Count']);
    fputcsv($output, ['Student Attendance Marked', $stats['attendance_marked']]);
    fputcsv($output, ['Exams Created', $stats['exams_created']]);
    fputcsv($output, ['Exams Approved (Others Included)', $stats['exams_approved']]);
    fputcsv($output, ['Exams with Marks Entered', $stats['marks_entered_exams']]);
    fputcsv($output, ['Announcements/Notices Sent', $stats['notices_sent']]);
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Performance Report • <?= htmlspecialchars($teacher_name) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    :root {
      --deepblue: #4682A9;
      --lightblue: #91C8E4;
      --midblue: #749BC2;
      --cream: #FFFBDE;
    }
    body { background: var(--cream); }
    .glass { background: rgba(255,255,255,0.95); backdrop-filter: blur(12px); border: 1px solid rgba(70,130,169,0.2); }
    .hover-lift:hover { transform: translateY(-10px) scale(1.02); transition: all 0.4s; }
    .chart-container { height: 340px; }
    .btn-primary { background: var(--deepblue); }
    .btn-primary:hover { background: #3a6d8c; }
    .text-deepblue { color: var(--deepblue); }
    .bg-deepblue { background: var(--deepblue); }
    .border-deepblue { border-color: var(--deepblue); }
  </style>
</head>
<body class="font-sans text-gray-800 min-h-screen">

  <!-- Header -->
  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-7 flex justify-between items-center">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-5xl font-extrabold">Teacher Performance Report</h1>
          <p class="text-xl opacity-90"><?= htmlspecialchars($teacher_name) ?> • <?= date('F Y') ?></p>
        </div>
      </div>
      <div class="flex gap-6">
        <a href="/../sms/teacher/teacher_dashboard.php" class="bg-white text-deepblue px-8 py-4 rounded-xl font-bold hover:bg-lightblue transition shadow-lg">
          Dashboard
        </a>
        <a href="?download_csv=1" class="bg-midblue hover:bg-deepblue text-white px-10 py-4 rounded-xl font-bold flex items-center gap-4 shadow-2xl hover-lift transition">
          <i class="fas fa-file-csv text-2xl"></i> Download CSV Report
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-12">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
      <div class="glass rounded-3xl p-8 text-center hover-lift shadow-xl border-t-8 border-deepblue">
        <i class="fas fa-clipboard-check text-6xl text-deepblue mb-4"></i>
        <h3 class="text-5xl font-bold text-deepblue"><?= number_format($stats['attendance_marked']) ?></h3>
        <p class="text-lg mt-2">Attendance Marked</p>
      </div>
      <div class="glass rounded-3xl p-8 text-center hover-lift shadow-xl border-t-8 border-midblue">
        <i class="fas fa-file-alt text-6xl text-midblue mb-4"></i>
        <h3 class="text-5xl font-bold text-midblue"><?= $stats['exams_created'] ?></h3>
        <p class="text-lg mt-2">Exams Created</p>
      </div>
      <div class="glass rounded-3xl p-8 text-center hover-lift shadow-xl border-t-8 border-green-600">
        <i class="fas fa-check-double text-6xl text-green-600 mb-4"></i>
        <h3 class="text-5xl font-bold text-green-600"><?= $stats['exams_approved'] ?></h3>
        <p class="text-lg mt-2">Exams Approved</p>
      </div>
      <div class="glass rounded-3xl p-8 text-center hover-lift shadow-xl border-t-8 border-purple-600">
        <i class="fas fa-edit text-6xl text-purple-600 mb-4"></i>
        <h3 class="text-5xl font-bold text-purple-600"><?= $stats['marks_entered_exams'] ?></h3>
        <p class="text-lg mt-2">Marks Entered</p>
      </div>
      <div class="glass rounded-3xl p-8 text-center hover-lift shadow-xl border-t-8 border-pink-600">
        <i class="fas fa-bullhorn text-6xl text-pink-600 mb-4"></i>
        <h3 class="text-5xl font-bold text-pink-600"><?= $stats['notices_sent'] ?></h3>
        <p class="text-lg mt-2">Notices Sent</p>
      </div>
    </div>

    <!-- Charts + Activity -->
    <div class="grid lg:grid-cols-2 gap-10 mb-12">
      <div class="glass rounded-3xl p-8 shadow-2xl">
        <h2 class="text-3xl font-bold text-deepblue mb-6 text-center">Activity Distribution</h2>
        <div class="chart-container">
          <canvas id="pieChart"></canvas>
        </div>
      </div>

      <div class="glass rounded-3xl p-8 shadow-2xl">
        <h2 class="text-3xl font-bold text-deepblue mb-6">Recent Activity</h2>
        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
          <?php foreach ($recent_activities as $act): ?>
            <div class="flex items-center gap-5 p-5 bg-lightblue/20 rounded-2xl hover:bg-lightblue/30 transition">
              <div class="w-3 h-3 rounded-full bg-midblue"></div>
              <div class="flex-1">
                <p class="font-semibold text-lg text-deepblue"><?= htmlspecialchars($act['description']) ?></p>
                <p class="text-sm opacity-75"><?= date('d M Y • h:i A', strtotime($act['date'])) ?></p>
              </div>
              <span class="px-4 py-2 bg-deepblue text-white rounded-full text-xs font-bold"><?= $act['type'] ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (empty($recent_activities)): ?>
            <p class="text-center text-gray-600 py-12 text-xl">No activity recorded yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

   <!-- Final Download Button – FIXED & BEAUTIFUL -->
<div class="text-center my-16">
  <a href="?download_csv=1" 
     class="inline-block bg-gradient-to-r from-[var(--deepblue)] to-[var(--midblue)] 
            hover:from-[var(--midblue)] hover:to-[var(--deepblue)] 
            text-white text-4xl font-extrabold 
            px-28 py-12 rounded-3xl shadow-2xl 
            transform hover:scale-110 transition duration-500 
            border-4 border-white/30">
    <i class="fas fa-download mr-6"></i>
    DOWNLOAD FULL REPORT (CSV)
  </a>
</div>

  </main>

  <script>
    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
      type: 'doughnut',
      data: {
        labels: ['Attendance', 'Exams Created', 'Exams Approved', 'Marks Entered', 'Notices'],
        datasets: [{
          data: [<?= $stats['attendance_marked'] ?>, <?= $stats['exams_created'] ?>, <?= $stats['exams_approved'] ?>, <?= $stats['marks_entered_exams'] ?>, <?= $stats['notices_sent'] ?>],
          backgroundColor: ['#4682A9', '#749BC2', '#10b981', '#8b5cf6', '#ec4899'],
          borderColor: '#fff',
          borderWidth: 4,
          hoverOffset: 20
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#333', font: { size: 16 }, padding: 20 } }
        }
      }
    });
  </script>
</body>
</html>