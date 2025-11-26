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

// ==================== CREATE CLASS ====================
if (isset($_POST['create_class'])) {
    $class_name = trim($_POST['class_name']);
    $stmt = $pdo->prepare("INSERT INTO classes (class_name) VALUES (?)");
    $stmt->execute([$class_name]);
    $success = "Class created: <strong>$class_name</strong>";
}

// ==================== CREATE SECTION ====================
if (isset($_POST['create_section'])) {
    $class_id = (int)$_POST['class_id'];
    $section_name = strtoupper(trim($_POST['section_name']));
    $stmt = $pdo->prepare("INSERT INTO sections (class_id, section_name) VALUES (?, ?)");
    $stmt->execute([$class_id, $section_name]);
    $success = "Section <strong>$section_name</strong> created!";
}

// ==================== ASSIGN TEACHER TO CLASS+SECTION+SUBJECT ====================
if (isset($_POST['assign_teacher'])) {
    $teacher_id   = (int)$_POST['teacher_id'];
    $section_id   = (int)$_POST['section_id'];
    $subject_name = trim($_POST['subject_name']);

    // Check if already assigned
    $check = $pdo->prepare("SELECT id FROM class_assignments WHERE teacher_id = ? AND section_id = ? AND subject_name = ?");
    $check->execute([$teacher_id, $section_id, $subject_name]);
    if ($check->rowCount() > 0) {
        $error = "This teacher is already assigned to this subject in this section!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO class_assignments (teacher_id, section_id, subject_name) VALUES (?, ?, ?)");
        $stmt->execute([$teacher_id, $section_id, $subject_name]);
        $success = "Teacher assigned successfully!";
    }
    log_action($pdo, $_SESSION['user_id'] ?? null, "assign teacher ID {$teacher_id}");
}

// ==================== DELETE CLASS ====================
if (isset($_POST['delete_class'])) {
    $id = (int)$_POST['class_id'];
    $pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$id]);
    $success = "Class deleted!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "delete class id {$id}");
}

// ==================== DELETE SECTION ====================
if (isset($_POST['delete_section'])) {
    $id = (int)$_POST['section_id'];
    $pdo->prepare("DELETE FROM sections WHERE id = ?")->execute([$id]);
    $success = "Section deleted!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "delete section ID {$id}");
}

// ==================== REMOVE ASSIGNMENT ====================
if (isset($_POST['remove_assignment'])) {
    $id = (int)$_POST['assignment_id'];
    $pdo->prepare("DELETE FROM class_assignments WHERE id = ?")->execute([$id]);
    $success = "Assignment removed!";
    log_action($pdo, $_SESSION['user_id'] ?? null, "remove assignment ID {$id}");
}

// Load data
$classes = $pdo->query("SELECT * FROM classes ORDER BY id")->fetchAll();
$teachers = $pdo->query("SELECT id, teacher_id, first_name, last_name, department FROM teachers ORDER BY first_name")->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Manage Classes & Sections | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Manage Classes & Sections</h1>
            <p class="text-lg opacity-90">Create classes, sections, and assign teachers</p>
        </div>
        <a href="dashboard.php" class="bg-white text-deepblue px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
            Back to Dashboard
        </a>
    </div>
