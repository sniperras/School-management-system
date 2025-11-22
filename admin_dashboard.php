<?php
// admin_dashboard.php
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

// Only allow admins
if (!is_logged_in() || current_user_role() !== 'admin') {
    $redirect = match (current_user_role()) {
        'teacher' => 'teacher_dashboard.php',
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
  <title>Admin Dashboard | School Management System</title>

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
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <!-- Top Admin Bar -->
  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <div class="flex items-center gap-5">
        <img src="img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white shadow-lg">
        <div>
          <h1 class="text-2xl font-extrabold">SCHOOL MANAGEMENT SYSTEM</h1>
          <p class="text-sm opacity-90 flex items-center gap-2">
            <i class="fas fa-crown text-yellow-400"></i>
            Administrator Portal
          </p>
        </div>
      </div>
      <div class="flex items-center gap-6">
        <span class="hidden md:block text-lg">
          <i class="fas fa-user-shield mr-2 text-yellow-300"></i>
          <strong><?= e($user['display_name'] ?? $user['name']) ?></strong>
        </span>
        <a href="logout.php" class="bg-lightblue text-deepblue px-6 py-3 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-lg">
          <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
      </div>
    </div>
  </header>

  <!-- Hero Welcome -->
  <section class="bg-gradient-to-r from-deepblue via-midblue to-indigo-700 text-white py-16" data-aos="fade-down">
    <div class="max-w-7xl mx-auto px-6 text-center">
      <h1 class="text-5xl md:text-6xl font-extrabold leading-tight">
        Welcome back, <span class="text-yellow-300">Admin</span>!
      </h1>
      <p class="text-2xl mt-4 opacity-95">You have full control over the system</p>
      <div class="mt-6 flex justify-center gap-8 text-lg">
        <div><strong><?= date('l, F j, Y') ?></strong></div>
        <div>|</div>
        <div>Academic Year 2025–2026</div>
      </div>
    </div>
  </section>

  <!-- Admin Power Grid -->
  <main class="max-w-7xl mx-auto px-6 py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

      <!-- Users Management -->
      <div data-aos="fade-up" data-aos-delay="100" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-deepblue overflow-hidden">
        <div class="bg-gradient-to-br from-deepblue to-midblue p-8 text-white">
          <i class="fas fa-users-cog text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Users</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Manage students, teachers, parents, and admin accounts.</p>
          <a href="admin_users.php" class="inline-block w-full text-center bg-deepblue text-white font-bold py-4 rounded-xl hover:bg-midblue transition shadow-lg">
            Manage Users
          </a>
        </div>
      </div>

      <!-- Students -->
      <div data-aos="fade-up" data-aos-delay="200" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-green-600">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-8 text-white">
          <i class="fas fa-user-graduate text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Students</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Register, edit, promote, and view all student records.</p>
          <a href="admin_students.php" class="inline-block w-full text-center bg-green-600 text-white font-bold py-4 rounded-xl hover:bg-emerald-700 transition shadow-lg">
            Manage Students
          </a>
        </div>
      </div>

      <!-- Teachers -->
      <div data-aos="fade-up" data-aos-delay="300" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-purple-600">
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-8 text-white">
          <i class="fas fa-chalkboard-teacher text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Teachers</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Add, assign classes, and manage teacher profiles.</p>
          <a href="admin_teachers.php" class="inline-block w-full text-center bg-purple-600 text-white font-bold py-4 rounded-xl hover:bg-indigo-700 transition shadow-lg">
            Manage Teachers
          </a>
        </div>
      </div>

      <!-- Classes & Subjects -->
      <div data-aos="fade-up" data-aos-delay="400" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-orange-600">
        <div class="bg-gradient-to-br from-orange-500 to-red-600 p-8 text-white">
          <i class="fas fa-school text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Classes & Subjects</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Create classes, assign subjects and teachers.</p>
          <a href="admin_classes.php" class="inline-block w-full text-center bg-orange-600 text-white font-bold py-4 rounded-xl hover:bg-red-700 transition shadow-lg">
            Manage Classes
          </a>
        </div>
      </div>

      <!-- Attendance Overview -->
      <div data-aos="fade-up" data-aos-delay="500" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-teal-600">
        <div class="bg-gradient-to-br from-teal-500 to-cyan-600 p-8 text-white">
          <i class="fas fa-clipboard-list text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Attendance</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">View reports and statistics across all classes.</p>
          <a href="admin_attendance.php" class="inline-block w-full text-center bg-teal-600 text-white font-bold py-4 rounded-xl hover:bg-cyan-700 transition shadow-lg">
            View Reports
          </a>
        </div>
      </div>

      <!-- Marks & Results -->
      <div data-aos="fade-up" data-aos-delay="600" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-pink-600">
        <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-8 text-white">
          <i class="fas fa-chart-pie text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Marks & Results</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Generate report cards and performance analytics.</p>
          <a href="admin_marks.php" class="inline-block w-full text-center bg-pink-600 text-white font-bold py-4 rounded-xl hover:bg-rose-700 transition shadow-lg">
            View Analytics
          </a>
        </div>
      </div>

      <!-- Announcements -->
      <div data-aos="fade-up" data-aos-delay="700" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-yellow-600">
        <div class="bg-gradient-to-br from-yellow-500 to-amber-600 p-8 text-white">
          <i class="fas fa-bullhorn text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Announcements</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Post school-wide notices and important updates.</p>
          <a href="admin_announcements.php" class="inline-block w-full text-center bg-yellow-600 text-white font-bold py-4 rounded-xl hover:bg-amber-700 transition shadow-lg">
            Create Post
          </a>
        </div>
      </div>

      <!-- System Settings -->
      <div data-aos="fade-up" data-aos-delay="800" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-gray-700">
        <div class="bg-gradient-to-br from-gray-600 to-gray-800 p-8 text-white">
          <i class="fas fa-cogs text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">System Settings</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Backup database, configure school info, and security.</p>
          <a href="admin_settings.php" class="inline-block w-full text-center bg-gray-700 text-white font-bold py-4 rounded-xl hover:bg-gray-900 transition shadow-lg">
            Open Settings
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