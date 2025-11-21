<?php
// announcements.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$dataPath = __DIR__ . '/data/announcements.json';
$announcements = read_json($dataPath, []);

$errors = [];

// Handle add announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_announcement') {
    // Only allow admins to publish
    if (!is_logged_in() || current_user_role() !== 'admin') {
        $errors[] = 'You must be logged in as admin to publish an announcement.';
    } else {
        $token = $_POST['csrf'] ?? '';
        if (!check_csrf($token)) {
            $errors[] = 'Invalid CSRF token.';
        } else {
            $title = trim($_POST['title'] ?? '');
            $body = trim($_POST['body'] ?? '');
            $author = trim($_POST['author'] ?? 'Admin');

            if ($title === '') $errors[] = 'Title is required.';
            if ($body === '') $errors[] = 'Announcement body is required.';

            if (empty($errors)) {
                $item = [
                    'id' => bin2hex(random_bytes(6)),
                    'title' => $title,
                    'body' => $body,
                    'author' => $author,
                    'created_at' => date('c'),
                ];
                array_unshift($announcements, $item);
                if (!write_json($dataPath, $announcements)) {
                    $errors[] = 'Failed to save announcement. Check file permissions.';
                } else {
                    header('Location: announcements.php');
                    exit;
                }
            }
        }
    }
}
?>
<section class="space-y-6">
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-2">Campus Announcements</h2>

    <?php if (empty($announcements)): ?>
      <p class="text-gray-600">No announcements yet.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($announcements as $a): ?>
          <article class="p-4 border rounded">
            <h3 class="text-xl font-semibold"><?php echo e($a['title']); ?></h3>
            <p class="text-sm text-gray-600">
              By <?php echo e($a['author']); ?> · <?php echo e(date('F j, Y', strtotime($a['created_at']))); ?>
            </p>
            <div class="mt-2 text-gray-700"><?php echo nl2br(e($a['body'])); ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if (is_logged_in() && current_user_role() === 'admin'): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-bold mb-2">Publish Announcement</h3>

      <?php if ($errors): ?>
        <div class="mb-3 text-sm text-red-700">
          <?php foreach ($errors as $err) echo '<div>'.e($err).'</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add_announcement">

        <label class="block mb-2 text-sm">Title
          <input name="title" class="w-full border rounded px-2 py-1 mt-1" required>
        </label>

        <label class="block mb-2 text-sm">Author
          <input name="author" class="w-full border rounded px-2 py-1 mt-1" value="Admin">
        </label>

        <label class="block mb-2 text-sm">Body
          <textarea name="body" rows="6" class="w-full border rounded px-2 py-1 mt-1" required></textarea>
        </label>

        <button class="bg-blue-600 text-white px-4 py-2 rounded-md">Publish</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
