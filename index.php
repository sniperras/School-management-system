<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = current_user();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ====== Header / Hero ====== -->
<header class="bg-gradient-to-br from-blue-50 to-indigo-100 py-20 text-center shadow-md">
  <div class="hero-content max-w-3xl mx-auto">
    <h1 class="text-5xl font-bold text-gray-900 mb-4">Welcome to University Portal</h1>
    <p class="text-lg text-gray-600 mb-8">Your gateway to managing Students, Faculty, Classes, and Campus Life</p>

    <?php if (!$user): ?>
      <a href="login.php" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition">Get Started</a>
    <?php else: ?>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="admin_dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition">Go to Admin Dashboard</a>
      <?php elseif ($user['role'] === 'teacher'): ?>
        <a href="teacher_dashboard.php" class="bg-purple-600 text-white px-6 py-3 rounded-md hover:bg-purple-700 transition">Go to Teacher Dashboard</a>
      <?php elseif ($user['role'] === 'student'): ?>
        <a href="student_dashboard.php" class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition">Go to Student Dashboard</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</header>

<!-- ====== Main Content ====== -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">🎓 Student Services</h2>
    <p class="text-gray-600">Register, track attendance, view grades, and access assignments.</p>
  </div>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">👩‍🏫 Faculty Management</h2>
    <p class="text-gray-600">Manage faculty profiles, assign subjects, and monitor performance.</p>
  </div>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">🏫 Academic Programs</h2>
    <p class="text-gray-600">Organize classes, subjects, and schedules across departments.</p>
  </div>
  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">📢 Campus Announcements</h2>
    <p class="text-gray-600">Stay updated with the latest news, events, and notifications.</p>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
