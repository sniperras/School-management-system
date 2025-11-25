<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacher_id = $_SESSION['teacher']['id'];
$department = $_SESSION['teacher']['department'];
$teacher_name = trim($_SESSION['teacher']['first_name'] . ' ' . ($_SESSION['teacher']['middle_name'] ?? '') . ' ' . $_SESSION['teacher']['last_name']);
$message = '';

// ============== HANDLE APPROVAL / REJECTION ==============
if ($_POST['action'] ?? false) {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $action = $_POST['action']; // 'approve' or 'reject'
    $comments = trim($_POST['comments'] ?? '');

    if (!$exam_id || !in_array($action, ['approve', 'reject'])) {
        $message = "<div class='bg-red-100 text-red-800 p-4 rounded-xl'>Invalid request!</div>";
    } else {
        // Verify teacher belongs to same department
        $stmt = $pdo->prepare("SELECT e.id FROM exams e JOIN teachers t ON e.created_by = t.id WHERE e.id = ? AND t.department = ? AND e.created_by != ?");
        $stmt->execute([$exam_id, $department, $teacher_id]);
        if (!$stmt->fetch()) {
            $message = "<div class='bg-red-100 text-red-800 p-4 rounded-xl'>Unauthorized action!</div>";
        } else {
            $approved = ($action === 'approve') ? 1 : 0;

            // Record approval
            $stmt = $pdo->prepare("INSERT INTO exam_approvals (exam_id, teacher_id, approved, comments, approved_at) 
                                   VALUES (?, ?, ?, ?, NOW()) 
                                   ON DUPLICATE KEY UPDATE approved = ?, comments = ?, approved_at = NOW()");
            $stmt->execute([$exam_id, $teacher_id, $approved, $comments, $approved, $comments]);

            // NEW LOGIC: Only 1 approval needed from department
            if ($action === 'approve') {
                $pdo->prepare("UPDATE exams SET status = 'dept_approved' WHERE id = ?")->execute([$exam_id]);
                $message = "<div class='bg-green-100 text-green-800 p-6 rounded-xl text-center shadow-lg'>
                    <i class='fas fa-check-circle text-5xl'></i><br>
                    Exam Approved! Now waiting for <strong>Admin Final Approval</strong>
                </div>";
            } else {
                $pdo->prepare("UPDATE exams SET status = 'rejected' WHERE id = ?")->execute([$exam_id]);
                $message = "<div class='bg-red-100 text-red-800 p-6 rounded-xl text-center shadow-lg'>
                    <i class='fas fa-times-circle text-5xl'></i><br>
                    Exam Rejected. Creator has been notified.
                </div>";
            }
        }
    }
}

// Fetch exams needing department approval
$stmt = $pdo->prepare("
    SELECT e.*, CONCAT(c.class_name, ' - ', c.section) AS full_class,
           CONCAT(t.first_name, ' ', COALESCE(t.middle_name,''), ' ', t.last_name) AS creator_name
    FROM exams e
    JOIN classes c ON e.class_name = c.class_name
    JOIN teachers t ON e.created_by = t.id
    WHERE t.department = ? AND e.created_by != ? AND e.status = 'pending'
    ORDER BY e.created_at DESC
");
$stmt->execute([$department, $teacher_id]);
$pending = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Department Exam Approval</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
  </script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <!-- Header & Back Button same as before -->
  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <div class="flex items-center gap-5">
        <img src="../../img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white shadow-lg">
        <h1 class="text-2xl font-extrabold">Department Exam Approval</h1>
      </div>
      <div class="flex items-center gap-6">
        <span class="hidden md:block text-lg"><strong><?= htmlspecialchars($teacher_name) ?></strong></span>
        <a href="../../logout.php" class="bg-lightblue text-deepblue px-6 py-3 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-lg">Logout</a>
      </div>
    </div>
  </header>

  <section class="bg-gradient-to-r from-deepblue via-midblue to-indigo-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-6 text-center">
      <a href="../teacher_dashboard.php" class="bg-white text-deepblue px-8 py-4 rounded-xl font-bold text-xl hover:bg-lightblue transition shadow-lg inline-flex items-center gap-3">
        Back to Dashboard
      </a>
    </div>
  </section>

  <main class="max-w-7xl mx-auto px-6 py-12">
    <?= $message ?>

    <?php if (empty($pending)): ?>
      <div class="text-center py-20">
        <i class="fas fa-check-double text-9xl text-green-400 mb-8"></i>
        <h3 class="text-4xl font-bold text-gray-700">No exams need your approval</h3>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($pending as $i => $exam): ?>
          <div data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>" class="bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-orange-500 overflow-hidden">
            <div class="bg-gradient-to-br from-yellow-500 to-orange-600 p-8 text-white">
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($exam['exam_name']) ?></h3>
              <p class="mt-2 text-lg opacity-95">by <?= htmlspecialchars($exam['creator_name']) ?></p>
            </div>
            <div class="p-8 space-y-6">
              <div class="space-y-3 text-sm">
                <p><strong>Class:</strong> <?= htmlspecialchars($exam['full_class']) ?></p>
                <p><strong>Type:</strong> <?= ucwords($exam['exam_type'] ?? 'N/A') ?></p>
                <p><strong>Date:</strong> <?= date('d M Y', strtotime($exam['exam_date'])) ?></p>
              </div>
              <div class="flex flex-wrap gap-3">
                <a href="view_exam.php?id=<?= $exam['id'] ?>" target="_blank" class="bg-deepblue text-white px-6 py-3 rounded-xl font-bold hover:bg-midblue transition">
                  View PDF
                </a>
                <button onclick="document.getElementById('approve<?= $exam['id'] ?>').showModal()" class="bg-green-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition">
                  Approve
                </button>
                <button onclick="document.getElementById('reject<?= $exam['id'] ?>').showModal()" class="bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition">
                  Reject
                </button>
              </div>
            </div>
          </div>

          <!-- Modals (same as before) -->
          <dialog id="approve<?= $exam['id'] ?>" class="p-8 rounded-2xl shadow-2xl max-w-lg w-full bg-white">
            <form method="POST">
              <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <div class="text-center">
                <i class="fas fa-thumbs-up text-6xl text-green-600 mb-4"></i>
                <h3 class="text-2xl font-bold text-deepblue">Approve This Exam?</h3>
                <p class="mt-4 text-lg"><strong><?= htmlspecialchars($exam['exam_name']) ?></strong></p>
              </div>
              <div class="flex gap-4 mt-8 justify-center">
                <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-xl font-bold text-lg">Yes, Approve</button>
                <button type="button" onclick="this.closest('dialog').close()" class="bg-gray-500 text-white px-8 py-4 rounded-xl font-bold text-lg">Cancel</button>
              </div>
            </form>
          </dialog>

          <dialog id="reject<?= $exam['id'] ?>" class="p-8 rounded-2xl shadow-2xl max-w-lg w-full bg-white">
            <form method="POST">
              <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <div class="text-center">
                <i class="fas fa-thumbs-down text-6xl text-red-600 mb-4"></i>
                <h3 class="text-2xl font-bold text-deepblue">Reject Exam?</h3>
                <p class="text-gray-600 mt-3">Provide reason:</p>
              </div>
              <textarea name="comments" rows="4" class="w-full mt-4 p-4 border-2 border-gray-300 rounded-xl" placeholder="e.g., Questions not clear, missing instructions..." required></textarea>
              <div class="flex gap-4 mt-6 justify-center">
                <button type="submit" class="bg-red-600 text-white px-8 py-4 rounded-xl font-bold text-lg">Reject</button>
                <button type="button" onclick="this.closest('dialog').close()" class="bg-gray-500 text-white px-8 py-4 rounded-xl font-bold text-lg">Cancel</button>
              </div>
            </form>
          </dialog>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script> AOS.init({ once: true, duration: 1000 }); </script>
</body>
</html>