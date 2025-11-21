<?php
// includes/navbar.php
$user = current_user();

$current = basename($_SERVER['PHP_SELF']);
function nav_class(string $page, string $activeColor = 'text-blue-600'): string {
    global $current;
    if ($current === $page) {
        return $activeColor . ' font-semibold bg-gray-200 px-3 py-1 rounded-md';
    }
    return 'text-gray-700 hover:bg-gray-200 px-3 py-1 rounded-md';
}
?>
<nav class="bg-white border-b-2 border-blue-200 shadow-sm">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">

    <div class="flex items-center space-x-4">
      <?php if (!$user): ?>
        <!-- Show Home + Login when not logged in -->
        <a href="index.php" class="<?php echo nav_class('index.php'); ?>">Home</a>
        <a href="login.php" class="<?php echo nav_class('login.php','text-blue-600'); ?>">Login</a>
      <?php else: ?>
        <!-- Show Dashboard + Logout when logged in -->
        <?php if ($user['role'] === 'admin'): ?>
          <a href="admin_dashboard.php" class="<?php echo nav_class('admin_dashboard.php','text-blue-600'); ?>">Admin Dashboard</a>
        <?php elseif ($user['role'] === 'teacher'): ?>
          <a href="teacher_dashboard.php" class="<?php echo nav_class('teacher_dashboard.php','text-purple-600'); ?>">Teacher Dashboard</a>
        <?php elseif ($user['role'] === 'student'): ?>
          <a href="student_dashboard.php" class="<?php echo nav_class('student_dashboard.php','text-green-600'); ?>">Student Dashboard</a>
        <?php endif; ?>
        <a href="logout.php" class="<?php echo nav_class('logout.php'); ?>">Logout</a>
      <?php endif; ?>

      <!-- Common links always visible -->
      <a href="departments.php" class="<?php echo nav_class('departments.php'); ?>">Departments</a>
      <a href="announcements.php" class="<?php echo nav_class('announcements.php'); ?>">Announcements</a>
      <a href="contact.php" class="<?php echo nav_class('contact.php'); ?>">Contact</a>
    </div>

    <!-- Right side greeting -->
    <?php if ($user): ?>
      <div class="text-sm text-gray-700">
        Hello, <span class="font-semibold"><?php echo e($user['display_name']); ?></span>
      </div>
    <?php endif; ?>
  </div>
</nav>
