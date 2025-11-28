<?php
// login.php – Login with Username OR Student/Staff ID
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php'; // We need $pdo here

// If user is already logged in → redirect to their dashboard
if (is_logged_in()) {
    $role = current_user_role();
    $redirect = match ($role) {
        'admin'   => 'admin/dashboard.php',
        'teacher' => 'teacher/teacher_dashboard.php',
        'student' => 'student/student_dashboard.php',
        default   => 'index.php'
    };
    header("Location: $redirect");
    exit;
}

$errors = [];
$next = $_GET['next'] ?? ($_POST['next'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $login_input = trim($_POST['username'] ?? ''); // This can be username OR ID
        $password    = $_POST['password'] ?? '';

        if ($login_input === '' || $password === '') {
            $errors[] = 'Please enter both username/ID and password.';
        } else {
            // Try to find user by username first
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$login_input]);
            $user = $stmt->fetch();

            // If not found by username → try by student_id (works for both student & teacher)
            if (!$user) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
                $stmt->execute([$login_input]);
                $user = $stmt->fetch();
            }

            // Now verify password
            if ($user && password_verify($password, $user['password_hash'])) {
                // Success! Log the user in
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['name']      = $user['name'];
                $_SESSION['logged_in'] = true;

                $role = $user['role'];
                $redirect = match ($role) {
                    'admin'   => 'admin/dashboard.php',
                    'teacher' => 'teacher/teacher_dashboard.php',
                    'student' => 'student/student_dashboard.php',
                    default   => 'index.php'
                };
                header("Location: $redirect");
                exit;
            } else {
                $errors[] = 'Invalid username, ID, or password.';
            }
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Login | School Management System</title>

<style>
  .login-container {
    min-height: calc(100vh - 200px);
    background: linear-gradient(rgba(255,251,222,0.97), rgba(145,200,228,0.3)),
                url('img/login-bg.jpg') center/cover no-repeat;
  }
</style>

<!-- Main Login Section -->
<section class="login-container flex items-center justify-center py-20">
  <div class="w-full max-w-md mx-6">
    <div data-aos="fade-up" class="bg-white/98 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-gray-100">

      <!-- Logo & Title -->
      <div class="text-center mb-10">
        <img src="img/school-logo.png" 
             alt="School Logo" 
             class="h-24 w-24 mx-auto rounded-full border-4 border-deepblue shadow-lg mb-6 bg-deepblue">

        <h1 class="text-4xl md:text-5xl font-extrabold text-deepblue mt-6">Welcome Back</h1>
        <p class="text-xl text-midblue mt-3">Sign in to your SMS account</p>
      </div>

      <!-- Error Messages -->
      <?php if ($errors): ?>
        <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl text-sm font-medium">
          <?php foreach ($errors as $err): ?>
            <div class="flex items-center gap-3">
              <i class="fas fa-exclamation-triangle text-xl"></i>
              <?= htmlspecialchars($err) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="post" novalidate class="space-y-7">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

        <div>
          <label class="block text-sm font-bold text-deepblue mb-2">
            <i class="fas fa-user text-midblue mr-2"></i> Username or Student/Staff ID
          </label>
          <input name="username" type="text" required autofocus
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/40 transition text-lg placeholder-gray-400"
                 placeholder="e.g. selam123, ADMA/1234/25 or tch/567/23">
          <p class="text-xs text-gray-500 mt-2 text-left">You can use your username or your official ID</p>
        </div>

        <div>
          <label class="block text-sm font-bold text-deepblue mb-2">
            <i class="fas fa-lock text-midblue mr-2"></i> Password
          </label>
          <input name="password" type="password" required
                 class="w-full px-6 py-5 border-2 border-gray-300 rounded-2xl focus:border-midblue focus:ring-4 focus:ring-lightblue/40 transition text-lg"
                 placeholder="Enter your password">
        </div>

        <button type="submit"
                class="w-full bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-xl py-6 rounded-2xl hover:shadow-2xl hover:scale-105 transition transform duration-300 uppercase tracking-wider flex items-center justify-center gap-3">
          <i class="fas fa-sign-in-alt text-2xl"></i>
          Sign In
        </button>
      </form>

      <!-- Quick Role Hints -->
      <div class="mt-10 grid grid-cols-3 gap-4 text-center text-xs font-bold">
        <div class="bg-red-50 text-red-700 py-4 rounded-xl border-2 border-red-200">
          <i class="fas fa-user-shield text-xl"></i><br>Admin
        </div>
        <div class="bg-purple-50 text-purple-700 py-4 rounded-xl border-2 border-purple-200">
          <i class="fas fa-chalkboard-teacher text-xl"></i><br>Teacher
        </div>
        <div class="bg-green-50 text-green-700 py-4 rounded-xl border-2 border-green-200">
          <i class="fas fa-user-graduate text-xl"></i><br>Student
        </div>
      </div>

      <!-- Links -->
      <div class="mt-10 text-center text-sm text-gray-600 space-y-3">
        <a href="forgot_password.php" class="text-midblue hover:text-deepblue font-semibold hover:underline">
          Forgot your password?
        </a>
        <div>
          New here? 
          <a href="register.php" class="text-deepblue font-bold hover:underline">Create an account</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>