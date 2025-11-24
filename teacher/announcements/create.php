<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
if (!is_logged_in() || current_user_role() !== 'teacher') { header("Location: ../../login.php"); exit; }

if ($_POST['submit'] ?? false) {
    $stmt = $pdo->prepare("INSERT INTO announcements (title, message, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$_POST['title'], $_POST['message']]);
    echo "<div class='alert alert-success'>Announcement sent! Visible for 7 days.</div>";
}
?>

<?php require_once __DIR__ . '/../../includes/head.php'; ?>
<title>Send Announcement</title>
<div class="container my-5">
    <h2>Send Notice / Announcement</h2>
    <form method="POST" class="card p-4">
        <div class="mb-3">
            <input type="text" name="title" class="form-control" placeholder="Title" required>
        </div>
        <div class="mb-3">
            <textarea name="message" class="form-control" rows="5" placeholder="Message..." required></textarea>
        </div>
        <button type="submit" name="submit" class="btn btn-danger btn-lg">Send Announcement</button>
    </form>
</div>