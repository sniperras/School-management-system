<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = $error = '';

// ==================== APPROVE EXAM ====================
if (isset($_POST['approve_exam'])) {
    $exam_id = (int)$_POST['exam_id'];
    $pdo->prepare("UPDATE exams SET status = 'approved' WHERE id = ?")->execute([$exam_id]);
    $success = "Exam approved!";
}

// ==================== REJECT EXAM ====================
if (isset($_POST['reject_exam'])) {
    $exam_id = (int)$_POST['exam_id'];
    $pdo->prepare("UPDATE exams SET status = 'rejected' WHERE id = ?")->execute([$exam_id]);
    $success = "Exam rejected!";
}

// ==================== PUBLISH RESULT ====================
if (isset($_POST['publish_result'])) {
    $exam_id = (int)$_POST['exam_id'];
    $pdo->prepare("UPDATE exams SET published = 1 WHERE id = ?")->execute([$exam_id]);
    
    // Add to announcements
    $exam = $pdo->prepare("SELECT exam_name, class_name FROM exams WHERE id = ?")->execute([$exam_id]);
    $exam = $pdo->query("SELECT exam_name, class_name FROM exams WHERE id = $exam_id")->fetch();
    $title = "Results Published: " . $exam['exam_name'];
    $message = "Results for <strong>" . $exam['exam_name'] . "</strong> (" . $exam['class_name'] . ") are now available!";
    
    $pdo->prepare("INSERT INTO announcements (title, message, type) VALUES (?, ?, 'result')")
        ->execute([$title, $message]);
    
    $success = "Results published & announced!";
}

// ==================== EDIT MARK (Admin Only) ====================
if (isset($_POST['edit_mark'])) {
    $mark_id = (int)$_POST['mark_id'];
    $marks = (float)$_POST['marks'];
    $pdo->prepare("UPDATE exam_marks SET marks = ? WHERE id = ?")->execute([$marks, $mark_id]);
    $success = "Mark updated!";
}

// Load pending exams
$pending_exams = $pdo->query("
    SELECT e.*, c.class_name, t.first_name, t.last_name 
    FROM exams e 
    JOIN classes c ON e.class_id = c.id 
    JOIN teachers t ON e.created_by = t.id 
    WHERE e.status = 'pending' 
    ORDER BY e.created_at DESC
")->fetchAll();

// Load approved exams
$approved_exams = $pdo->query("
    SELECT e.*, c.class_name 
    FROM exams e 
    JOIN classes c ON e.class_id = c.id 
    WHERE e.status = 'approved' 
    ORDER BY e.exam_date DESC
")->fetchAll();
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Manage Exams & Results | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<header class="bg-deepblue text-white shadow-xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
        <h1 class="text-3xl font-bold">Manage Exams & Results</h1>
        <a href="dashboard.php" class="bg-white text-deepblue px-8 py-3 rounded-lg font-bold hover:bg-gray-100">Back</a>
    </div>
</header>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 py-10">

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8 text-center text-lg font-bold">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <!-- Pending Exam Requests -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Pending Exam Requests</h2>
            <?php if ($pending_exams): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-deepblue text-white">
                            <tr>
                                <th class="px-6 py-4 text-left">Exam Name</th>
                                <th class="px-6 py-4 text-left">Class</th>
                                <th class="px-6 py-4 text-left">Date</th>
                                <th class="px-6 py-4 text-left">Created By</th>
                                <th class="px-6 py-4 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($pending_exams as $e): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($e['exam_name']) ?></td>
                                <td class="px-6 py-4"><?= $e['class_name'] ?></td>
                                <td class="px-6 py-4"><?= date('d M Y', strtotime($e['exam_date'])) ?></td>
                                <td class="px-6 py-4"><?= $e['first_name'] ?> <?= $e['last_name'] ?></td>
                                <td class="px-6 py-4">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                        <button name="approve_exam" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 mr-2">Approve</button>
                                        <button name="reject_exam" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-8">No pending exam requests.</p>
            <?php endif; ?>
        </div>

        <!-- Approved Exams & Results -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-deepblue mb-6">Approved Exams & Results</h2>
            <?php foreach ($approved_exams as $exam): 
                $marks = $pdo->prepare("SELECT em.*, s.student_id, s.first_name, s.last_name FROM exam_marks em JOIN students s ON em.student_id = s.id WHERE em.exam_id = ? ORDER BY s.first_name");
                $marks->execute([$exam['id']]);
                $marks_list = $marks->fetchAll();
            ?>
                <div class="border rounded-xl p-6 mb-8 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-deepblue"><?= htmlspecialchars($exam['exam_name']) ?></h3>
                            <p>Class: <strong><?= $exam['class_name'] ?></strong> | Date: <?= date('d M Y', strtotime($exam['exam_date'])) ?></p>
                            <p class="text-sm text-gray-600">Total Students: <?= count($marks_list) ?> | Published: <?= $exam['published'] ? 'Yes' : 'No' ?></p>
                        </div>
                        <?php if (!$exam['published']): ?>
                            <form method="post">
                                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                                <button name="publish_result" class="bg-red-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-red-700">
                                    Publish Result
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold">Published</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($marks_list): ?>
                        <div class="overflow-x-auto mt-6">
                            <table class="w-full">
                                <thead class="bg-deepblue text-white">
                                    <tr>
                                        <th class="px-4 py-3">Student ID</th>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Marks</th>
                                        <th class="px-4 py-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php foreach ($marks_list as $m): ?>
                                    <tr class="hover:bg-white">
                                        <td class="px-4 py-3 font-mono"><?= $m['student_id'] ?></td>
                                        <td class="px-4 py-3"><?= $m['first_name'] ?> <?= $m['last_name'] ?></td>
                                        <td class="px-4 py-3">
                                            <form method="post" class="inline">
                                                <input type="hidden" name="mark_id" value="<?= $m['id'] ?>">
                                                <input type="number" name="marks" value="<?= $m['marks'] ?>" min="0" max="100" class="w-20 px-2 py-1 border rounded">
                                                <button name="edit_mark" class="text-blue-600 text-sm ml-2">Update</button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">Edited by teacher</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-8">No marks entered yet.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>