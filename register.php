<?php
// register.php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
// If user is already logged in → redirect to their dashboard
if (is_logged_in()) {
    $role = current_user_role();
    $redirect = match ($role) {
        'admin'   => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        'student' => 'student_dashboard.php',
        default   => 'index.php'
    };
    header("Location: $redirect");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $role     = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($name === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($phone === '' || !preg_match('/^\+?[0-9]{10,15}$/', $phone)) $errors[] = 'Valid phone number is required.';
        if (!in_array($role, ['student', 'parent', 'teacher'])) $errors[] = 'Please select a valid role.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            // In real app: save to database with hashed password
            // For now: simulate success
            $success = true;
            // Clear form on success
            $_POST = [];
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Create Account | School Management System</title>

<!-- Registration Form -->
<section class="py-20 bg-gray-50">
  <div class="max-w-4xl mx-auto px-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 md:p-16">

      <?php if ($success): ?>
        <div class="mb-10 p-8 bg-green-50 border-2 border-green-200 text-green-800 rounded-2xl text-center">
          <i class="fas fa-check-circle text-6xl mb-4 block"></i>
          <h2 class="text-3xl font-bold">Registration Successful!</h2>
          <p class="text-lg mt-4">
            Thank you, <strong><?= htmlspecialchars($_POST['name'] ?? '') ?></strong><br>
            Your account has been created. Please check your email for confirmation.
          </p>
          <a href="login.php" class="inline-block mt-6 bg-deepblue text-white px-10 py-4 rounded-xl font-bold hover:bg-midblue transition">
            Go to Login
          </a>
        </div>
      <?php else: ?>

        <h2 class="text-4xl font-bold text-center text-deepblue mb-12">Create Your Account</h2>

        <?php if ($errors): ?>
          <div class="mb-8 p-6 bg-red-50 border border-red-200 text-red-700 rounded-2xl">
            <?php foreach ($errors as $err): ?>
              <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($err) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" novalidate class="space-y-8">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">
                <i class="fas fa-user mr-2 text-midblue"></i> Full Name
              </label>
              <input name="name" type="text" required value="<?= e($_POST['name'] ?? '') ?>"
                     class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition text-lg"
                     placeholder="e.g. Amina Yusuf">
            </div>

            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">
                <i class="fas fa-envelope mr-2 text-midblue"></i> Email Address
              </label>
              <input name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>"
                     class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition text-lg"
                     placeholder="amina@example.com">
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">
                <i class="fas fa-phone mr-2 text-midblue"></i> Phone Number
              </label>
              <input name="phone" type="tel" required value="<?= e($_POST['phone'] ?? '') ?>"
                     class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition text-lg"
                     placeholder="+251 911 223 344">
            </div>

            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">
                <i class="fas fa-user-tag mr-2 text-midblue"></i> I am a...
              </label>
              <select name="role" required class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition text-lg">
                <option value="">Select your role</option>
                <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="parent" <?= ($_POST['role'] ?? '') === 'parent' ? 'selected' : '' ?>>Parent / Guardian</option>
                <option value="teacher" <?= ($_POST['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher / Staff</option>
              </select>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">
                <i class="fas fa-lock mr-2 text-midblue"></i> Password
              </label>
              <input name="password" type="password" required
                     class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition text-lg"
                     placeholder="Create a strong password">
            </div>

            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">
                <i class="fas fa-lock mr-2 text-midblue"></i> Confirm Password
              </label>
              <input name="confirm_password" type="password" required
                     class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition text-lg"
                     placeholder="Type password again">
            </div>
          </div>

          <div class="text-center pt-8">
            <button type="submit"
                    class="bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-xl px-16 py-6 rounded-2xl hover:shadow-2xl hover:scale-105 transition transform duration-300 uppercase tracking-wider">
              Create My Account
            </button>
          </div>

          <p class="text-center text-gray-600 mt-10">
            Already have an account? 
            <a href="login.php" class="text-deepblue font-bold hover:underline">Login here</a>
          </p>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>