</header>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-10">

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 text-center text-lg font-bold">
                <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-8 text-center font-bold">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-8">

            <!-- Create Class -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-deepblue mb-6">Create New Class</h2>
                <form method="post" class="space-y-6">
                    <input type="text" name="class_name" placeholder="e.g. Year 1, Masters, PhD" required class="w-full px-4 py-3 border rounded-lg">
                    <button name="create_class" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-4 rounded-xl hover:shadow-xl transition">
                        Create Class
                    </button>
                </form>
            </div>

            <!-- Create Section -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-deepblue mb-6">Create Section</h2>
                <form method="post" class="space-y-6">
                    <select name="class_id" required class="w-full px-4 py-3 border rounded-lg">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="section_name" placeholder="e.g. A, B, C" required class="w-full px-4 py-3 border rounded-lg">
                    <button name="create_section" class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white font-bold py-4 rounded-xl hover:shadow-xl transition">
                        Create Section
                    </button>
                </form>
            </div>

            <!-- Assign Teacher -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-deepblue mb-6">Assign Teacher</h2>
                <form method="post" class="space-y-6">
                    <select name="teacher_id" required class="w-full px-4 py-3 border rounded-lg">
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars("{$t['teacher_id']} — {$t['first_name']} {$t['last_name']} ({$t['department']})") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="section_id" required class="w-full px-4 py-3 border rounded-lg">
                        <option value="">Select Section</option>
                        <?php
                        $sections = $pdo->query("SELECT s.id, c.class_name, s.section_name FROM sections s JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, s.section_name")->fetchAll();
                        foreach ($sections as $sec): ?>
                            <option value="<?= $sec['id'] ?>">
                                <?= htmlspecialchars("{$sec['class_name']} - Section {$sec['section_name']}") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="subject_name" placeholder="Subject Name" required class="w-full px-4 py-3 border rounded-lg">
                    <button name="assign_teacher" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white font-bold py-4 rounded-xl hover:shadow-xl transition">
                        Assign Teacher
                    </button>
                </form>
            </div>
        </div>

        <!-- All Classes & Sections -->
        <div class="mt-12 bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-6 border-b bg-gray-50">
                <h2 class="text-2xl font-bold text-deepblue">All Classes, Sections & Teacher Assignments</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach ($classes as $class): 
                    $sections = $pdo->prepare("SELECT * FROM sections WHERE class_id = ? ORDER BY section_name");
                    $sections->execute([$class['id']]);
                    $secs = $sections->fetchAll();
                ?>
                <div class="p-8 hover:bg-gray-50 transition">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-2xl font-bold text-deepblue"><?= htmlspecialchars($class['class_name']) ?></h3>
                        <form method="post" class="inline" onsubmit="return confirm('Delete this class? All sections and assignments will be removed!')">
                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                            <button name="delete_class" class="text-red-600 hover:text-red-800 font-medium">
                                Delete Class
                            </button>
                        </form>
                    </div>

                    <?php if ($secs): ?>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                            <?php foreach ($secs as $sec): 
                                $assignments = $pdo->prepare("
                                    SELECT ca.*, t.teacher_id, t.first_name, t.last_name 
                                    FROM class_assignments ca 
                                    JOIN teachers t ON ca.teacher_id = t.id 
                                    WHERE ca.section_id = ?
                                    ORDER BY ca.subject_name
                                ");
                                $assignments->execute([$sec['id']]);
                                $assigns = $assignments->fetchAll();
                            ?>
                            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-200">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-xl font-bold text-indigo-800">
                                        Section <?= htmlspecialchars($sec['section_name']) ?>
                                    </h4>
                                    <form method="post" class="inline" onsubmit="return confirm('Delete this section?')">
                                        <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">
                                        <button name="delete_section" class="text-red-600 hover:text-red-800 text-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                <?php if ($assigns): ?>
                                    <div class="space-y-3">
                                        <?php foreach ($assigns as $a): ?>
                                            <div class="bg-white rounded-lg p-4 shadow-sm border">
                                                <p class="font-bold text-purple-800"><?= htmlspecialchars($a['subject_name']) ?></p>
                                                <p class="text-sm text-gray-700">
                                                    Teacher: <?= htmlspecialchars("{$a['first_name']} {$a['last_name']} ({$a['teacher_id']})") ?>
                                                </p>
                                                <form method="post" class="mt-2">
                                                    <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                                    <button name="remove_assignment" onclick="return confirm('Remove this assignment?')" 
                                                            class="text-xs text-red-600 hover:text-red-800 underline">
                                                        Remove Assignment
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-gray-500 italic text-sm">No teachers assigned yet</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No sections created yet</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>