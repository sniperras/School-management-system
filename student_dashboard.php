<?php
// student_dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Only allow students
if (!is_logged_in() || current_user_role() !== 'student') {
    if (is_logged_in()) {
        $role = current_user_role();
        if ($role === 'teacher') {
            header('Location: teacher_dashboard.php'); exit;
        } elseif ($role === 'admin') {
            header('Location: admin_dashboard.php'); exit;
        }
    }
    header('Location: index.php'); exit;
}

$user = current_user();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<header class="bg-green-50 py-8 shadow-md">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <h1 class="text-3xl font-bold text-gray-900">Student Dashboard</h1>
    <p class="text-gray-600 mt-2">Access your academic resources</p>
    <p class="text-gray-700 mt-1">Logged in as: <?php echo e($user['display_name']); ?></p>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Attendance</h2>
    <p class="text-gray-600 mb-4">Check your attendance record.</p>
    <a href="student_attendance.php" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">View Attendance</a>
  </div>

  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Grades</h2>
    <p class="text-gray-600 mb-4">View your grades and report cards.</p>
    <a href="student_grades.php" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">View Grades</a>
  </div>

  <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Assignments</h2>
    <p class="text-gray-600 mb-4">Download homework and assignments.</p>
    <a href="student_assignments.php" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">View Assignments</a>
  </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
