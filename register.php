<?php
// register.php - SECURE SELF-REGISTRATION: ID + First Name must match real record
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php'; // Make sure $pdo is defined here

if (is_logged_in()) {
    $role = current_user_role();
    $redirect = match ($role) {
        'admin' => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        'student' => 'student_dashboard.php',
        default => 'index.php'
    };
    header("Location: $redirect");
    exit;
}

$errors = [];
$success = false;
$registered_name = '';
$entered_id = '';
$generated_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $role       = $_POST['role'] ?? '';
        $raw_id     = trim($_POST['student_id'] ?? '');
        $password   = $_POST['password'] ?? '';
        $confirm    = $_POST['confirm_password'] ?? '';

        // Extract only first name from full name input
        $first_name_input = trim(explode(' ', $name)[0]);

        // Normalize ID format
        if ($role === 'teacher') {
            $official_id = strtolower(preg_replace('/^TCH\/?/i', 'tch/', $raw_id));
        } else {
            $official_id = strtoupper($raw_id);
        }

        // Basic validation
        if ($name === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) $errors[] = 'Valid phone number required.';
        if (!in_array($role, ['student', 'teacher'])) $errors[] = 'Please select a valid role.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        // === MAIN SECURITY CHECK: ID + First Name must exist in real table ===
        if (empty($errors)) {
            if ($role === 'student') {
                if (!preg_match('/^ADMA\/[0-9]+\/[0-9]{2}$/i', $official_id)) {
                    $errors[] = 'Student ID must be in format: ADMA/1234/25';
                } else {
                    $stmt = $pdo->prepare("SELECT first_name FROM students WHERE student_id = ? LIMIT 1");
                    $stmt->execute([$official_id]);
                    $record = $stmt->fetch();

                    if (!$record) {
                        $errors[] = "Student ID <strong>$official_id</strong> not found. Contact admin.";
                    } elseif (strcasecmp($record['first_name'], $first_name_input) !== 0) {
                        $errors[] = "First name does not match our records for ID <strong>$official_id</strong>.<br>
                                     Expected: <strong>{$record['first_name']}</strong><br>
                                     You entered: <strong>$first_name_input</strong>";
                    }
                }
            }

            if ($role === 'teacher') {
                if (!preg_match('/^tch\/[0-9]+\/[0-9]{2}$/', $official_id)) {
                    $errors[] = 'Teacher ID must be in format: tch/2343/23';
                } else {
                    $stmt = $pdo->prepare("SELECT first_name FROM teachers WHERE teacher_id = ? LIMIT 1");
                    $stmt->execute([$official_id]);
                    $record = $stmt->fetch();

                    if (!$record) {
                        $errors[] = "Teacher ID <strong>$official_id</strong> not found. Ask admin to add you first.";
                    } elseif (strcasecmp($record['first_name'], $first_name_input) !== 0) {
                        $errors[] = "First name does not match our records for ID <strong>$official_id</strong>.<br>
                                     Expected: <strong>{$record['first_name']}</strong><br>
                                     You entered: <strong>$first_name_input</strong>";
                    }
                }
            }
        }

        // Prevent duplicate registration
        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
            $check->execute([$official_id]);
            if ($check->fetch()) {
                $errors[] = "An account already exists for ID <strong>$official_id</strong>. Please login instead.";
            }
        }

        // All checks passed → create account
        if (empty($errors)) {
            // Generate smart username
            $parts = array_filter(preg_split('/\s+/', $name));
            $first = strtolower($parts[0] ?? '');
            $father = strtolower($parts[1] ?? '');
            $base = $first . ($father ? substr($father, 0, 2) : '');
            $username = $base;
            $i = 1;
            $check_user = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
            while (true) {
                $check_user->execute([$username]);
                if (!$check_user->fetchColumn()) break;
                $username = $base . $i++;
            }

            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, username, password_hash, role, student_id) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $username, $hash, $role, $official_id]);

                $success = true;
                $registered_name = $name;
                $entered_id = $official_id;
                $generated_username = $username;
            } catch (Exception $e) {
                $errors[] = 'Registration failed. This email or phone may already be in use.';
            }
        }
    }
}
?>

<?php require_once 'includes/head.php'; ?>
<?php require_once 'includes/nav.php'; ?>
<title>Register Account | School System</title>

