<?php
// forgot_password.php - SECURITY QUESTIONS BASED PASSWORD RESET
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

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

$step = 1; // 1 = Enter ID, 2 = Answer questions, 3 = Set new password, 4 = Success
$errors = [];
$user_data = null;
$official_id = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['step'])) {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $step = (int)$_POST['step'];

        if ($step === 1) {
            $official_id = trim($_POST['official_id'] ?? '');
            $role = $_POST['role'] ?? '';

            if ($official_id === '') $errors[] = 'Please enter your Official ID.';
            if (!in_array($role, ['student', 'teacher'])) $errors[] = 'Please select a valid role.';

            if (empty($errors)) {
                $table = $role === 'teacher' ? 'teachers' : 'students';
                $id_field = $role === 'teacher' ? 'teacher_id' : 'student_id';

                $stmt = $pdo->prepare("SELECT u.* FROM users u 
                                       LEFT JOIN $table t ON u.student_id = t.$id_field 
                                       WHERE u.student_id = ? AND u.role = ? LIMIT 1");
                $stmt->execute([$official_id, $role]);
                $user_data = $stmt->fetch();

                if (!$user_data) {
                    $errors[] = "No account found with this Official ID and role.";
                } elseif (empty($user_data['security_q1'])) {
                    $errors[] = "This account doesn't have security questions. Contact admin.";
                } else {
                    $step = 2; // Proceed to questions
                }
            }
        }

        elseif ($step === 2) {
            $official_id = trim($_POST['official_id'] ?? '');
            $role = $_POST['role'] ?? '';
            $a1 = strtolower(trim($_POST['answer1'] ?? ''));
            $a2 = strtolower(trim($_POST['answer2'] ?? ''));
            $a3 = strtolower(trim($_POST['answer3'] ?? ''));

            if ($a1 === '' || $a2 === '' || $a3 === '') {
                $errors[] = 'Please answer all three questions.';
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE student_id = ? AND role = ? LIMIT 1");
                $stmt->execute([$official_id, $role]);
                $user_data = $stmt->fetch();

                if ($user_data && 
                    password_verify($a1, $user_data['security_a1']) &&
                    password_verify($a2, $user_data['security_a2']) &&
                    password_verify($a3, $user_data['security_a3'])) {
                    $step = 3; // All answers correct
                } else {
                    $errors[] = 'One or more answers are incorrect. Please try again.';
                }
            }
        }

        elseif ($step === 3) {
            $official_id = trim($_POST['official_id'] ?? '');
            $role = $_POST['role'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (strlen($new_pass) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } elseif ($new_pass !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE student_id = ? AND role = ?");
                $stmt->execute([$hash, $official_id, $role]);

                $step = 4; // Success!
            }
        }
    }
}
?>

<?php require_once 'includes/head.php'; ?>
<?php require_once 'includes/nav.php'; ?>
<title>Forgot Password | School System</title>

<style>
  .fp-bg { background: linear-gradient(rgba(70,130,169,0.92), rgba(116,155,194,0.88)), 
           url('img/school-building.jpg') center/cover no-repeat; min-height: 100vh; }
</style>

<section class="fp-bg flex items-center justify-center py-20">
  <div class="w-full max-w-2xl mx-6">
    <div class="bg-white/98 backdrop-blur-xl rounded-3xl shadow-2xl p-10 md:p-14 text-center">

      <img src="img/school-logo.png" alt="School Logo" class="h-24 w-24 mx-auto rounded-full border-4 border-deepblue shadow-lg mb-6">

      <h1 class="text-4xl md:text-5xl font-extrabold text-cream mb-4">
        Forgot Password?
      </h1>

      <!-- STEP 1: Enter ID & Role -->
      <?php if ($step === 1): ?>
        <p class="text-lg text-cream mb-10">Enter your Official ID and role to begin recovery.</p>

        <?php if ($errors): ?>
          <div class="mb-8 p-6 bg-red-50 border-2 border-red-300 text-red-800 rounded-2xl text-left">
            <?php foreach ($errors as $e): ?><div class="flex items-start gap-3 mb-3"><i class="fas fa-times-circle mt-1"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-8">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="step" value="1">

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-cream mb-3">Official ID</label>
              <input name="official_id" type="text" required autofocus value="<?= htmlspecialchars($official_id) ?>" 
                     class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg font-mono uppercase"
                     placeholder="ADMA/1234/25 or tch/234/23">
            </div>
            <div>
              <label class="block text-lg font-bold text-cream mb-3">I am a...</label>
              <select name="role" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
                <option value="">Select role</option>
                <option value="student" <?= $role==='student'?'selected':'' ?>>Student</option>
                <option value="teacher" <?= $role==='teacher'?'selected':'' ?>>Teacher / Staff</option>
              </select>
            </div>
          </div>

          <button type="submit" class="w-full bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-xl py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
            Next →
          </button>
        </form>
      <?php endif; ?>

      <!-- STEP 2: Answer Security Questions -->
      <?php if ($step === 2 && $user_data): ?>
        <p class="text-lg text-cream mb-8">Please answer your security questions to continue.</p>

        <?php if ($errors): ?>
          <div class="mb-8 p-6 bg-red-50 border-2 border-red-300 text-red-800 rounded-2xl text-left">
            <?php foreach ($errors as $e): ?><div class="flex items-start gap-3 mb-3"><i class="fas fa-times-circle mt-1"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-8">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="step" value="2">
          <input type="hidden" name="official_id" value="<?= htmlspecialchars($official_id) ?>">
          <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

          <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-8 rounded-3xl border-2 border-indigo-200 space-y-8">
            <div>
              <p class="font-bold text-deepblue text-left mb-2">1. <?= htmlspecialchars($user_data['security_q1']) ?></p>
              <input name="answer1" type="text" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg" placeholder="Your answer">
            </div>
            <div>
              <p class="font-bold text-deepblue text-left mb-2">2. <?= htmlspecialchars($user_data['security_q2']) ?></p>
              <input name="answer2" type="text" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg" placeholder="Your answer">
            </div>
            <div>
              <p class="font-bold text-deepblue text-left mb-2">3. <?= htmlspecialchars($user_data['security_q3']) ?></p>
              <input name="answer3" type="text" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg" placeholder="Your answer">
            </div>
          </div>

          <button type="submit" class="w-full bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-xl py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
            Verify Answers
          </button>
        </form>
      <?php endif; ?>

      <!-- STEP 3: Set New Password -->
      <?php if ($step === 3): ?>
        <p class="text-lg text-cream mb-8">All answers correct! Now set your new password.</p>

        <?php if ($errors): ?>
          <div class="mb-8 p-6 bg-red-50 border-2 border-red-300 text-red-800 rounded-2xl text-left">
            <?php foreach ($errors as $e): ?><div class="flex items-start gap-3 mb-3"><i class="fas fa-times-circle mt-1"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-8">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="step" value="3">
          <input type="hidden" name="official_id" value="<?= htmlspecialchars($official_id) ?>">
          <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-cream mb-3">New Password</label>
              <input name="new_password" type="password" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
            <div>
              <label class="block text-lg font-bold text-cream mb-3">Confirm Password</label>
              <input name="confirm_password" type="password" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
          </div>

          <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold text-xl py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
            Update Password
          </button>
        </form>
      <?php endif; ?>

      <!-- STEP 4: Success -->
      <?php if ($step === 4): ?>
        <div class="py-16">
          <i class="fas fa-check-circle text-9xl text-green-500 mb-8 block"></i>
          <h2 class="text-5xl font-bold text-cream mb-8">Password Updated Successfully!</h2>
          <p class="text-xl text-cream mb-10">You can now log in with your new password.</p>
          <a href="login.php" class="inline-block bg-gradient-to-r from-deepblue to-midblue text-white text-2xl font-bold px-20 py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
            Go to Login
          </a>
        </div>
      <?php endif; ?>

      <div class="mt-10 text-center text-cream">
        <p>
          Remember your password? 
          <a href="login.php" class="text-cream underline font-bold hover:underline">Back to Login</a>
        </p>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>