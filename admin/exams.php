<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php"); exit;
}

$message = '';

// ============== ADMIN FINAL APPROVAL / REJECTION ==============
if ($_POST['admin_action'] ?? false) {
    $exam_id = (int)$_POST['exam_id'];
    $action = $_POST['admin_action'];
    $room = trim($_POST['room'] ?? 'TBD');
    $duration = trim($_POST['duration'] ?? '2 hours');
    $percentage = (int)($_POST['percentage'] ?? 100);
    $instructions = trim($_POST['instructions'] ?? 'Bring pen and ID card. No phones allowed.');
    $comments = trim($_POST['comments'] ?? '');

    $stmt = $pdo->prepare("SELECT e.*, c.class_name, c.section 
                           FROM exams e 
                           JOIN classes c ON e.class_name = c.class_name 
                           WHERE e.id = ? AND e.status = 'dept_approved'");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-800 p-5 rounded-xl text-center font-bold'>Invalid or already processed exam!</div>";
    } else {
        if ($action === 'publish') {
            $pdo->prepare("UPDATE exams SET 
                status = 'published',
                room = ?, duration = ?, percentage_weight = ?, instructions = ?
                WHERE id = ?")
                ->execute([$room, $duration, $percentage, $instructions, $exam_id]);

            $full_class = htmlspecialchars($exam['class_name'] . ' - ' . $exam['section']);
            $announce_msg = "<div class='bg-green-50 border-l-4 border-green-500 p-6 rounded-r-xl'>
                <h3 class='text-2xl font-bold text-green-800'>Exam Published!</h3>
                <ul class='mt-4 space-y-2 text-gray-700'>
                    <li><strong>Title:</strong> " . htmlspecialchars($exam['exam_name']) . "</li>
                    <li><strong>Class:</strong> $full_class</li>
                    <li><strong>Date:</strong> " . date('d F Y', strtotime($exam['exam_date'])) . "</li>
                    <li><strong>Room:</strong> <span class='font-bold text-green-600'>$room</span></li>
                    <li><strong>Duration:</strong> $duration</li>
                    <li><strong>Weight:</strong> $percentage% of final grade</li>
                    <li><strong>Instructions:</strong> $instructions</li>
                </ul>
                <p class='mt-4 font-medium text-sm text-blue-700'>Download from Student Portal → Exams</p>
            </div>";

            $pdo->prepare("INSERT INTO announcements (title, message, type, created_at) VALUES (?, ?, 'exam', NOW())")
                ->execute(["Published: " . $exam['exam_name'], $announce_msg]);

            $message = "<div class='bg-green-100 border border-green-400 text-green-800 p-6 rounded-xl text-center shadow-lg'>
                <i class='fas fa-check-circle text-7xl mb-4'></i><br>
                Exam Published Successfully!<br><span class='text-sm'>All students have been notified.</span>
            </div>";
            log_action($pdo, $_SESSION['user_id'] ?? null, "Exam Published Successfully ID {$exam_id}");
        } else {
            $pdo->prepare("UPDATE exams SET status = 'rejected', admin_comment = ? WHERE id = ?")
                ->execute([$comments, $exam_id]);

            $message = "<div class='bg-red-100 border border-red-400 text-red-800 p-6 rounded-xl text-center shadow-lg'>
                <i class='fas fa-times-circle text-7xl mb-4'></i><br>
                Exam Rejected<br><span class='text-sm'>Teacher notified with your feedback.</span>
            </div>";
            log_action($pdo, $_SESSION['user_id'] ?? null, "Exam Rejected {$exam_id}");
        }
    }
}

// ============== STATISTICS ==============
$total_exams = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();

$stats = [
    'pending'   => $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'dept_approved'")->fetchColumn(),
    'published' => $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'published'")->fetchColumn(),
    'rejected'  => $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'rejected'")->fetchColumn(),
    'total'     => $total_exams
];

// ============== FETCH PENDING EXAMS ==============
$stmt = $pdo->prepare("
    SELECT e.*, 
           CONCAT(c.class_name, ' - ', c.section) AS full_class,
           CONCAT(t.first_name, ' ', COALESCE(t.middle_name,''), ' ', t.last_name) AS creator_name
    FROM exams e
    JOIN classes c ON e.class_name = c.class_name
    JOIN teachers t ON e.created_by = t.id
    WHERE e.status = 'dept_approved'
    ORDER BY e.created_at DESC
");
$stmt->execute();
$awaiting = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Final Exam Approval</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
  </script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <!-- FIXED HEADER — NO BROKEN TEXT -->
  <header class="bg-deepblue text-white shadow-2xl sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <div class="flex items-center gap-5">
        <img src="../img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white shadow-lg">
        <h1 class="text-2xl font-extrabold">Final Exam Approval</h1>
      </div>
      <a href="dashboard.php" class="bg-lightblue text-deepblue px-8 py-3 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-lg">
        Back to Dashboard
      </a>
    </div>
  </header>

  <!-- Dashboard Stats + Total Exams + Pie Chart -->
  <section class="bg-white shadow-xl py-10 my-8 mx-4 rounded-3xl">
    <div class="max-w-7xl mx-auto px-6">
      <h2 class="text-3xl font-bold text-deepblue text-center mb-10">Exam Approval Dashboard</h2>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
        <div class="bg-yellow-100 p-6 rounded-2xl text-center shadow-lg border-t-4 border-yellow-500">
          <i class="fas fa-clock text-5xl text-yellow-600 mb-3"></i>
          <h3 class="text-4xl font-bold"><?= $stats['pending'] ?></h3>
          <p class="text-lg font-semibold">Pending Final</p>
        </div>
        <div class="bg-green-100 p-6 rounded-2xl text-center shadow-lg border-t-4 border-green-500">
          <i class="fas fa-check-circle text-5xl text-green-600 mb-3"></i>
          <h3 class="text-4xl font-bold"><?= $stats['published'] ?></h3>
          <p class="text-lg font-semibold">Published</p>
        </div>
        <div class="bg-red-100 p-6 rounded-2xl text-center shadow-lg border-t-4 border-red-500">
          <i class="fas fa-times-circle text-5xl text-red-600 mb-3"></i>
          <h3 class="text-4xl font-bold"><?= $stats['rejected'] ?></h3>
          <p class="text-lg font-semibold">Rejected</p>
        </div>
        <div class="bg-deepblue text-white p-6 rounded-2xl text-center shadow-lg border-t-4 border-indigo-700">
          <i class="fas fa-file-alt text-5xl mb-3"></i>
          <h3 class="text-4xl font-bold"><?= $stats['total'] ?></h3>
          <p class="text-lg font-semibold">Total Created</p>
        </div>
      </div>

      <div class="max-w-sm mx-auto bg-gray-50 p-8 rounded-2xl shadow-inner border">
        <canvas id="examChart"></canvas>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-6 py-10">
    <?= $message ?>

    <?php if (empty($awaiting)): ?>
      <div class="text-center py-24">
        <i class="fas fa-thumbs-up text-9xl text-green-500 mb-8"></i>
        <h3 class="text-5xl font-bold text-gray-700">All exams are processed!</h3>
        <p class="text-xl text-gray-600 mt-4">Great job, Admin!</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($awaiting as $i => $exam): ?>
          <div data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>" class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 border-t-8 border-purple-600 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-700 p-8 text-white">
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($exam['exam_name']) ?></h3>
              <p class="mt-2 opacity-90 text-lg">by <?= htmlspecialchars($exam['creator_name']) ?></p>
            </div>
            <div class="p-8 space-y-4">
              <p><strong>Class:</strong> <?= htmlspecialchars($exam['full_class']) ?></p>
              <p><strong>Type:</strong> <?= ucwords($exam['exam_type'] ?? 'General') ?></p>
              <p><strong>Date:</strong> <?= date('d M Y', strtotime($exam['exam_date'])) ?></p>
              <p><strong>Marks:</strong> <?= $exam['total_marks'] ?></p>

              <div class="flex flex-wrap gap-3 mt-6">
                <a href="../teacher/exams/view_exam.php?id=<?= $exam['id'] ?>" target="_blank" 
                   class="bg-deepblue text-white px-6 py-3 rounded-xl font-bold hover:bg-midblue transition shadow">View PDF</a>
                <button onclick="document.getElementById('publish<?= $exam['id'] ?>').showModal()" 
                        class="bg-green-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition shadow">Publish</button>
                <button onclick="document.getElementById('reject<?= $exam['id'] ?>').showModal()" 
                        class="bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition shadow">Reject</button>
              </div>
            </div>

            <!-- Publish Modal -->
            <dialog id="publish<?= $exam['id'] ?>" class="p-10 rounded-3xl shadow-2xl max-w-2xl w-full bg-white">
              <form method="POST">
                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                <input type="hidden" name="admin_action" value="publish">
                <h3 class="text-3xl font-bold text-center text-deepblue mb-8">Publish Exam</h3>
                <div class="grid grid-cols-2 gap-6">
                  <div><label class="block font-bold mb-2">Room</label><input type="text" name="room" value="Room 301" class="w-full p-4 border-2 border-midblue rounded-xl" required></div>
                  <div><label class="block font-bold mb-2">Duration</label><input type="text" name="duration" value="2 hours 30 mins" class="w-full p-4 border-2 border-midblue rounded-xl" required></div>
                  <div><label class="block font-bold mb-2">Weight (%)</label><input type="number" name="percentage" value="100" min="1" max="100" class="w-full p-4 border-2 border-midblue rounded-xl" required></div>
                  <div class="col-span-2"><label class="block font-bold mb-2">Instructions</label><textarea name="instructions" rows="3" class="w-full p-4 border-2 border-midblue rounded-xl">Bring ID card and pen. No phones allowed.</textarea></div>
                </div>
                <div class="text-center mt-10">
                  <button type="submit" class="bg-green-600 text-white px-12 py-5 rounded-xl text-xl font-bold hover:bg-green-700 shadow-xl">Publish & Notify</button>
                  <button type="button" onclick="this.closest('dialog').close()" class="ml-4 bg-gray-500 text-white px-8 py-5 rounded-xl font-bold">Cancel</button>
                </div>
              </form>
            </dialog>

            <!-- Reject Modal -->
            <dialog id="reject<?= $exam['id'] ?>" class="p-10 rounded-3xl shadow-2xl max-w-lg w-full bg-white">
              <form method="POST">
                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                <input type="hidden" name="admin_action" value="reject">
                <h3 class="text-3xl font-bold text-red-600 text-center mb-6">Reject Exam</h3>
                <textarea name="comments" rows="6" class="w-full p-4 border-2 border-red-300 rounded-xl" placeholder="Reason for rejection..." required></textarea>
                <div class="text-center mt-8">
                  <button type="submit" class="bg-red-600 text-white px-12 py-5 rounded-xl text-xl font-bold hover:bg-red-700 shadow-xl">Reject Exam</button>
                  <button type="button" onclick="this.closest('dialog').close()" class="ml-4 bg-gray-500 text-white px-8 py-5 rounded-xl font-bold">Cancel</button>
                </div>
              </form>
            </dialog>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script>
    AOS.init({ duration: 1000 });
    new Chart(document.getElementById('examChart'), {
      type: 'doughnut',
      data: {
        labels: ['Pending Final', 'Published', 'Rejected', 'Total Created'],
        datasets: [{
          data: [<?= $stats['pending'] ?>, <?= $stats['published'] ?>, <?= $stats['rejected'] ?>, <?= $stats['total'] ?>],
          backgroundColor: ['#FBBF24', '#10B981', '#EF4444', '#4682A9'],
          borderWidth: 4,
          borderColor: '#fff',
          hoverOffset: 12
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 20, font: { size: 13 } } }
        }
      }
    });
  </script>
</body>
</html>