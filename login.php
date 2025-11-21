<?php
// login.php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$next = $_GET['next'] ?? ($_POST['next'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            $errors[] = 'Username and password are required.';
        } else {
            if (attempt_login_user($username, $password)) {
                $role = current_user_role();
                if ($role === 'admin') {
                    header('Location: admin_dashboard.php');
                    exit;
                } elseif ($role === 'teacher') {
                    header('Location: teacher_dashboard.php');
                    exit;
                } elseif ($role === 'student') {
                    header('Location: student_dashboard.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/includes/navbar.php';

?>


<div class="flex items-center justify-center min-h-screen bg-gray-100">
  <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-4 text-center text-gray-900">Login</h2>

    <?php if ($errors): ?>
      <div class="mb-4 rounded-md bg-red-100 p-3 text-red-700">
        <?php foreach ($errors as $err): ?>
          <div><?php echo e($err); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" novalidate class="space-y-4">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="next" value="<?php echo e($next); ?>">

      <div>
        <label class="block text-sm font-medium text-gray-700">Username</label>
        <input name="username"
               class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500"
               value="<?php echo e($_POST['username'] ?? ''); ?>">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Password</label>
        <input name="password" type="password"
               class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit"
              class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition">
        Sign In
      </button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-600">
      <a href="index.php" class="text-blue-600 hover:underline">Back to Home</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
