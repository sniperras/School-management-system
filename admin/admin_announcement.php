<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php"); exit;
}

$feedback = '';
$total_posts = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();

if (isset($_POST['submit'])) {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type    = $_POST['type'] ?? 'general';

    if (empty($title) || empty($content)) {
        $feedback = '<div class="bg-red-100 border border-red-400 text-red-800 p-6 rounded-xl text-center font-bold">Please fill Title and Message!</div>';
    } else {
        $image = null;
        $image_type = null;

        if ($type !== 'exam' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['image']['size'] <= 5000000 && in_array($_FILES['image']['type'], ['image/jpeg','image/jpg','image/png','image/gif'])) {
                $image = file_get_contents($_FILES['image']['tmp_name']);
                $image_type = $_FILES['image']['type'];
            } else {
                $feedback = '<div class="bg-red-100 border border-red-400 text-red-800 p-6 rounded-xl text-center font-bold">Invalid image!</div>';
            }
        }

        if (!$feedback) {
            try {
                $stmt = $pdo->prepare("INSERT INTO news (title, message, type, image, image_type, created_by, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW())");
                // created_by = admin's user_id (safe now — no foreign key!)
                $stmt->execute([$title, $content, $type, $image, $image_type, $_SESSION['user_id'] ?? null]);

                $feedback = '<div class="bg-green-100 border-4 border-green-500 text-green-800 p-10 rounded-2xl text-center font-bold text-4xl shadow-2xl">
                                <i class="fas fa-check-circle text-8xl mb-4 block text-green-600"></i>
                                Published Successfully!
                             </div>';
                $_POST = [];
            } catch (Exception $e) {
                $feedback = '<div class="bg-red-100 border border-red-400 text-red-800 p-6 rounded-xl text-center font-bold">
                                Error: ' . htmlspecialchars($e->getMessage()) . '
                             </div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Announcement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
    </script>
</head>
<body class="bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

    <header class="bg-deepblue text-white shadow-2xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <h1 class="text-3xl font-extrabold">Create Post</h1>
                <span class="bg-lightblue/20 px-6 py-3 rounded-full text-lg font-bold">
                    Total: <strong class="text-2xl"><?= $total_posts ?></strong> post<?= $total_posts != 1 ? 's' : '' ?>
                </span>
            </div>
            <a href="dashboard.php" class="bg-lightblue text-deepblue px-8 py-4 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-lg">
                Back to Dashboard
            </a>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-16">
        <div class="bg-white rounded-3xl shadow-3xl p-12">

            <?php if ($feedback): ?>
                <?= $feedback ?>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-10">
                <div>
                    <label class="block text-2xl font-bold text-deepblue mb-4">Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required 
                           class="w-full p-6 border-2 border-midblue rounded-2xl text-xl focus:border-deepblue focus:outline-none transition" 
                           placeholder="e.g. Mid-Term Break">
                </div>

                <div>
                    <label class="block text-2xl font-bold text-deepblue mb-4">Message</label>
                    <textarea name="content" rows="12" required 
                              class="w-full p-6 border-2 border-midblue rounded-2xl text-xl focus:border-deepblue focus:outline-none transition resize-none" 
                              placeholder="Write your announcement..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-2xl font-bold text-deepblue mb-4">Type</label>
                    <select name="type" id="typeSelect" onchange="toggleImage()" 
                            class="w-full p-6 border-2 border-midblue rounded-2xl text-xl focus:border-deepblue focus:outline-none transition">
                        <option value="general" <?= ($_POST['type'] ?? 'general') === 'general' ? 'selected' : '' ?>>General Notice</option>
                        <option value="event" <?= ($_POST['type'] ?? '') === 'event' ? 'selected' : '' ?>>School Event</option>
                        <option value="notice" <?= ($_POST['type'] ?? '') === 'notice' ? 'selected' : '' ?>>Urgent Notice</option>
                        <option value="exam" <?= ($_POST['type'] ?? '') === 'exam' ? 'selected' : '' ?>>Published Exam (Auto Image)</option>
                    </select>
                </div>

                <div id="imageUpload" class="space-y-4">
                    <label class="block text-2xl font-bold text-deepblue">Upload Image (Optional)</label>
                    <input type="file" name="image" accept="image/*" 
                           class="w-full p-6 border-2 border-dashed border-midblue rounded-2xl file:mr-6 file:py-4 file:px-10 file:rounded-xl file:border-0 file:bg-lightblue file:text-deepblue file:font-bold hover:file:bg-midblue hover:file:text-white text-lg">
                    <p class="text-gray-600 text-lg">Max 5MB • JPG, PNG, GIF only</p>
                </div>

                <div class="text-center pt-12">
                    <button type="submit" name="submit" 
                            class="bg-gradient-to-r from-deepblue to-midblue text-white px-32 py-8 rounded-2xl text-3xl font-extrabold hover:shadow-2xl transform hover:scale-105 transition duration-300 shadow-2xl">
                        PUBLISH ANNOUNCEMENT
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleImage() {
            document.getElementById('imageUpload').style.display = 
                document.getElementById('typeSelect').value === 'exam' ? 'none' : 'block';
        }
        toggleImage();
    </script>
</body>
</html>