<section class="py-20 bg-gray-50 min-h-screen">
  <div class="max-w-4xl mx-auto px-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 md:p-16">

      <?php if ($success): ?>
        <div class="text-center py-16">
          <i class="fas fa-check-circle text-9xl text-green-500 mb-8"></i>
          <h2 class="text-5xl font-bold text-deepblue mb-8">Account Created Successfully!</h2>
          
          <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-3xl p-12 max-w-2xl mx-auto shadow-xl">
            <p class="text-2xl mb-10">Welcome <strong class="text-deepblue"><?= htmlspecialchars($registered_name) ?></strong>!</p>
            
            <div class="grid md:grid-cols-2 gap-8 text-left">
              <div class="bg-white rounded-2xl p-8 shadow-lg border-4 border-blue-200">
                <p class="text-lg text-gray-600 mb-3">Username</p>
                <p class="text-4xl font-bold font-mono text-midblue"><?= htmlspecialchars($generated_username) ?></p>
              </div>
              <div class="bg-white rounded-2xl p-8 shadow-lg border-4 border-purple-200">
                <p class="text-lg text-gray-600 mb-3">Your Official ID</p>
                <p class="text-4xl font-bold font-mono text-deepblue"><?= htmlspecialchars($entered_id) ?></p>
              </div>
            </div>
          </div>

          <a href="login.php" class="inline-block mt-12 bg-gradient-to-r from-deepblue to-midblue text-white text-2xl font-bold px-20 py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
            Go to Login
          </a>
        </div>

      <?php else: ?>
        <h2 class="text-4xl font-bold text-center text-deepblue mb-6">Create Your Account</h2>
        <p class="text-center text-gray-600 mb-10 text-lg">
          Only registered students and teachers can create an account.<br>
          Your <strong>Official ID</strong> and <strong>First Name</strong> must match our records exactly.
        </p>

        <?php if ($errors): ?>
          <div class="mb-8 p-6 bg-red-50 border-2 border-red-300 text-red-800 rounded-2xl text-left">
            <?php foreach ($errors as $e): ?>
              <div class="flex items-start gap-3 mb-3"><i class="fas fa-times-circle mt-1"></i> <?= $e ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-8">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Full Name <span class="text-red-600">*</span></label>
              <input name="name" type="text" required value="<?= htmlspecialchars($_POST['name']??'') ?>" 
                     class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue transition text-lg" 
                     placeholder="Natnael Bizuneh Zenebe">
              <p class="text-sm text-gray-500 mt-2">Must match your official first name exactly</p>
            </div>

            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Official ID <span class="text-red-600">*</span></label>
              <input name="student_id" type="text" required value="<?= htmlspecialchars($_POST['student_id']??'') ?>" 
                     placeholder="ADMA/1311/25 or tch/2343/23" id="idInput"
                     class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue transition text-lg font-mono uppercase">
              <p class="text-sm text-gray-600 mt-2" id="idHint">Enter your official school ID</p>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Email Address</label>
              <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email']??'') ?>" class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Phone Number</label>
              <input name="phone" type="tel" required value="<?= htmlspecialchars($_POST['phone']??'') ?>" placeholder="+251911223344" class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">I am a...</label>
              <select name="role" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg" id="roleSelect">
                <option value="">Select role</option>
                <option value="student" <?= ($_POST['role']??'')==='student'?'selected':'' ?>>Student</option>
                <option value="teacher" <?= ($_POST['role']??'')==='teacher'?'selected':'' ?>>Teacher / Staff</option>
              </select>
            </div>
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Password</label>
              <input name="password" type="password" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Confirm Password</label>
              <input name="confirm_password" type="password" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
          </div>

          <div class="text-center pt-10">
            <button type="submit" class="bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-2xl px-20 py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
              Create Account
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
  const roleSelect = document.getElementById('roleSelect');
  const idInput = document.getElementById('idInput');
  const idHint = document.getElementById('idHint');

  roleSelect?.addEventListener('change', function() {
    if (this.value === 'student') {
      idInput.placeholder = 'ADMA/1311/25';
      idHint.innerHTML = 'Your official admission number (e.g. ADMA/2025/23)';
    } else if (this.value === 'teacher') {
      idInput.placeholder = 'tch/2343/23';
      idHint.innerHTML = 'Your official staff ID (e.g. tch/1001/24)';
    }
  });

  // Auto-format teacher ID to lowercase
  idInput?.addEventListener('input', function() {
    if (roleSelect?.value === 'teacher') {
      let val = this.value.toLowerCase().replace(/[^a-z0-9\/]/g, '');
      if (val && !val.startsWith('tch/')) {
        val = 'tch/' + val.replace(/^tch\/?/, '');
      }
      this.value = val;
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>