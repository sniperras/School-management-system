<?php
// forgot_password.php
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
$email = $_POST['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = 'Security check failed. Please try again.';
    } elseif (empty($email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // In real app: generate reset token + send email
        // For now: simulate success after 1.5 seconds
        $success = true;
    }
}
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Forgot Password | School Management System</title>

<style>
  .fp-bg {
    background: linear-gradient(rgba(70,130,169,0.92), rgba(116,155,194,0.88)), 
                url('img/login-bg.jpg') center/cover no-repeat;
    min-height: 100vh;
  }
</style>

<!-- Main Section -->
<section class="fp-bg flex items-center justify-center py-20">
  <div class="w-full max-w-md mx-6">
    <div data-aos="zoom-in" class="bg-white/98 backdrop-blur-xl rounded-3xl shadow-2xl p-10 md:p-12 text-center">

      <!-- Logo -->
      <img src="img/school-logo.png" alt="School Logo" 
           class="h-24 w-24 mx-auto rounded-full border-4 border-deepblue shadow-lg mb-6">

      <h1 class="text-4xl md:text-5xl font-extrabold text-deepblue mb-4">
        Forgot Password?
      </h1>
      <p class="text-lg text-gray-700 mb-10">
        No worries! Just enter your email and we’ll send you a password reset link.
      </p>

      <!-- Success Message -->
      <?php if ($success): ?>
        <div class="mb-8 p-8 bg-green-50 border-2 border-green-200 text-green-800 rounded-2xl">
          <i class="fas fa-check-circle text-6xl mb-4 block"></i>
          <h2 class="text-2xl font-bold mb-3">Check Your Email!</h2>
          <p class="text-base leading-relaxed">
            We’ve sent a password reset link to:<br>
            <strong class="text-deepblue"><?= htmlspecialchars($email) ?></strong>
          </p>
          <p class="text-sm mt-4 text-gray-600">
            Didn’t receive it? Check spam or 
            <a href="forgot_password.php" class="text-midblue font-bold hover:underline">try again</a>.
          </p>
          <a href="login.php" class="inline-block mt-6 bg-deepblue text-white px-10 py-4 rounded-xl font-bold hover:bg-midblue transition">
            Back to Login
          </a>
        </div>

      <?php else: ?>

        <!-- Error Messages -->
        <?php if ($errors): ?>
          <div class="mb-8 p-6 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-left">
            <?php foreach ($errors as $err): ?>
              <div class="flex items-center gap-3 mb-2">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($err) ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Reset Form -->
        <form method="post" novalidate class="space-y-8">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

          <div>
            <label class="block text-left text-lg font-bold text-deepblue mb-3">
              <i class="fas fa-envelope mr-2 text-midblue"></i> Your Email Address
            </label>
            <input name="email" type="email" required autofocus
                   value="<?= htmlspecialchars($email) ?>"
                   class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/40 transition text-lg placeholder-gray-400"
                   placeholder="e.g. student@smschool.edu.et">
          </div>

          <button type="submit"
                  class="w-full bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-xl py-6 rounded-2xl hover:shadow-2xl hover:scale-105 transition transform duration-300 uppercase tracking-wider flex items-center justify-center gap-3">
            <i class="fas fa-paper-plane text-2xl"></i>
            Send Reset Link
          </button>
        </form>

        <div class="mt-10 text-center space-y-3 text-gray-600">
          <p>
            Remember your password? 
            <a href="login.php" class="text-deepblue font-bold hover:underline">Back to Login</a>
          </p>
          <p class="text-sm">
            Or 
            <a href="contact_us.php" class="text-midblue hover:underline font-semibold">
              contact support
            </a> 
            if you need help
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>