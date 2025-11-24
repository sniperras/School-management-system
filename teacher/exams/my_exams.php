<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacher_id = $_SESSION['teacher']['id'];
$teacher_name = trim($_SESSION['teacher']['first_name'] . ' ' . ($_SESSION['teacher']['middle_name'] ?? '') . ' ' . $_SESSION['teacher']['last_name']);
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Exams | Teacher</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
  </script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <!-- Header -->
  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <div class="flex items-center gap-5">
        <img src="../../img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white shadow-lg">
        <div>
          <h1 class="text-2xl font-extrabold">My Created Exams</h1>
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

  <!-- Back Button + Create New -->
  <section class="bg-gradient-to-r from-deepblue via-midblue to-indigo-700 text-white py-12" data-aos="fade-down">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
      <a href="../teacher_dashboard.php" class="bg-white text-deepblue px-8 py-4 rounded-xl font-bold text-xl hover:bg-lightblue transition shadow-lg flex items-center gap-3">
        Back to Dashboard
      </a>
      <a href="create_exam.php" class="bg-yellow-400 text-deepblue px-8 py-4 rounded-xl font-bold text-xl hover:bg-yellow-300 transition shadow-lg">
        Create New Exam
      </a>
    </div>
  </section>

  <!-- Exams Grid -->
  <main class="max-w-7xl mx-auto px-6 py-12">
    <?php
    $stmt = $pdo->prepare("
        SELECT e.*, CONCAT(c.class_name, ' - ', c.section) AS full_class
        FROM exams e
        JOIN classes c ON e.class_name = c.class_name
        WHERE e.created_by = ?
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$teacher_id]);
    $exams = $stmt->fetchAll();
    ?>

    <?php if (empty($exams)): ?>
      <div class="text-center py-20" data-aos="fade-up">
        <i class="fas fa-file-upload text-9xl text-gray-300 mb-8"></i>
        <h3 class="text-4xl font-bold text-gray-600">No exams created yet</h3>
        <p class="text-xl text-gray-500 mt-4">Start creating your first exam!</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($exams as $i => $exam): ?>
          <?php
          $status_color = match($exam['status']) {
            'pending' => 'from-yellow-500 to-amber-600',
            'dept_approved' => 'from-cyan-500 to-blue-600',
            'published' => 'from-green-500 to-emerald-600',
            'rejected' => 'from-red-500 to-rose-600',
            default => 'from-gray-500 to-gray-600'
          };
          $status_icon = match($exam['status']) {
            'pending' => 'fa-clock',
            'dept_approved' => 'fa-thumbs-up',
            'published' => 'fa-check-circle',
            'rejected' => 'fa-times-circle',
            default => 'fa-question-circle'
          };
          ?>
          <div data-aos="fade-up" data-aos-delay="<?= $i * 100 ?>" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-deepblue overflow-hidden">
            <div class="bg-gradient-to-br <?= $status_color ?> p-8 text-white">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="text-2xl font-bold"><?= htmlspecialchars($exam['exam_name']) ?></h3>
                  <p class="mt-2 text-lg opacity-95"><?= htmlspecialchars($exam['full_class']) ?></p>
                </div>
                <i class="fas <?= $status_icon ?> text-5xl opacity-80"></i>
              </div>
            </div>
            <div class="p-8 space-y-4">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Type:</strong> <?= ucwords($exam['exam_type'] ?? 'N/A') ?></div>
                <div><strong>Marks:</strong> <?= $exam['total_marks'] ?></div>
                <div><strong>Date:</strong> <?= date('d M Y', strtotime($exam['exam_date'])) ?></div>
                <div><strong>Status:</strong> <span class="font-bold"><?= ucwords(str_replace('_', ' ', $exam['status'])) ?></span></div>
              </div>

              <div class="pt-4 flex flex-wrap gap-3">
                <a href="view_exam.php?id=<?= $exam['id'] ?>" target="_blank" class="bg-deepblue text-white px-5 py-3 rounded-xl font-bold hover:bg-midblue transition flex items-center gap-2">
                  View PDF
                </a>
                <?php if ($exam['status'] === 'published'): ?>
                  <a href="view_exam.php?id=<?= $exam['id'] ?>&download=1" class="bg-green-600 text-white px-5 py-3 rounded-xl font-bold hover:bg-green-700 transition flex items-center gap-2">
                    Download
                  </a>
                <?php endif; ?>
                <?php if ($exam['status'] === 'rejected'): ?>
                  <button class="bg-orange-600 text-white px-5 py-3 rounded-xl font-bold hover:bg-orange-700 transition" onclick="document.getElementById('reupload<?= $exam['id'] ?>').showModal()">
                    Re-upload
                  </button>
                <?php endif; ?>
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