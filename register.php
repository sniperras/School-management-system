<?php
// register.php - ENHANCED WITH 3 SECURITY QUESTIONS
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
//equire __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    $role = current_user_role();
    $redirect = match ($role) {
        'admin' => 'admin/dashboard.php',
        'teacher' => 'teacher/teacher_dashboard.php',
        'student' => 'student/student_dashboard.php',
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

// Predefined security questions
$security_questions = [
    "What is the name of your first school?",
    "What is your mother's maiden name?",
    "What is the name of your favorite teacher?",
    "In which city were you born?",
    "What is your favorite food?",
    "What is the name of your first pet?",
    "What is your favorite movie?",
    "What was your childhood nickname?"
];

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

        // Security Questions & Answers
        $q1 = $_POST['security_q1'] ?? '';
        $a1 = trim($_POST['security_a1'] ?? '');
        $q2 = $_POST['security_q2'] ?? '';
        $a2 = trim($_POST['security_a2'] ?? '');
        $q3 = $_POST['security_q3'] ?? '';
        $a3 = trim($_POST['security_a3'] ?? '');

        $first_name_input = trim(explode(' ', $name)[0]);

        if ($role === 'teacher') {
            $official_id = strtolower(preg_replace('/^TCH\/?/i', 'tch/', $raw_id));
        } else {
            $official_id = strtoupper($raw_id);
        }

        // === Validation ===
        if ($name === '') $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) $errors[] = 'Valid phone number required.';
        if (!in_array($role, ['student', 'teacher'])) $errors[] = 'Please select a valid role.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        // Security Questions Validation
        if ($q1 === '' || $q2 === '' || $q3 === '') {
            $errors[] = 'Please select all 3 security questions.';
        }
        if ($a1 === '' || $a2 === '' || $a3 === '') {
            $errors[] = 'Please provide answers to all 3 security questions.';
        }
        if ($q1 === $q2 || $q1 === $q3 || $q2 === $q3) {
            $errors[] = 'Please choose 3 different security questions.';
        }
        if (strlen($a1) < 2 || strlen($a2) < 2 || strlen($a3) < 2) {
            $errors[] = 'Each answer must be at least 2 characters long.';
        }

        // ID + First Name verification (unchanged)
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
                        $errors[] = "First name does not match our records for ID <strong>$official_id</strong>.";
                                    //  Expected: <strong>{$record['first_name']}</strong><br>
                                    //  You entered: <strong>$first_name_input</strong>";
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
                        $errors[] = "Teacher ID <strong>$official_id</strong> not found.";
                    } elseif (strcasecmp($record['first_name'], $first_name_input) !== 0) {
                        $errors[] = "First name does not match our records for ID <strong>$official_id</strong>.";
                    }
                }
            }
        }

        // Prevent duplicate account
        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM users WHERE student_id = ?");
            $check->execute([$official_id]);
            if ($check->fetch()) {
                $errors[] = "An account already exists for ID <strong>$official_id</strong>. Please login.";
            }
        }

        // === ALL GOOD → Register User ===
        if (empty($errors)) {
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
                $ans1 = password_hash(strtolower($a1), PASSWORD_DEFAULT);
                $ans2 = password_hash(strtolower($a2), PASSWORD_DEFAULT);
                $ans3 = password_hash(strtolower($a3), PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        name, email, phone, username, password_hash, role, student_id,
                        security_q1, security_a1, security_q2, security_a2, security_q3, security_a3
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $name, $email, $phone, $username, $hash, $role, $official_id,
                    $q1, $ans1, $q2, $ans2, $q3, $ans3
                ]);

                $success = true;
                $registered_name = $name;
                $entered_id = $official_id;
                $generated_username = $username;
            } catch (Exception $e) {
                $errors[] = 'Registration failed. Email or phone may already be in use.';
            }
        }
    }
}
?>

<?php require_once 'includes/head.php'; ?>
<?php require_once 'includes/nav.php'; ?>
<title>Register Account | School System</title>

