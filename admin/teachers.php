<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = $error = '';

// ==================== ADD TEACHER ====================
if (isset($_POST['add_teacher'])) {
    try {
        $first_name    = trim($_POST['first_name']);
        $middle_name   = trim($_POST['middle_name'] ?? '');
        $last_name     = trim($_POST['last_name']);
        $gender        = $_POST['gender'];
        $phone         = trim($_POST['phone']);
        $email         = trim($_POST['email'] ?? '');
        $department    = trim($_POST['department']);
        $qualification = trim($_POST['qualification']);
        $hire_date     = $_POST['hire_date'];

        // CHANGED: Teacher ID → tch/xxxx/xx
        $yearFull  = date('Y');
        $yearShort = date('y'); // 25 for 2025
        $count     = $pdo->query("SELECT COUNT(*) FROM teachers WHERE YEAR(hire_date) = $yearFull")->fetchColumn() + 1;
        $seq       = str_pad((string)$count, 4, '0', STR_PAD_LEFT);
        $teacher_id = "tch/{$seq}/{$yearShort}"; // e.g. tch/0001/25

        $photo = null;
        if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === 0) {
            if ($_FILES['photo']['size'] > 10 * 1024 * 1024) throw new Exception("Photo too large");
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        }

        $stmt = $pdo->prepare("INSERT INTO teachers (teacher_id, first_name, middle_name, last_name, gender, phone, email, department, qualification, photo, hire_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$teacher_id, $first_name, $middle_name, $last_name, $gender, $phone, $email, $department, $qualification, $photo, $hire_date]);

        $success = "Teacher added: <strong>$teacher_id</strong>";
        log_action($pdo, $_SESSION['user_id'] ?? null, "Teacher added ID {$teacher_id}");
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// ==================== EDIT TEACHER ====================
if (isset($_POST['edit_teacher'])) {
    $id = (int)$_POST['teacher_id'];
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department']);
    $qualification = trim($_POST['qualification']);

    if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === 0) {
        $photo = file_get_contents($_FILES['photo']['tmp_name']);
        $stmt = $pdo->prepare("UPDATE teachers SET first_name=?, middle_name=?, last_name=?, phone=?, email=?, department=?, qualification=?, photo=? WHERE id=?");
        $stmt->execute([$first_name, $middle_name, $last_name, $phone, $email, $department, $qualification, $photo, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE teachers SET first_name=?, middle_name=?, last_name=?, phone=?, email=?, department=?, qualification=? WHERE id=?");
        $stmt->execute([$first_name, $middle_name, $last_name, $phone, $email, $department, $qualification, $id]);
    }
    $success = "Teacher updated successfully!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "Teacher updated successfully ID {$id}");
}

// ==================== DELETE TEACHER ====================
if (isset($_POST['delete_teacher'])) {
    $id = (int)$_POST['teacher_id'];
    $pdo->prepare("DELETE FROM teachers WHERE id = ?")->execute([$id]);
    $success = "Teacher deleted!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "Teacher deleted ID {$id}");
}

// ==================== ASSIGN SUBJECT ====================
if (isset($_POST['assign_subject'])) {
    $teacher_id   = (int)$_POST['teacher_id'];
    $subject_name = trim($_POST['subject_name']);
    $class_level  = $_POST['class_level'];

    $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, subject_name, class_level) VALUES (?, ?, ?)");
    $stmt->execute([$teacher_id, $subject_name, $class_level]);
    $success = "Subject assigned!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "Subject assigned ID {$teacher_id}");
}

// ==================== UPDATE SUBJECT ====================
if (isset($_POST['update_subject'])) {
    $sub_id       = (int)$_POST['subject_id'];
    $subject_name = trim($_POST['subject_name']);
    $class_level  = $_POST['class_level'];

    $stmt = $pdo->prepare("UPDATE teacher_subjects SET subject_name = ?, class_level = ? WHERE id = ?");
    $stmt->execute([$subject_name, $class_level, $sub_id]);
    $success = "Subject updated!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "Subject updated ID {$sub_id}");
}

// ==================== DELETE SUBJECT ====================
if (isset($_POST['delete_subject'])) {
    $sub_id = (int)$_POST['subject_id'];
    $pdo->prepare("DELETE FROM teacher_subjects WHERE id = ?")->execute([$sub_id]);
    $success = "Subject removed!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "Subject removed ID {$sub_id}");
}

// Load all teachers
$search = trim($_GET['search'] ?? '');
$where = $params = [];
if ($search) {
    $where[] = "(teacher_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM teachers $whereClause ORDER BY hire_date DESC");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$stats = $pdo->query("SELECT COUNT(*) as total FROM teachers")->fetch();
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Manage Teachers | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Header -->
<header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Manage Teachers</h1>
            <p class="text-lg opacity-90">Total: <strong><?= $stats['total'] ?></strong> teachers</p>
        </div>
        <a href="dashboard.php" class="bg-white text-deepblue px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
            Back to Dashboard
        </a>
    </div>
</header>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 text-center text-lg font-bold">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- Add Teacher Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Add New Teacher</h2>
            <form method="post" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-6">
                <input type="text" name="first_name" placeholder="First Name" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="middle_name" placeholder="Middle Name (optional)" class="px-4 py-3 border rounded-lg">
                <input type="text" name="last_name" placeholder="Last Name" required class="px-4 py-3 border rounded-lg">
                <select name="gender" required class="px-4 py-3 border rounded-lg">
                    <option value="">Gender</option>
                    <option>Male</option>
                    <option>Female</option>
                </select>
                <input type="text" name="phone" placeholder="Phone Number" required class="px-4 py-3 border rounded-lg">
                <input type="email" name="email" placeholder="Email" class="px-4 py-3 border rounded-lg">
                <input type="text" name="department" placeholder="Department" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="qualification" placeholder="Qualification" required class="px-4 py-3 border rounded-lg">
                <input type="date" name="hire_date" value="<?= date('Y-m-d') ?>" required class="px-4 py-3 border rounded-lg">
                <div class="md:col-span-2">
                    <label class="block font-bold mb-2">Photo (Optional)</label>
                    <input type="file" name="photo" accept="image/*" class="block w-full text-sm file:mr-4 file:py-3 file:px-6 file:rounded file:bg-blue-50 file:text-blue-700">
                </div>
                <div class="md:col-span-2 text-center">
                    <button name="add_teacher" class="bg-gradient-to-r from-green-600 to-green-700 text-white font-bold text-xl px-16 py-5 rounded-xl hover:shadow-2xl transition">
                        Add Teacher
                    </button>
                </div>
            </form>
        </div>

        <!-- Assign Subject Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Assign Subject to Teacher</h2>
            <form method="post" class="grid md:grid-cols-4 gap-6 max-w-5xl">
                <select name="teacher_id" required class="px-4 py-3 border rounded-lg text-lg">
                    <option value="">Select Teacher</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars("{$t['teacher_id']} — {$t['first_name']} {$t['last_name']}") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="subject_name" placeholder="Subject Name" required class="px-4 py-3 border rounded-lg">
                <select name="class_level" required class="px-4 py-3 border rounded-lg">
                    <option value="">Level</option>
                    <option>Year 1</option><option>Year 2</option><option>Year 3</option><option>Year 4</option><option>Masters</option><option>PhD</option>
                </select>
                <button name="assign_subject" class="bg-gradient-to-r from-purple-600 to-purple-700 text-white font-bold px-12 py-4 rounded-xl hover:shadow-xl transition">
                    Assign Subject
                </button>
            </form>
        </div>

        <!-- Teachers Table -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b bg-gray-50">
                <h2 class="text-2xl font-bold text-deepblue">All Teachers & Assigned Subjects</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-deepblue text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Photo</th>
                            <th class="px-6 py-4 text-left">Teacher ID</th>
                            <th class="px-6 py-4 text-left">Name</th>
                            <th class="px-6 py-4 text-left">Department</th>
                            <th class="px-6 py-4 text-left">Assigned Subjects</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($teachers as $t): 
                            $subs = $pdo->prepare("SELECT id, subject_name, class_level FROM teacher_subjects WHERE teacher_id = ? ORDER BY subject_name");
                            $subs->execute([$t['id']]);
                            $subjects = $subs->fetchAll();
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-5">
                                <?php if ($t['photo']): ?>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($t['photo']) ?>" class="w-14 h-14 rounded-full object-cover border-2 border-gray-300">
                                <?php else: ?>
                                    <div class="w-14 h-14 bg-gray-300 rounded-full flex items-center justify-center text-xl font-bold text-gray-600">
                                        <?= substr($t['first_name'], 0, 1) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 font-mono font-bold text-deepblue"><?= htmlspecialchars($t['teacher_id']) ?></td>
                            <td class="px-6 py-5 font-semibold">
                                <?= htmlspecialchars($t['first_name'] . ' ' . ($t['middle_name'] ? $t['middle_name'].' ' : '') . $t['last_name']) ?>
                                <br><small class="text-gray-600"><?= htmlspecialchars($t['qualification']) ?></small>
                            </td>
                            <td class="px-6 py-5"><?= htmlspecialchars($t['department']) ?></td>
                            <td class="px-6 py-5">
                                <?php if ($subjects): ?>
                                    <div class="space-y-2">
                                        <?php foreach ($subjects as $s): ?>
                                            <div class="flex items-center justify-between bg-purple-50 px-4 py-2 rounded-lg">
                                                <div>
                                                    <span class="font-medium text-purple-900"><?= htmlspecialchars($s['subject_name']) ?></span>
                                                    <span class="text-xs text-purple-700 ml-2">— <?= $s['class_level'] ?></span>
                                                </div>
                                                <div class="flex gap-2">
                                                    <button onclick='openSubjectModal(<?= $s['id'] ?>, "<?= addslashes($s['subject_name']) ?>", "<?= $s['class_level'] ?>")' 
                                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</button>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
                                                        <button name="delete_subject" onclick="return confirm('Remove this subject assignment?')" 
                                                                class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-500 italic">No subjects assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-center space-x-3">
                                <button onclick="openEditModal(<?= $t['id'] ?>, '<?= addslashes($t['first_name']) ?>', '<?= addslashes($t['middle_name'] ?? '') ?>', '<?= addslashes($t['last_name']) ?>', '<?= $t['phone'] ?>', '<?= $t['email'] ?>', '<?= addslashes($t['department']) ?>', '<?= addslashes($t['qualification']) ?>')" 
                                        class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm transition">
                                    Edit Teacher
                                </button>
                                <form method="post" class="inline" onsubmit="return confirm('Delete this teacher permanently? All subjects will be removed.')">
                                    <input type="hidden" name="teacher_id" value="<?= $t['id'] ?>">
                                    <button name="delete_teacher" class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 text-sm transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-2xl max-h-screen overflow-y-auto">
        <h2 class="text-2xl font-bold text-deepblue mb-6">Edit Teacher</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="teacher_id" id="edit_id">
            <div class="grid md:grid-cols-2 gap-6">
                <input type="text" name="first_name" id="edit_first" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="middle_name" id="edit_middle" class="px-4 py-3 border rounded-lg">
                <input type="text" name="last_name" id="edit_last" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="phone" id="edit_phone" required class="px-4 py-3 border rounded-lg">
                <input type="email" name="email" id="edit_email" class="px-4 py-3 border rounded-lg">
                <input type="text" name="department" id="edit_dept" required class="px-4 py-3 border rounded-lg">
                <input type="text" name="qualification" id="edit_qual" required class="px-4 py-3 border rounded-lg">
                <div class="md:col-span-2">
                    <label class="block font-bold mb-2">Update Photo (leave empty to keep current)</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
            </div>
            <div class="mt-8 flex justify-end gap-4">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" 
                        class="px-8 py-3 bg-gray-300 rounded-lg hover:bg-gray-400 transition font-bold">
                    Cancel
                </button>
                <button name="edit_teacher" class="px-10 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:shadow-xl transition font-bold">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Subject Modal -->
<div id="subjectModal" class="fixed inset-0 bg-black bg-opacity-60 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <h3 class="text-xl font-bold text-deepblue mb-6">Edit Assigned Subject</h3>
        <form method="post">
            <input type="hidden" name="subject_id" id="sub_id">
            <div class="space-y-5">
                <input type="text" name="subject_name" id="sub_name" required placeholder="Subject Name" class="w-full px-4 py-3 border rounded-lg">
                <select name="class_level" id="sub_level" required class="w-full px-4 py-3 border rounded-lg">
                    <option value="Year 1">Year 1</option>
                    <option value="Year 2">Year 2</option>
                    <option value="Year 3">Year 3</option>
                    <option value="Year 4">Year 4</option>
                    <option value="Masters">Masters</option>
                    <option value="PhD">PhD</option>
                </select>
            </div>
            <div class="mt-8 flex justify-end gap-4">
                <button type="button" onclick="document.getElementById('subjectModal').classList.add('hidden')" 
                        class="px-8 py-3 bg-gray-300 rounded-lg hover:bg-gray-400 transition font-bold">
                    Cancel
                </button>
                <button name="update_subject" class="px-10 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:shadow-xl transition font-bold">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, first, middle, last, phone, email, dept, qual) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_first').value = first;
    document.getElementById('edit_middle').value = middle;
    document.getElementById('edit_last').value = last;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_dept').value = dept;
    document.getElementById('edit_qual').value = qual;
    document.getElementById('editModal').classList.remove('hidden');
}

function openSubjectModal(id, name, level) {
    document.getElementById('sub_id').value = id;
    document.getElementById('sub_name').value = name;
    document.getElementById('sub_level').value = level;
    document.getElementById('subjectModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>