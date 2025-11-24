<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php"); exit;
}

$teacher_id = $_SESSION['teacher']['id'];
$teacher_name = trim($_SESSION['teacher']['first_name'] . ' ' . ($_SESSION['teacher']['middle_name'] ?? '') . ' ' . $_SESSION['teacher']['last_name']);
$teacher_department = $_SESSION['teacher']['department'] ?? '';
$message = '';

// Get all classes
$stmt = $pdo->prepare("SELECT id, class_name, section, CONCAT(class_name, ' - ', section) AS full_class FROM classes ORDER BY class_name, section");
$stmt->execute();
$all_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle submission
if (isset($_POST['submit_attendance'])) {
    $class_id = (int)$_POST['class_id'];
    $today = date('Y-m-d');

    if ($_POST['attendance_date'] !== $today) {
        $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl mb-6'>Error: Only today's attendance is allowed!</div>";
    } else {
        $check = $pdo->prepare("SELECT marked_at FROM attendance WHERE class_id = ? AND attendance_date = ? LIMIT 1");
        $check->execute([$class_id, $today]);
        $already = $check->fetch();

        if ($already) {
            $marked_time = date('h:i A', strtotime($already['marked_at']));
            $message = "<div class='bg-yellow-100 border border-yellow-400 text-yellow-800 px-6 py-4 rounded-xl mb-6'>Attendance already taken today at $marked_time</div>";
        } else {
            $success = 0;
            foreach ($_POST['status'] as $student_internal_id => $status) {
                if (in_array($status, ['present', 'absent', 'late'])) {
                    // Use the internal `id` from students table (not student_id string)
                    $stmt = $pdo->prepare("INSERT INTO attendance 
                        (student_id, class_id, attendance_date, status, marked_by, marked_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$student_internal_id, $class_id, $today, $status, $teacher_id]);
                    $success++;
                }
            }
            $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6'>Attendance successfully saved for <strong>$success students</strong>!</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mark Attendance</title>
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
          <h1 class="text-2xl font-extrabold">Mark Attendance</h1>
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

  <!-- Back Button -->
  <section class="bg-gradient-to-r from-deepblue via-midblue to-indigo-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-6 text-center">
      <a href="../teacher_dashboard.php" class="bg-white text-deepblue px-8 py-4 rounded-xl font-bold text-xl hover:bg-lightblue transition shadow-lg inline-flex items-center gap-3">
        Back to Dashboard
      </a>
    </div>
  </section>

  <main class="max-w-7xl mx-auto px-6 py-12">
    <div class="bg-white rounded-3xl shadow-2xl p-10" data-aos="fade-up">

      <h2 class="text-4xl font-extrabold text-deepblue text-center mb-10">Select Class to Mark Attendance</h2>

      <?= $message ?>

      <form method="GET" class="mb-12">
        <div class="max-w-2xl mx-auto">
          <select name="class_id" onchange="this.form.submit()" class="w-full p-6 border-4 border-deepblue rounded-2xl text-xl font-bold text-deepblue bg-white shadow-2xl focus:outline-none focus:ring-4 focus:ring-lightblue" required>
            <option value="">-- Select Class & Section --</option>
            <?php foreach ($all_classes as $cls): ?>
              <option value="<?= $cls['id'] ?>" <?= (isset($_GET['class_id']) && $_GET['class_id'] == $cls['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cls['full_class']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <?php if (isset($_GET['class_id'])): 
        $class_id = (int)$_GET['class_id'];

        $class_stmt = $pdo->prepare("SELECT class_name, section FROM classes WHERE id = ?");
        $class_stmt->execute([$class_id]);
        $class = $class_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$class) {
            echo "<div class='text-center text-red-600 text-2xl'>Invalid class.</div>";
            exit;
        }

        $today = date('Y-m-d');
        $check = $pdo->prepare("SELECT marked_at FROM attendance WHERE class_id = ? AND attendance_date = ? LIMIT 1");
        $check->execute([$class_id, $today]);
        $already = $check->fetch();

        // Get students using internal id
        $stmt = $pdo->prepare("
            SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name
            FROM students s
            WHERE s.current_year = ? 
              AND s.section = ?
              AND s.department = ?
            ORDER BY s.first_name, s.last_name
        ");
        $stmt->execute([$class['class_name'], $class['section'], $teacher_department]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

        <div class="bg-gradient-to-r from-deepblue to-indigo-800 text-white p-8 rounded-3xl mb-10 text-center shadow-xl">
          <h3 class="text-4xl font-extrabold"><?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?></h3>
          <p class="text-2xl mt-4">Date: <strong><?= date('l, F j, Y') ?></strong></p>
          <?php if ($already): ?>
            <p class="text-yellow-300 text-xl mt-4">
              Marked at <?= date('h:i A', strtotime($already['marked_at'])) ?>
              <?php if (strtotime($already['marked_at']) < strtotime('-3 days')): ?>
                <br><span class="text-red-400 font-bold text-2xl">LOCKED — Cannot edit after 3 days</span>
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </div>

        <?php if ($already && strtotime($already['marked_at']) < strtotime('-3 days')): ?>
          <div class="text-center py-20 bg-gray-50 rounded-3xl">
            <i class="fas fa-lock text-9xl text-red-500 mb-8"></i>
            <h3 class="text-5xl font-bold text-red-600">Attendance Locked</h3>
          </div>

        <?php elseif ($already): ?>
          <div class="text-center py-20 bg-green-50 rounded-3xl">
            <i class="fas fa-check-circle text-9xl text-green-600 mb-8"></i>
            <h3 class="text-5xl font-bold text-green-700">Already Taken Today</h3>
          </div>

        <?php else: ?>
          <?php if (empty($students)): ?>
            <div class="text-center py-20 bg-orange-50 rounded-3xl">
              <i class="fas fa-users-slash text-8xl text-orange-500 mb-6"></i>
              <h3 class="text-3xl font-bold text-orange-700">No students in this class/section</h3>
            </div>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="class_id" value="<?= $class_id ?>">
              <input type="hidden" name="attendance_date" value="<?= $today ?>">

              <div class="overflow-x-auto rounded-3xl shadow-2xl">
                <table class="w-full bg-white">
                  <thead class="bg-gradient-to-r from-deepblue to-indigo-700 text-white">
                    <tr>
                      <th class="p-6 text-left">#</th>
                      <th class="p-6 text-left">Student ID</th>
                      <th class="p-6 text-left">Name</th>
                      <th class="p-6 text-center text-green-300">Present</th>
                      <th class="p-6 text-center text-red-300">Absent</th>
                      <th class="p-6 text-center text-yellow-300">Late</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $i => $s): 
                      $name = trim($s['first_name'] . ' ' . ($s['middle_name'] ?? '') . ' ' . $s['last_name']);
                    ?>
                      <tr class="border-b hover:bg-lightblue hover:bg-opacity-20">
                        <td class="p-6 font-bold"><?= $i + 1 ?></td>
                        <td class="p-6 font-mono text-indigo-700"><?= htmlspecialchars($s['student_id']) ?></td>
                        <td class="p-6 font-semibold"><?= htmlspecialchars($name) ?></td>
                        <td class="p-6 text-center">
                          <input type="radio" name="status[<?= $s['id'] ?>]" value="present" required class="w-8 h-8 text-green-600">
                        </td>
                        <td class="p-6 text-center">
                          <input type="radio" name="status[<?= $s['id'] ?>]" value="absent" class="w-8 h-8 text-red-600">
                        </td>
                        <td class="p-6 text-center">
                          <input type="radio" name="status[<?= $s['id'] ?>]" value="late" class="w-8 h-8 text-yellow-600">
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="text-center mt-12">
                <button type="submit" name="submit_attendance" class="bg-green-600 text-white px-20 py-7 rounded-3xl font-extrabold text-3xl hover:bg-green-700 transition shadow-2xl transform hover:scale-105">
                  Submit Attendance
                </button>
              </div>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>

  <script>
    AOS.init({ once: true, duration: 1200 });
  </script>
</body>
</html>