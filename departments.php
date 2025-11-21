<?php
// departments.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$dataPath = __DIR__ . '/data/departments.json';
$departments = read_json($dataPath, []);

$errors = [];

// Handle add department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_department') {
    // Only allow admins to add
    if (!is_logged_in() || current_user_role() !== 'admin') {
        $errors[] = 'You must be logged in as admin to add a department.';
    } else {
        $token = $_POST['csrf'] ?? '';
        if (!check_csrf($token)) {
            $errors[] = 'Invalid CSRF token.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($name === '') $errors[] = 'Department name is required.';
            if ($description === '') $errors[] = 'Description is required.';

            if (empty($errors)) {
                $item = [
                    'id' => bin2hex(random_bytes(6)),
                    'name' => $name,
                    'description' => $description,
                    'created_at' => date('c'),
                ];
                array_unshift($departments, $item);
                if (!write_json($dataPath, $departments)) {
                    $errors[] = 'Failed to save department. Check file permissions.';
                } else {
                    header('Location: departments.php');
                    exit;
                }
            }
        }
    }
}
?>
<section class="space-y-6">
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-2">Departments</h2>

    <?php if (empty($departments)): ?>
      <p class="text-gray-600">No departments yet.</p>
    <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($departments as $d): ?>
          <article class="p-4 border rounded">
            <h3 class="text-xl font-semibold"><?php echo e($d['name']); ?></h3>
            <p class="text-sm text-gray-600">
              Created on <?php echo e(date('F j, Y', strtotime($d['created_at']))); ?>
            </p>
            <div class="mt-2 text-gray-700"><?php echo nl2br(e($d['description'])); ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if (is_logged_in() && current_user_role() === 'admin'): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-bold mb-2">Add Department</h3>

      <?php if ($errors): ?>
        <div class="mb-3 text-sm text-red-700">
          <?php foreach ($errors as $err) echo '<div>'.e($err).'</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add_department">

        <label class="block mb-2 text-sm">Department Name
          <input name="name" class="w-full border rounded px-2 py-1 mt-1" required>
        </label>

        <label class="block mb-2 text-sm">Description
          <textarea name="description" rows="4" class="w-full border rounded px-2 py-1 mt-1" required></textarea>
        </label>

        <button class="bg-blue-600 text-white px-4 py-2 rounded-md">Add Department</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
