<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$alert = '';

// Fetch student + user data
$stmt = $pdo->prepare("
    SELECT s.*, u.email AS user_email, u.phone AS user_phone 
    FROM students s 
    LEFT JOIN users u ON s.id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Contact Info
    if (isset($_POST['update_contact'])) {
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">Invalid email format!</div>';
        } elseif (!preg_match('/^\+?\d{10,15}$/', $phone)) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">Invalid phone number!</div>';
        } else {
            $stmt = $pdo->prepare("UPDATE students SET phone = ? WHERE id = ?");
            $stmt->execute([$phone, $student_id]);

            $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$email, $phone, $student_id]);

            $alert = '<div class="bg-green-100 text-green-700 p-4 rounded">Contact info updated successfully!</div>';
            $student['phone'] = $phone;
            $student['user_email'] = $email;
        }
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$student_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($old, $hash)) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">Old password is incorrect!</div>';
        } elseif ($new !== $confirm) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">New passwords do not match!</div>';
        } elseif (strlen($new) < 6) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">New password must be at least 6 characters!</div>';
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_hash, $student_id]);
            $alert = '<div class="bg-green-100 text-green-700 p-4 rounded">Password changed successfully!</div>';
        }
    }

    // Upload Photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $file = $_FILES['photo'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">Only JPG, PNG, GIF allowed!</div>';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $alert = '<div class="bg-red-100 text-red-700 p-4 rounded">Photo must be under 2MB!</div>';
        } else {
            $photo = file_get_contents($file['tmp_name']);
            $stmt = $pdo->prepare("UPDATE students SET passport_photo = ? WHERE id = ?");
            $stmt->execute([$photo, $student_id]);
            $alert = '<div class="bg-green-100 text-green-700 p-4 rounded">Profile photo updated!</div>';
            $student['passport_photo'] = $photo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Minimal Navbar -->
    <div class="bg-gradient-to-r from-purple-700 to-indigo-800 text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-wide">
                <i class="fas fa-user-circle mr-3"></i> My Profile
            </h1>
            <a href="student_dashboard.php" class="bg-white text-indigo-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-100 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-12">
        <?= $alert ?>

        <div class="grid md:grid-cols-3 gap-10">

            <!-- Profile Photo & Basic Info -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-3xl shadow-2xl p-8 text-center">
                    <?php if ($student['passport_photo']): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($student['passport_photo']) ?>" alt="Profile" class="w-48 h-48 rounded-full mx-auto object-cover border-8 border-indigo-100 shadow-xl">
                    <?php else: ?>
                        <div class="w-48 h-48 rounded-full mx-auto bg-gray-200 border-8 border-indigo-100 flex items-center justify-center">
                            <i class="fas fa-user text-6xl text-gray-400"></i>
                        </div>
                    <?php endif; ?>

                    <h2 class="text-3xl font-bold mt-6"><?= htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']) ?></h2>
                    <p class="text-xl text-indigo-600 font-semibold"><?= $student['student_id'] ?></p>
                    <p class="text-gray-600 mt-2"><?= $student['department'] ?> • <?= $student['current_year'] ?> <?= $student['section'] ?></p>

                    <form method="POST" enctype="multipart/form-data" class="mt-6">
                        <label class="block">
                            <span class="sr-only">Choose profile photo</span>
                            <input type="file" name="photo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </label>
                        <button type="submit" class="mt-4 w-full bg-indigo-600 text-white py-3 rounded-full font-bold hover:bg-indigo-700 transition">
                            <i class="fas fa-upload mr-2"></i> Update Photo
                        </button>
                    </form>
                </div>
            </div>

            <!-- Editable Contact + Password -->
            <div class="md:col-span-2 space-y-8">

                <!-- Contact Info -->
                <div class="bg-white rounded-3xl shadow-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-address-book mr-3 text-indigo-600"></i> Contact Information
                    </h3>
                    <form method="POST" class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium">Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? $student['user_phone'] ?? '') ?>" class="mt-2 w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium">Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($student['user_email'] ?? '') ?>" class="mt-2 w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500" required>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" name="update_contact" class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white px-10 py-4 rounded-full font-bold hover:shadow-xl transition transform hover:scale-105">
                                <i class="fas fa-save mr-3"></i> Save Contact Info
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-3xl shadow-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-lock mr-3 text-red-600"></i> Change Password
                    </h3>
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 font-medium">Current Password</label>
                            <input type="password" name="old_password" class="mt-2 w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-4 focus:ring-red-200 focus:border-red-500" required>
                        </div>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 font-medium">New Password</label>
                                <input type="password" name="new_password" class="mt-2 w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-medium">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="mt-2 w-full px-5 py-3 border border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="bg-gradient-to-r from-red-600 to-pink-700 text-white px-10 py-4 rounded-full font-bold hover:shadow-xl transition transform hover:scale-105">
                            <i class="fas fa-key mr-3"></i> Change Password
                        </button>
                    </form>
                </div>

                <!-- Full Student Details (Read-only) -->
                <div class="bg-gray-50 rounded-3xl p-8 border-2 border-dashed border-gray-300">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Complete Profile Details</h3>
                    <div class="grid md:grid-cols-2 gap-6 text-gray-700">
                        <div><strong>Full Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']) ?></div>
                        <div><strong>Gender:</strong> <?= ucfirst($student['gender']) ?></div>
                        <div><strong>Date of Birth:</strong> <?= date('d M Y', strtotime($student['birth_date'])) ?></div>
                        <div><strong>Program:</strong> <?= $student['program'] ?></div>
                        <div><strong>Department:</strong> <?= $student['department'] ?></div>
                        <div><strong>Current Year:</strong> <?= $student['current_year'] ?></div>
                        <div><strong>Section:</strong> <?= $student['section'] ?></div>
                        <div><strong>Enrolled On:</strong> <?= date('d M Y', strtotime($student['enrolled_at'])) ?></div>
                        <div><strong>Address:</strong> <?= nl2br(htmlspecialchars($student['address'] ?? 'Not provided')) ?></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>