<section class="py-20 bg-gray-50 min-h-screen">
  <div class="max-w-5xl mx-auto px-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 md:p-16">

      <?php if ($success): ?>
        <!-- Success Message (unchanged) -->
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

        <form method="post" class="space-y-10">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

          <!-- Existing Fields (Name, ID, Email, Phone, Role, Password) -->
          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Full Name <span class="text-red-600">*</span></label>
              <input name="name" type="text" required value="<?= htmlspecialchars($_POST['name']??'') ?>" 
                     class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue transition text-lg" 
                     placeholder="Abebe Gemechu G/Hiwot">
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
              <label class="block text-lg font-bold text-deepblue mb-3">Email Address <span class="text-red-600">*</span></label>
              <input name="email" type="email" required value="<?= htmlspecialchars($_POST['email']??'') ?>" class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Phone Number <span class="text-red-600">*</span></label>
              <input name="phone" type="tel" required value="<?= htmlspecialchars($_POST['phone']??'') ?>" placeholder="+251911223344" class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">I am a... <span class="text-red-600">*</span></label>
              <select name="role" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg" id="roleSelect">
                <option value="">Select role</option>
                <option value="student" <?= ($_POST['role']??'')==='student'?'selected':'' ?>>Student</option>
                <option value="teacher" <?= ($_POST['role']??'')==='teacher'?'selected':'' ?>>Teacher / Staff</option>
              </select>
            </div>
          </div>

          <!-- Password Fields -->
          <div class="grid md:grid-cols-2 gap-8">
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Password <span class="text-red-600">*</span></label>
              <input name="password" type="password" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
            <div>
              <label class="block text-lg font-bold text-deepblue mb-3">Confirm Password <span class="text-red-600">*</span></label>
              <input name="confirm_password" type="password" required class="w-full px-6 py-5 border-2 rounded-2xl focus:border-midblue text-lg">
            </div>
          </div>

          <!-- Security Questions Section -->
          <div class="mt-12 p-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-3xl border-2 border-indigo-200">
            <h3 class="text-2xl font-bold text-deepblue mb-6 text-center">
              Security Questions (Required for Password Recovery)
            </h3>

            <div class="space-y-8">
              <!-- Question 1 -->
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label class="block font-semibold text-deepblue">Question 1</label>
                  <select name="security_q1" required class="w-full px-5 py-4 border-2 rounded-xl mt-2">
                    <option value="">Choose a question...</option>
                    <?php foreach ($security_questions as $q): ?>
                      <option value="<?= htmlspecialchars($q) ?>" <?= ($_POST['security_q1']??'')===htmlspecialchars($q)?'selected':'' ?>>
                        <?= htmlspecialchars($q) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block font-semibold text-deepblue">Your Answer</label>
                  <input name="security_a1" type="text" required value="<?= htmlspecialchars($_POST['security_a1']??'') ?>" 
                         class="w-full px-5 py-4 border-2 rounded-xl mt-2" placeholder="Type your answer...">
                </div>
              </div>

              <!-- Question 2 -->
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label class="block font-semibold text-deepblue">Question 2</label>
                  <select name="security_q2" required class="w-full px-5 py-4 border-2 rounded-xl mt-2">
                    <option value="">Choose a different question...</option>
                    <?php foreach ($security_questions as $q): ?>
                      <option value="<?= htmlspecialchars($q) ?>" <?= ($_POST['security_q2']??'')===htmlspecialchars($q)?'selected':'' ?>>
                        <?= htmlspecialchars($q) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block font-semibold text-deepblue">Your Answer</label>
                  <input name="security_a2" type="text" required value="<?= htmlspecialchars($_POST['security_a2']??'') ?>" 
                         class="w-full px-5 py-4 border-2 rounded-xl mt-2" placeholder="Type your answer...">
                </div>
              </div>
              </div>

              <!-- Question 3 -->
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label class="block font-semibold text-deepblue">Question 3</label>
                  <select name="security_q3" required class="w-full px-5 py-4 border-2 rounded-xl mt-2">
                    <option value="">Choose another question...</option>
                    <?php foreach ($security_questions as $q): ?>
                      <option value="<?= htmlspecialchars($q) ?>" <?= ($_POST['security_q3']??'')===htmlspecialchars($q)?'selected':'' ?>>
                        <?= htmlspecialchars($q) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block font-semibold text-deepblue">Your Answer</label>
                  <input name="security_a3" type="text" required value="<?= htmlspecialchars($_POST['security_a3']??'') ?>" 
                         class="w-full px-5 py-4 border-2 rounded-xl mt-2" placeholder="Type your answer...">
                </div>
              </div>
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

  idInput?.addEventListener('input', function() {
    if (roleSelect?.value === 'teacher') {
      let val = this.value.toLowerCase().replace(/[^a-z0-9\/]/g, '');
      if (val && !val.startsWith('tch/')) {
        val = 'tch/' + val.replace(/^tch\/?/, '');
      }
      this.value = val;
    } else {
      this.value = this.value.toUpperCase();
    }
  });
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>