<?php
// student/student_dashboard.php
declare(strict_types=1);

// Correct paths: go up one level to root includes/
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if not logged in
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit;
}

// Restrict access to students only
if (current_user_role() !== 'student') {
    $redirect = match (current_user_role()) {
        'admin'   => '../admin/dashboard.php',
        'teacher' => '../teacher/teacher_dashboard.php',
        default   => '../index.php'
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
  <title>Student Dashboard | School Management System</title>

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
  
  <style>
    .e { escape: <?= json_encode(e('test')) ?> }
  </style>
</head>
<body class="font-sans antialiased text-gray-800 bg-cream min-h-screen">

  <!-- Top Bar -->
  <header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <img src="../img/school-logo.png" alt="School Logo" class="h-12 w-12 rounded-full border-4 border-white object-cover">
        <div>
          <h1 class="text-xl font-bold">SCHOOL MANAGEMENT SYSTEM</h1>
          <p class="text-xs opacity-90">Student Portal</p>
        </div>
      </div>
      <div class="flex items-center gap-6 text-sm">
        <span class="hidden md:block">Welcome, <strong class="text-lightblue"><?= htmlspecialchars($user['display_name'] ?? $user['name'] ?? 'Student', ENT_QUOTES) ?></strong></span>
        <a href="../logout.php" class="bg-lightblue text-deepblue px-5 py-2 rounded-lg font-bold hover:bg-midblue hover:text-white transition">
          <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
      </div>
    </div>
  </header>

  <!-- Main Dashboard -->
  <main class="max-w-7xl mx-auto px-6 py-12">

    <!-- Welcome Hero -->
    <section class="bg-gradient-to-r from-deepblue to-midblue text-white rounded-3xl p-10 mb-12 shadow-2xl" data-aos="fade-down">
      <div class="flex flex-col md:flex-row items-center justify-between gap-8">
        <div>
          <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">
            Hello, <?= htmlspecialchars($user['display_name'] ?? $user['name'] ?? 'Student', ENT_QUOTES) ?>!
          </h1>
          <p class="text-xl mt-3 opacity-95">Ready to learn and grow today?</p>
        </div>
        <div class="text-right">
          <p class="text-6xl font-bold"><?= date('d') ?></p>
          <p class="text-lg opacity-90"><?= date('F Y') ?></p>
          <p class="text-sm mt-2">Academic Year 2025–2026</p>
        </div>
      </div>
    </section>

    <!-- Quick Actions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

      <!-- Attendance -->
      <div data-aos="fade-up" data-aos-delay="100" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 text-white">
          <i class="fas fa-calendar-check text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">My Attendance</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">View your daily attendance record and monthly summary.</p>
          <a href="student_attendance.php" class="inline-block bg-green-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-emerald-700 transition transform hover:scale-105 shadow-lg">
            View Attendance
          </a>
        </div>
      </div>

      <!-- Grades & Report Card -->
      <div data-aos="fade-up" data-aos-delay="200" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-6 text-white">
          <i class="fas fa-chart-bar text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Grades & Results</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Check your exam results, GPA, and download report cards.</p>
          <a href="exam_results.php" class="inline-block bg-blue-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-indigo-700 transition transform hover:scale-105 shadow-lg">
            View Grades
          </a>
        </div>
      </div>

      <!-- Assignments & Homework -->
      <div data-aos="fade-up" data-aos-delay="300" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-purple-500 to-pink-600 p-6 text-white">
          <i class="fas fa-book-open text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Assignments</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Download homework, projects, and submit assignments online.</p>
          <a href="student_assignments.php" class="inline-block bg-purple-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-pink-700 transition transform hover:scale-105 shadow-lg">
            Open Assignments
          </a>
        </div>
      </div>

      <!-- Timetable -->
      <div data-aos="fade-up" data-aos-delay="400" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white">
          <i class="fas fa-clock text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Class Schedule</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">View your weekly timetable and upcoming classes.</p>
          <a href="student_timetable.php" class="inline-block bg-orange-600 text-white font-bold px-8 py-4 rounded-xl hover:bg-red-700 transition transform hover:scale-105 shadow-lg">
            View Schedule
          </a>
        </div>
      </div>

    

      <!-- Profile -->
      <div data-aos="fade-up" data-aos-delay="600" class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-3 border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-br from-gray-600 to-gray-800 p-6 text-white">
          <i class="fas fa-user-graduate text-5xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">My Profile</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Update personal info, change password, and view details.</p>
          <a href="student_profile.php" class="inline-block bg-gray-700 text-white font-bold px-8 py-4 rounded-xl hover:bg-gray-900 transition transform hover:scale-105 shadow-lg">
            Edit Profile
          </a>
        </div>
      </div>

    </div>
  </main>



  <script>
    AOS.init({ once: true, duration: 1000, easing: 'ease-out-quart' });
  </script>
</body>
</html>