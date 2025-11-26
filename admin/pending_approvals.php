<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../../login.php");
    exit;
}

$user_role = current_user_role();
$user_id = $_SESSION['teacher']['id'] ?? $_SESSION['admin']['id'];
$department = $_SESSION['teacher']['department'] ?? null;

$message = '';

// Handle Approval Actions
if (isset($_POST['action']) && isset($_POST['exam_id'])) {
    $exam_id = (int)$_POST['exam_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $comment = trim($_POST['comment'] ?? '');

    // Prevent self-approval
    $check = $pdo->prepare("SELECT created_by, status FROM exams WHERE id = ?");
    $check->execute([$exam_id]);
    $exam = $check->fetch();

    if (!$exam || $exam['created_by'] == $user_id) {
        $message = "<div class='alert alert-danger'>You cannot approve your own exam!</div>";
    } else {
        if ($action === 'approve') {
            if ($user_role === 'teacher') {
                // Department teacher giving first approval
                if ($exam['status'] === 'pending') {
                    $stmt = $pdo->prepare("INSERT INTO exam_approvals (exam_id, teacher_id, approved, comment) VALUES (?, ?, 1, ?)
                                           ON DUPLICATE KEY UPDATE approved = 1, comment = ?");
                    $stmt->execute([$exam_id, $user_id, $comment, $comment]);

                    // Update exam status to dept_approved
                    $pdo->prepare("UPDATE exams SET status = 'dept_approved' WHERE id = ?")->execute([$exam_id]);
                    $message = "<div class='alert alert-success'>Exam sent to Admin for final approval!</div>";
                log_action($pdo, $_SESSION['user_id'] ?? null, "Exam sent to Admin ID {$exam_id}");
                }
            } elseif ($user_role === 'admin') {
                // Admin giving final approval
                if (in_array($exam['status'], ['dept_approved', 'pending'])) {
                    $pdo->prepare("UPDATE exams SET status = 'published', published = 1 WHERE id = ?")->execute([$exam_id]);
                    $message = "<div class='alert alert-success'>Exam Published Successfully!</div>";
                    log_action($pdo, $_SESSION['user_id'] ?? null, "Exam Published Successfully ID {$exam_id}");
                }
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("INSERT INTO exam_approvals (exam_id, teacher_id, approved, comment) VALUES (?, ?, 0, ?)
                                   ON DUPLICATE KEY UPDATE approved = 0, comment = ?");
            $stmt->execute([$exam_id, $user_id, $comment, $comment]);

            $pdo->prepare("UPDATE exams SET status = 'rejected' WHERE id = ?")->execute([$exam_id]);
            $message = "<div class='alert alert-danger'>Exam Rejected!</div>";
            log_action($pdo, $_SESSION['user_id'] ?? null, "Exam Rejected ID {$exam_id}");
        }
    }
}
?>

<?php require_once __DIR__ . '/../../includes/head.php'; ?>
<title>Pending Exam Approvals</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; color: white; }
    .card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: none; border-radius: 20px; }
    .status-pending { background: #ffc107; color: black; }
    .status-dept_approved { background: #17a2b8; }
    .status-published { background: #28a745; }
    .status-rejected { background: #dc3545; }
    .btn-sm i { margin-right: 5px; }
    .modal-content { background: #2c3e50; color: white; border-radius: 15px; }
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold"><i class="fas fa-clipboard-check text-warning"></i> Exam Approval Center</h1>
                <p class="lead">Review and approve exams created by teachers</p>
            </div>

            <?= $message ?>

            <?php
            // Query based on role
            if ($user_role === 'teacher') {
                // Show only exams from same department that are pending or dept_approved (but not by self)
                $stmt = $pdo->prepare("
                    SELECT e.*, c.class_name, c.section, t.name as creator_name,
                           CONCAT(c.class_name, ' - ', c.section) as full_class
                    FROM exams e
                    JOIN classes c ON e.class_name = c.class_name
                    JOIN teachers t ON e.created_by = t.id
                    WHERE t.department = ? 
                      AND e.created_by != ?
                      AND e.status IN ('pending', 'dept_approved')
                    ORDER BY e.created_at DESC
                ");
                $stmt->execute([$department, $user_id]);
            } else { // admin
                $stmt = $pdo->prepare("
                    SELECT e.*, c.class_name, c.section, t.name as creator_name,
                           CONCAT(c.class_name, ' - ', c.section) as full_class
                    FROM exams e
                    JOIN classes c ON e.class_name = c.class_name
                    JOIN teachers t ON e.created_by = t.id
                    WHERE e.status IN ('pending', 'dept_approved')
                    ORDER BY 
                        CASE WHEN e.status = 'dept_approved' THEN 0 ELSE 1 END,
                        e.created_at DESC
                ");
                $stmt->execute();
            }

            $exams = $stmt->fetchAll();
            ?>

            <?php if (empty($exams)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                    <h3>No pending approvals at the moment</h3>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($exams as $exam): ?>
                        <?php
                        $has_approved = $pdo->prepare("SELECT 1 FROM exam_approvals WHERE exam_id = ? AND teacher_id = ? AND approved = 1");
                        $has_approved->execute([$exam['id'], $user_id]);
                        $already_approved = $has_approved->fetch();
                        ?>
                        <div class="col-lg-6">
                            <div class="card p-4 shadow-lg h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="fw-bold text-white"><?= htmlspecialchars($exam['exam_name']) ?></h5>
                                        <p class="mb-1"><strong>Class:</strong> <?= htmlspecialchars($exam['full_class']) ?></p>
                                        <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($exam['exam_type'] ?? 'N/A') ?></p>
                                        <small>Created by: <?= htmlspecialchars($exam['creator_name']) ?> • <?= date('d M Y', strtotime($exam['created_at'])) ?></small>
                                    </div>
                                    <span class="badge fs-6 px-3 py-2 status-<?= $exam['status'] ?>">
                                        <?= ucwords(str_replace('_', ' ', $exam['status'])) ?>
                                    </span>
                                </div>

                                <div class="mt-3">
                                    <a href="view_exam.php?id=<?= $exam['id'] ?>" target="_blank" class="btn btn-outline-light btn-sm">
                                        <i class="fas fa-eye"></i> View PDF
                                    </a>

                                    <?php if (!$already_approved): ?>
                                        <!-- Approve Button -->
                                        <button class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#approveModal<?= $exam['id'] ?>">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <!-- Reject Button -->
                                        <button class="btn btn-danger btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $exam['id'] ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><i class="fas fa-check-circle"></i> You already approved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Approve Modal -->
                        <div class="modal fade" id="approveModal<?= $exam['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-check text-success"></i> Approve Exam</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to approve this exam?</p>
                                            <textarea name="comment" class="form-control bg-dark text-white border-0" placeholder="Optional comment..." rows="3"></textarea>
                                            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Yes, Approve</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?= $exam['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="fas fa-times text-danger"></i> Reject Exam</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Please provide a reason for rejection:</p>
                                            <textarea name="comment" class="form-control bg-dark text-white border-0" placeholder="Reason for rejection..." rows="3" required></textarea>
                                            <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject Exam</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>