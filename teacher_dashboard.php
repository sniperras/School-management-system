<?php
// teacher_dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

// Optional: Extra layer — only allow correct role
$allowed = match (basename($_SERVER['PHP_SELF'])) {
    'admin_dashboard.php'   => 'admin',
    'teacher_dashboard.php' => 'teacher',
    'student_dashboard.php' => 'student',
    default                 => null
};

if ($allowed && current_user_role() !== $allowed) {
    // Trying to access wrong dashboard → redirect to correct one
    $correct = match (current_user_role()) {
        'admin'   => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        'student' => 'student_dashboard.php',
        default   => 'index.php'
    };
    header("Location: $correct");
    exit;
}

// Restrict access to teachers only
if (!is_logged_in() || current_user_role() !== 'teacher') {
    $redirect = match (current_user_role()) {
        'admin'   => 'admin_dashboard.php',
        'student' => 'student_dashboard.php',
        default   => 'index.php'
    };
    header("Location: $redirect");
    exit;
}

$user = current_user();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Teacher Dashboard | School Management System</title>

  <!-- Tailwind CDN + Custom Colors -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            deepblue: '#4682A9',
            lightblue: '#91C8E4',
            midblue: '#749BC2',
            cream: '#FFFBDE',
          }
        }
      }
    }
  </script>

  <!-- AOS Animations -->
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="font-sans antialiased text-gray-800 bg-cream min-h-screen">

  <!-- Top Bar -->
  <header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <img src="img/school-logo.png" alt="School Logo" class="h-12 w-12 rounded-full border-4 border-white">
        <div>
          <h1 class="text-xl font-bold">SCHOOL MANAGEMENT SYSTEM</h1>
          <p class="text-xs opacity-90">Teacher Portal</p>
        </div>
      </div>
      <div class="flex items-center gap-6 text-sm">
        <span class="hidden md:block">Welcome, <strong class="text-lightblue"><?= e($user['display_name'] ?? $user['name']) ?></strong></span>
        <a href="logout.php" class="bg-lightblue text-deepblue px-5 py-2 rounded-lg font-bold hover:bg-midblue hover:text-white transition">
          <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
      </div>
    </div>
  </header>

  <!-- Main Dashboard -->
  <main class="max-w-7xl mx-auto px-6 py-12">

    <!-- Welcome Hero -->
    <section class="bg-gradient-to-r from-purple-600 to-deepblue text-white rounded-3xl p-10 mb-12 shadow-2xl" data-aos="fade-down">
      <div class="flex flex-col md:flex-row items-center justify-between gap-8">
        <div>
          <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">
            Welcome back, <?= e($user['display_name'] ?? 'Teacher') ?>!
          </h1>
          <p class="text-xl mt-3 opacity-95">Let's make today a great learning day</p>
        </div>
        <div class="text-right">
          <p class="text-6xl font-bold"><?= date('d') ?></p>
          <p class="text-lg opacity-90"><?= date('F Y') ?></p>
          <p class="text-sm mt-2">Academic Year 2025–2026</p>
        </div>
      </div>
    </section>

    <!-- Teacher Tools Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- My Classes -->
      <div data-aos="fade-up" data-aos-delay="100" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-6 text-white">
          <i class="fas fa-chalkboard-teacher text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">My Classes</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">View assigned classes, students, and schedules.</p>
          <a href="teacher_classes.php" class="inline-block bg-indigo-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-purple-700 transition transform hover:scale-105 shadow-lg">
            Manage Classes
          </a>
        </div>
      </div>

      <!-- Take Attendance -->
      <div data-aos="fade-up" data-aos-delay="200" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-green-500 to-teal-600 p-6 text-white">
          <i class="fas fa-clipboard-check text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Take Attendance</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Mark daily attendance for your classes quickly.</p>
          <a href="teacher_attendance.php" class="inline-block bg-green-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-teal-700 transition transform hover:scale-105 shadow-lg">
            Record Attendance
          </a>
        </div>
      </div>

      <!-- Enter Grades -->
      <div data-aos="fade-up" data-aos-delay="300" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white">
          <i class="fas fa-edit text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Enter Grades</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Submit exam results and manage student performance.</p>
          <a href="teacher_grades.php" class="inline-block bg-orange-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-red-700 transition transform hover:scale-105 shadow-lg">
            Grade Students
          </a>
        </div>
      </div>

      <!-- Assignments & Homework -->
      <div data-aos="fade-up" data-aos-delay="400" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-6 text-white">
          <i class="fas fa-tasks text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Assignments</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Upload homework, projects, and track submissions.</p>
          <a href="teacher_assignments.php" class="inline-block bg-pink-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-rose-700 transition transform hover:scale-105 shadow-lg">
            Manage Assignments
          </a>
        </div>
      </div>

      <!-- Announcements -->
      <div data-aos="fade-up" data-aos-delay="500" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-cyan-500 to-blue-600 p-6 text-white">
          <i class="fas fa-bullhorn text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Announcements</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Post notices, events, and reminders to students.</p>
          <a href="teacher_announcements.php" class="inline-block bg-cyan-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-blue-700 transition transform hover:scale-105 shadow-lg">
            Create Announcement
          </a>
        </div>
      </div>

      <!-- Teacher Profile -->
      <div data-aos="fade-up" data-aos-delay="600" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-gray-600 to-gray-800 p-6 text-white">
          <i class="fas fa-id-card text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">My Profile</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Update contact info, view schedule, and settings.</p>
          <a href="teacher_profile.php" class="inline-block bg-gray-700 text-white font-bold px-8 py-4 rounded-xl hover:bg-gray-900 transition transform hover:scale-105 shadow-lg">
            Edit Profile
          </a>
        </div>
      </div>

    </div>
  </main>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>

  <script>
    AOS.init({ once: true, duration: 1000, easing: 'ease-out-quart' });
  </script>
</body>
</html>