<?php

declare(strict_types=1);
// At the top of every file in admin/
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admin can access
if (!is_logged_in() || current_user_role() !== 'admin') {
    header('Location: login.php');
    exit;
}

$success = $error = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== current_user_id()) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $success = "User deleted successfully.";
        log_action($pdo, $_SESSION['user_id'] ?? null, "User deleted successfully ID {$id}");
    } else {
        $error = "You cannot delete your own account!";
    }
}

// Search & filter
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';

$sql = "SELECT id, name, username, email, phone, role, student_id, created_at FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR username LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params = array_fill(0, 4, $like);
}

if ($role_filter !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin Panel</title>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .text-deepblue {
            color: #1e40af;
        }

        .bg-deepblue {
            background-color: #1e40af;
        }

        .text-midblue {
            color: #3b82f6;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- ADMIN NAVIGATION BAR (built-in, no external file needed) -->
    <nav class="bg-gradient-to-r from-deepblue to-indigo-800 text-deepblue shadow-2xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <div class="hidden2 flex items-center gap-8">
                <h1 class="text-3xl font-bold">School Admin</h1>
            </div>
            <div class="justify-between flex">
                <div class=" md:flex gap-8 text-lg mx-8" style="margin-right: 50px">
                    <a href="dashboard.php" class="hover:text-yellow-300 transition">Dashboard</a>
                    <a href="/../sms/logout.php" class="hover:text-red-400 transition">Logout</a>
                </div>
                <div class="text-right">
                    <p class="text-sm opacity-90">Welcome, <strong>Admin</strong></p>
                    <p class="text-xs">System Administrator</p>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="bg-white rounded-3xl shadow-xl p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
                <div>
                    <h1 class="text-4xl font-bold text-deepblue">Manage Users</h1>
                    <p class="text-gray-600 mt-2">Control all students, teachers, parents & admin accounts</p>
                </div>
                <a href="admin_add_user.php" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold px-8 py-4 rounded-2xl hover:scale-105 transition shadow-lg flex items-center gap-3">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
            </div>

            <!-- Success / Error Messages -->
            <?php if ($success): ?>
                <div class="mb-6 p-5 bg-green-100 border-2 border-green-300 text-green-800 rounded-2xl flex items-center gap-3">
                    <i class="fas fa-check-circle text-2xl"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-6 p-5 bg-red-100 border-2 border-red-300 text-red-800 rounded-2xl flex items-center gap-3">
                    <i class="fas fa-times-circle text-2xl"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <form method="get" class="mb-8 grid md:grid-cols-3 gap-6">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by name, ID, email, username..."
                    class="px-6 py-4 border-2 rounded-2xl focus:border-midblue transition text-lg">
                <select name="role" class="px-6 py-4 border-2 rounded-2xl focus:border-midblue transition text-lg">
                    <option value="all">All Roles</option>
                    <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Students</option>
                    <option value="teacher" <?= $role_filter === 'teacher' ? 'selected' : '' ?>>Teachers</option>
                    <option value="parent" <?= $role_filter === 'parent' ? 'selected' : '' ?>>Parents</option>
                    <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                </select>
                <div class="flex gap-4">
                    <button type="submit" class="bg-deepblue text-white px-10 py-4 rounded-2xl hover:scale-105 transition font-bold">
                        Search
                    </button>
                    <a href="admin_users.php" class="bg-gray-300 text-gray-800 px-8 py-4 rounded-2xl hover:bg-gray-400 transition font-bold">
                        Clear
                    </a>
                </div>
            </form>

            <!-- Users Table -->
            <div class="overflow-x-auto rounded-2xl border-2 border-gray-200">
                <table class="w-full text-left">
                    <thead class="bg-gradient-to-r from-deepblue to-midblue text-white">
                        <tr>
                            <th class="px-6 py-5 rounded-tl-2xl">Name</th>
                            <th class="px-6 py-5">Username</th>
                            <th class="px-6 py-5">ID</th>
                            <th class="px-6 py-5">Role</th>
                            <th class="px-6 py-5">Contact</th>
                            <th class="px-6 py-5">Joined</th>
                            <th class="px-6 py-5 rounded-tr-2xl text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-5 font-semibold"><?= htmlspecialchars($user['name']) ?></td>
                                <td class="px-6 py-5 font-mono text-midblue"><?= htmlspecialchars($user['username']) ?></td>
                                <td class="px-6 py-5 font-mono text-purple-600">
                                    <?= $user['student_id'] ? htmlspecialchars($user['student_id']) : '—' ?>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="px-4 py-2 rounded-full text-sm font-bold
                                    <?= $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : '' ?>
                                    <?= $user['role'] === 'teacher' ? 'bg-blue-100 text-blue-800' : '' ?>
                                    <?= $user['role'] === 'student' ? 'bg-green-100 text-green-800' : '' ?>
                                    <?= $user['role'] === 'parent' ? 'bg-purple-100 text-purple-800' : '' ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-sm">
                                    <?= htmlspecialchars($user['email'] ?: '—') ?><br>
                                    <span class="text-gray-600"><?= htmlspecialchars($user['phone']) ?></span>
                                </td>
                                <td class="px-6 py-5 text-sm text-gray-600">
                                    <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <a href="admin_edit_user.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-800 text-xl" title="Edit">
                                        Edit
                                    </a>
                                    <a href="?delete=<?= $user['id'] ?>"
                                        onclick="return confirm('Delete <?= addslashes(htmlspecialchars($user['name'])) ?> permanently?')"
                                        class="text-red-600 hover:text-red-800 text-xl ml-4" title="Delete">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-16 text-gray-500 text-2xl">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-10 text-center text-gray-600">
                Total Users: <strong><?= count($users) ?></strong>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
</body>

</html>