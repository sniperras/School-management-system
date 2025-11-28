<?php
// admin/dashboard.php → Full version with all 10 cards
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
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
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } }
    }
  </script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="font-sans antialiased text-gray-800 bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <!-- Top Bar -->
  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <div class="flex items-center gap-5">
        <img src="../img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white shadow-lg">
        <div>
          <h1 class="text-2xl font-extrabold">SCHOOL MANAGEMENT SYSTEM</h1>
          <p class="text-sm opacity-90">Administrator Portal</p>
        </div>
      </div>
      <div class="flex items-center gap-6">
        <span class="hidden md:block text-lg">
          <strong><?= htmlspecialchars($user['name'] ?? 'Admin') ?></strong>
        </span>
        <a href="../logout.php" class="bg-lightblue text-deepblue px-6 py-3 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-lg">
          Logout
        </a>
      </div>
    </div>
  </header>

  <!-- Welcome Banner -->
  <section class="bg-gradient-to-r from-deepblue via-midblue to-indigo-700 text-white py-16" data-aos="fade-down">
    <div class="max-w-7xl mx-auto px-6 text-center">
      <h1 class="text-5xl md:text-6xl font-extrabold">Welcome back, <span class="text-yellow-300">Admin</span>!</h1>
      <p class="text-2xl mt-4 opacity-95">You have full control over the entire system</p>
      <div class="mt-6 flex justify-center gap-8 text-lg">
        <div><strong><?= date('l, F j, Y') ?></strong></div>
        <div>|</div>
        <div>Academic Year 2025–2026</div>
      </div>
    </div>
  </section>

  <!-- All 10 Admin Cards -->
  <main class="max-w-7xl mx-auto px-6 py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

      <!-- 1. Users -->
      <div data-aos="fade-up" data-aos-delay="100" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-deepblue">
        <div class="bg-gradient-to-br from-deepblue to-midblue p-8 text-white">
          <i class="fas fa-users-cog text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Users</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Manage students, teachers, parents, and admin accounts.</p>
          <a href="users.php" class="block text-center bg-deepblue text-white font-bold py-4 rounded-xl hover:bg-midblue transition shadow-lg">
            Manage Users
          </a>
        </div>
      </div>

      <!-- 2. Applications -->
      <div data-aos="fade-up" data-aos-delay="200" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-green-600">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-8 text-white">
          <i class="fas fa-file-alt text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Applications</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Review and approve new student applications.</p>
          <a href="view_applications.php" class="block text-center bg-green-600 text-white font-bold py-4 rounded-xl hover:bg-emerald-700 transition shadow-lg">
            View Applications
          </a>
        </div>
      </div>

      <!-- 3. Students -->
      <div data-aos="fade-up" data-aos-delay="300" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-cyan-600">
        <div class="bg-gradient-to-br from-cyan-500 to-blue-600 p-8 text-white">
          <i class="fas fa-user-graduate text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Students</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Register, promote, and manage all student records.</p>
          <a href="students.php" class="block text-center bg-cyan-600 text-white font-bold py-4 rounded-xl hover:bg-blue-700 transition shadow-lg">
            Manage Students
          </a>
        </div>
      </div>

      <!-- 4. Teachers -->
      <div data-aos="fade-up" data-aos-delay="400" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-purple-600">
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 p-8 text-white">
          <i class="fas fa-chalkboard-teacher text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Teachers</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Add, assign subjects, and manage staff.</p>
          <a href="teachers.php" class="block text-center bg-purple-600 text-white font-bold py-4 rounded-xl hover:bg-indigo-700 transition shadow-lg">
            Manage Teachers
          </a>
        </div>
      </div>

      <!-- 5. Classes & Sections -->
      <div data-aos="fade-up" data-aos-delay="500" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-orange-600">
        <div class="bg-gradient-to-br from-orange-500 to-red-600 p-8 text-white">
          <i class="fas fa-school text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Classes</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Create classes, sections, and assign teachers.</p>
          <a href="classes.php" class="block text-center bg-orange-600 text-white font-bold py-4 rounded-xl hover:bg-red-700 transition shadow-lg">
            Manage Classes
          </a>
        </div>
      </div>

      <!-- 6. Attendance -->
      <div data-aos="fade-up" data-aos-delay="600" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-teal-600">
        <div class="bg-gradient-to-br from-teal-500 to-cyan-600 p-8 text-white">
          <i class="fas fa-clipboard-check text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Attendance</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Track daily attendance and generate reports.</p>
          <a href="attendance.php" class="block text-center bg-teal-600 text-white font-bold py-4 rounded-xl hover:bg-cyan-700 transition shadow-lg">
            View Reports
          </a>
        </div>
      </div>

      <!-- 7. Exams & Results -->
      <div data-aos="fade-up" data-aos-delay="700" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-pink-600">
        <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-8 text-white">
          <i class="fas fa-trophy text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Exams & Results</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Create exams, enter marks, publish results.</p>
          <a href="exams.php" class="block text-center bg-pink-600 text-white font-bold py-4 rounded-xl hover:bg-rose-700 transition shadow-lg">
            Manage Exams
          </a>
        </div>
      </div>

      <!-- 8. Announcements -->
      <div data-aos="fade-up" data-aos-delay="800" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-yellow-600">
        <div class="bg-gradient-to-br from-yellow-500 to-amber-600 p-8 text-white">
          <i class="fas fa-bullhorn text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Announcements</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Send notices to students, parents & staff.</p>
          <a href="admin_announcement.php" class="block text-center bg-yellow-600 text-white font-bold py-4 rounded-xl hover:bg-amber-700 transition shadow-lg">
            Create Post
          </a>
        </div>
      </div>

      <!-- 9. Finance / Fees -->
      <div data-aos="fade-up" data-aos-delay="900" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-lime-600">
        <div class="bg-gradient-to-br from-lime-500 to-green-600 p-8 text-white">
          <i class="fas fa-dollar-sign text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Finance</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Track fees, payments, and generate receipts.</p>
          <a href="finance.php" class="block text-center bg-lime-600 text-white font-bold py-4 rounded-xl hover:bg-green-700 transition shadow-lg">
            Manage Fees
          </a>
        </div>
      </div>

      <!-- 10. alumni & others -->
      <div data-aos="fade-up" data-aos-delay="1000" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-cyan-700">
        <div class="bg-gradient-to-br from-cyan-600 to-gray-800 p-8 text-white">
          <i class="fas fa-cogs text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Alumni & Contact Messages</h3>
        </div>
        <div class="p-8">
          <p class="text-grey-600 mb-6">Alumni List, Edit, Contact Messages.</p>
          <a href="admin_alumni_list.php" class="block text-center bg-cyan-700 text-white font-bold py-4 rounded-xl hover:bg-cyan-900 transition shadow-lg">
            Open Alumni
          </a>
        </div>
      </div>

<!-- 11. System Settings -->
      <div data-aos="fade-up" data-aos-delay="1000" class="group bg-white rounded-2xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 border-t-4 border-gray-700">
        <div class="bg-gradient-to-br from-gray-600 to-gray-800 p-8 text-white">
          <i class="fas fa-cogs text-6xl opacity-90"></i>
          <h3 class="text-2xl font-bold mt-4">Settings</h3>
        </div>
        <div class="p-8">
          <p class="text-gray-600 mb-6">Backup, school info, academic year, security.</p>
          <a href="settings.php" class="block text-center bg-gray-700 text-white font-bold py-4 rounded-xl hover:bg-gray-900 transition shadow-lg">
            Open Settings
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