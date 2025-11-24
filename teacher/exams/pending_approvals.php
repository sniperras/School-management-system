<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacher_id = $_SESSION['teacher']['id'];
$department = $_SESSION['teacher']['department'];
$teacher_name = trim($_SESSION['teacher']['first_name'] . ' ' . ($_SESSION['teacher']['middle_name'] ?? '') . ' ' . $_SESSION['teacher']['last_name']);
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Pending Approvals | Teacher</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
  </script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <div class="flex items-center gap-5">
        <img src="../../img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white shadow-lg">
        <div>
          <h1 class="text-2xl font-extrabold">Exam Approval Center</h1>
        </div>
      </div>
      <div class="flex items-center gap-6">
        <span class="hidden md:block text-lg"><strong><?= htmlspecialchars($teacher_name) ?></strong></span>
        <a href="../../logout.php" class="bg-lightblue text-deepblue px-6 py-3 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-lg">
          Logout
        </a>
      </div>
    </div>
  </header>

  <section class="bg-gradient-to-r from-deepblue via-midblue to-indigo-700 text-white py-12" data-aos="fade-down">
    <div class="max-w-7xl mx-auto px-6 text-center">
      <a href="../teacher_dashboard.php" class="bg-white text-deepblue px-8 py-4 rounded-xl font-bold text-xl hover:bg-lightblue transition shadow-lg inline-flex items-center gap-3">
        Back to Dashboard
      </a>
    </div>
  </section>

  <main class="max-w-7xl mx-auto px-6 py-12">
    <?php
    $stmt = $pdo->prepare("
        SELECT e.*, CONCAT(c.class_name, ' - ', c.section) AS full_class,
               CONCAT(t.first_name, ' ', COALESCE(t.middle_name,''), ' ', t.last_name) AS creator_name
        FROM exams e
        JOIN classes c ON e.class_name = c.class_name
        JOIN teachers t ON e.created_by = t.id
        WHERE t.department = ? AND e.created_by != ? AND e.status IN ('pending', 'dept_approved')
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$department, $teacher_id]);
    $pending = $stmt->fetchAll();
    ?>

    <?php if (empty($pending)): ?>
      <div class="text-center py-20" data-aos="fade-up">
        <i class="fas fa-check-double text-9xl text-green-400 mb-8"></i>
        <h3 class="text-4xl font-bold text-gray-700">No pending approvals</h3>
        <p class="text-xl text-gray-600 mt-4">You're all caught up!</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($pending as $i => $exam): ?>
          <div data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-deepblue overflow-hidden">
            <div class="bg-gradient-to-br from-yellow-500 to-orange-600 p-8 text-white">
              <h3 class="text-2xl font-bold"><?= htmlspecialchars($exam['exam_name']) ?></h3>
              <p class="mt-2 text-lg opacity-95">by <?= htmlspecialchars($exam['creator_name']) ?></p>
            </div>
            <div class="p-8 space-y-6">
              <div class="space-y-3 text-sm">
                <p><strong>Class:</strong> <?= htmlspecialchars($exam['full_class']) ?></p>
                <p><strong>Type:</strong> <?= ucwords($exam['exam_type'] ?? 'N/A') ?></p>
                <p><strong>Status:</strong> <span class="font-bold text-orange-600"><?= ucwords(str_replace('_', ' ', $exam['status'])) ?></span></p>
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
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script>
    AOS.init({ once: true, duration: 1000 });
  </script>
</body>
</html>