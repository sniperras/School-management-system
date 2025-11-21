<?php
// admin_dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Only allow admins
if (!is_logged_in() || current_user_role() !== 'admin') {
    if (is_logged_in()) {
        $role = current_user_role();
        if ($role === 'teacher') {
            header('Location: teacher_dashboard.php'); exit;
        } elseif ($role === 'student') {
            header('Location: student_dashboard.php'); exit;
        }
    }
    header('Location: index.php'); exit;
}

$user = current_user();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<header class="bg-blue-50 py-8 shadow-md">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
    <p class="text-gray-600 mt-2">System management and administration</p>
    <p class="text-gray-700 mt-1">Logged in as: <?php echo e($user['display_name']); ?></p>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 space-y-6">
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Manage Departments</h2>
    <p class="text-gray-600 mb-4">Create, update, and organize academic departments.</p>
    <a href="departments.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Go to Departments</a>
  </div>

  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Manage Announcements</h2>
    <p class="text-gray-600 mb-4">Post and edit campus-wide announcements.</p>
    <a href="announcements.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Go to Announcements</a>
  </div>

  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Messages</h2>
    <p class="text-gray-600 mb-4">View and respond to student/teacher messages.</p>
    <a href="messages_admin.php" class="bg-gray-200 px-4 py-2 rounded-md hover:bg-gray-300">View Messages</a>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
