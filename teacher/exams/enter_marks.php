<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacher_id = $_SESSION['teacher']['id'] ?? 0;
$teacher_name = trim(($_SESSION['teacher']['first_name'] ?? '') . ' ' . ($_SESSION['teacher']['middle_name'] ?? '') . ' ' . ($_SESSION['teacher']['last_name'] ?? ''));
$message = '';

// Handle marks submission
if (isset($_POST['submit_marks'])) {
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $marks = $_POST['marks'] ?? [];

    // Securely fetch exam + class info
    $stmt = $pdo->prepare("
        SELECT e.*, c.class_name, c.section 
        FROM exams e 
        JOIN classes c ON e.class_name = c.class_name 
        WHERE e.id = ? AND e.status = 'published' AND e.created_by = ?
    ");
    $stmt->execute([$exam_id, $teacher_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        $message = "<div class='bg-red-100 text-red-800 p-6 rounded-xl text-center font-bold'>Invalid or unauthorized exam!</div>";
    } else {
        $saved = 0;
        $stmt = $pdo->prepare("
            INSERT INTO exam_marks (exam_id, student_id, marks, entered_by, entered_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE marks = VALUES(marks), entered_by = VALUES(entered_by), entered_at = NOW()
        ");

        foreach ($marks as $student_id => $mark) {
            $mark = trim($mark);
            if ($mark === '' || !is_numeric($mark)) continue;
            $mark = (float)$mark;
            if ($mark < 0 || $mark > $exam['total_marks']) continue;

            if ($stmt->execute([$exam_id, $student_id, $mark, $teacher_id])) {
                $saved++;
            }
        }

        $message = "<div class='bg-green-100 border-2 border-green-500 text-green-800 p-8 rounded-2xl text-center shadow-2xl'>
            <i class='fas fa-check-circle text-6xl mb-4'></i><br>
            <strong class='text-2xl'>$saved marks saved successfully!</strong><br>
            Exam: <strong>" . htmlspecialchars($exam['exam_name']) . "</strong>
        </div>";
    }
}

// Fetch teacher's published exams with proper class name & section
$stmt = $pdo->prepare("
    SELECT e.id, e.exam_name, e.total_marks, e.exam_date, e.class_name,
           c.section,
           CONCAT(c.class_name, ' - ', c.section) AS full_class
    FROM exams e
    JOIN classes c ON e.class_name = c.class_name
    WHERE e.created_by = ? AND e.status = 'published'
    ORDER BY e.exam_date DESC
");
$stmt->execute([$teacher_id]);
$published_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Enter Marks | Teacher</title>
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
          <h1 class="text-2xl font-extrabold">Enter Student Marks</h1>
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
    <?= $message ?? '' ?>

    <?php if (empty($published_exams)): ?>
      <div class="text-center py-20 bg-white rounded-3xl shadow-2xl" data-aos="fade-up">
        <i class="fas fa-clipboard-check text-9xl text-gray-300 mb-8"></i>
        <h3 class="text-4xl font-bold text-gray-700">No Published Exams Found</h3>
        <p class="text-xl text-gray-600 mt-4">Create exams and get them approved first!</p>
      </div>
    <?php else: ?>
      <div data-aos="fade-up">
        <label class="text-3xl font-bold text-deepblue mb-8 block">Select Exam to Enter Marks</label>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <?php foreach ($published_exams as $exam): ?>
            <button onclick="document.getElementById('exam<?= $exam['id'] ?>').scrollIntoView({behavior:'smooth'})"
                    class="bg-white rounded-2xl shadow-xl p-8 hover:shadow-2xl hover:scale-105 transition text-left border-l-8 border-green-600 text-gray-800">
              <h3 class="text-2xl font-bold text-deepblue mb-3"><?= htmlspecialchars($exam['exam_name']) ?></h3>
              <p class="text-lg font-medium"><strong>Class:</strong> <?= htmlspecialchars($exam['full_class']) ?></p>
              <p class="text-gray-600 mt-2"><strong>Date:</strong> <?= date('d M Y', strtotime($exam['exam_date'])) ?></p>
              <p class="text-gray-600"><strong>Total Marks:</strong> <?= $exam['total_marks'] ?></p>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Marks Entry Forms -->
      <?php foreach ($published_exams as $exam):
        // Safely get students from correct class & section
        $stmt = $pdo->prepare("
            SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name
            FROM students s
            WHERE s.current_year = ? AND s.section = ?
            ORDER BY s.first_name, s.last_name
        ");
        $stmt->execute([$exam['class_name'], $exam['section']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Load existing marks
        $stmt = $pdo->prepare("SELECT student_id, marks FROM exam_marks WHERE exam_id = ?");
        $stmt->execute([$exam['id']]);
        $existing = [];
        foreach ($stmt->fetchAll() as $row) {
            $existing[$row['student_id']] = $row['marks'];
        }
      ?>
        <div id="exam<?= $exam['id'] ?>" class="mt-20 bg-white rounded-3xl shadow-2xl overflow-hidden border-4 border-green-100" data-aos="fade-up">
          <div class="bg-gradient-to-r from-green-600 to-emerald-700 text-white p-10">
            <h2 class="text-4xl font-bold"><?= htmlspecialchars($exam['exam_name']) ?></h2>
            <p class="text-2xl opacity-95 mt-3"><?= htmlspecialchars($exam['full_class']) ?> • Total: <?= $exam['total_marks'] ?> marks</p>
          </div>

          <?php if (empty($students)): ?>
            <div class="p-16 text-center text-gray-500">
              <i class="fas fa-users-slash text-8xl mb-6 text-gray-300"></i>
              <p class="text-2xl font-medium">No students enrolled in this class/section.</p>
            </div>
          <?php else: ?>
            <form method="POST" class="p-10">
              <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
              <div class="overflow-x-auto rounded-2xl border-2 border-gray-200">
                <table class="w-full table-auto">
                  <thead>
                    <tr class="bg-deepblue text-white text-left">
                      <th class="px-8 py-5 font-bold">Student ID</th>
                      <th class="px-8 py-5 font-bold">Full Name</th>
                      <th class="px-8 py-5 text-center w-40 font-bold">Marks (Max <?= $exam['total_marks'] ?>)</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <?php foreach ($students as $s): 
                      $full_name = trim($s['first_name'] . ' ' . ($s['middle_name'] ?? '') . ' ' . $s['last_name']);
                      $current_mark = $existing[$s['id']] ?? '';
                    ?>
                      <tr class="hover:bg-gray-50 transition">
                        <td class="px-8 py-5 font-mono text-deepblue font-bold"><?= htmlspecialchars($s['student_id']) ?></td>
                        <td class="px-8 py-5 font-medium text-gray-800"><?= htmlspecialchars($full_name) ?></td>
                        <td class="px-8 py-5 text-center">
                          <input type="number" name="marks[<?= $s['id'] ?>]" value="<?= $current_mark ?>" 
                                 min="0" max="<?= $exam['total_marks'] ?>" step="0.01"
                                 class="w-32 px-4 py-3 border-2 border-midblue rounded-xl text-center font-bold text-deepblue focus:outline-none focus:border-green-600 transition text-lg"
                                 placeholder="0">
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="text-center mt-10">
                <button type="submit" name="submit_marks" 
                        class="bg-green-600 text-white px-16 py-6 rounded-2xl text-2xl font-bold hover:bg-green-700 transition shadow-2xl transform hover:scale-105">
                  Save All Marks
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <script>
    AOS.init({ once: true, duration: 800 });
  </script>
</body>
</html>