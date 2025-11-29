<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'teacher') {
    header("Location: ../../login.php");
    exit;
}

$teacher_id   = $_SESSION['teacher']['id'];
$teacher_name = trim(($_SESSION['teacher']['first_name'] ?? '') . ' ' . ($_SESSION['teacher']['middle_name'] ?? '') . ' ' . ($_SESSION['teacher']['last_name'] ?? ''));
$message = '';

// Handle form submission
if (isset($_POST['submit'])) {
    $title       = trim($_POST['title'] ?? '');
    $type        = $_POST['type'] ?? 'general';
    $messageText = trim($_POST['message'] ?? '');
    $class       = trim($_POST['class'] ?? '');
    $date        = $_POST['exam_date'] ?? '';
    $room        = trim($_POST['room'] ?? '');
    $duration    = trim($_POST['duration'] ?? '');
    $weight      = trim($_POST['weight'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    $image_blob  = null;
    $image_type  = null;

    // Handle image upload → store as BLOB
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type']; // e.g. image/jpeg

        // Validate: only images, max 5MB
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if ($file_size <= 5 * 1024 * 1024 && in_array($file_type, $allowed_types)) {
            $image_blob = file_get_contents($file_tmp);
            $image_type = $file_type;
        }
    }

    if ($title && $messageText) {
        $stmt = $pdo->prepare("
            INSERT INTO news 
            (title, message, type, image, image_type, created_at, created_by) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $title,
            $messageText,
            $type,
            $image_blob,      // BLOB data
            $image_type,      // MIME type
            $teacher_id
        ]);

        $message = "<div class='bg-green-100 border-4 border-green-500 text-green-800 p-10 rounded-3xl text-center shadow-2xl'>
            <i class='fas fa-check-circle text-8xl mb-4'></i><br>
            <strong class='text-3xl'>Announcement Published Successfully!</strong>
            <p class='mt-4 text-xl'>Visible to students for 7 days</p>
        </div>";
    } else {
        $message = "<div class='bg-red-100 text-red-800 p-6 rounded-xl'>Please fill title and message.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Announcement | Teacher</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</head>
<body class="font-sans bg-gradient-to-br from-cream to-lightblue min-h-screen">

  <!-- Header -->
  <header class="bg-deepblue text-white shadow-2xl">
    <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
      <div class="flex items-center gap-5">
        <img src="../../img/school-logo.png" alt="Logo" class="h-14 w-14 rounded-full border-4 border-white">
        <div>
          <h1 class="text-3xl font-extrabold">Create Announcement</h1>
          <p>Beautiful cards • Images stored in database</p>
        </div>
      </div>
      <div class="flex items-center gap-6">
        <span class="hidden md:block font-medium"><?= htmlspecialchars($teacher_name) ?></span>
        <a href="../teacher_dashboard.php" class="bg-white text-deepblue px-8 py-4 rounded-xl font-bold hover:bg-lightblue transition shadow-lg">
          Dashboard
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-12">
    <?= $message ?>

    <div class="grid lg:grid-cols-2 gap-12" data-aos="fade-up">

      <!-- Form -->
      <div class="bg-white rounded-3xl shadow-2xl p-10">
        <h2 class="text-3xl font-bold text-deepblue mb-8">Fill Details</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-8">
          <div>
            <label class="block text-xl font-bold mb-3">Title</label>
            <input type="text" name="title" required class="w-full px-6 py-4 border-2 border-midblue rounded-2xl text-lg" placeholder="e.g. Final Exam Published!" oninput="updatePreview()">
          </div>

          <div>
            <label class="block text-xl font-bold mb-3">Type</label>
            <select name="type" class="w-full px-6 py-4 border-2 border-midblue rounded-2xl text-lg" onchange="toggleExamFields(); updatePreview()">
              <option value="general">General Notice</option>
              <option value="exam">Exam Schedule</option>
              <option value="event">School Event</option>
              <option value="holiday">Holiday</option>
              <option value="urgent">Urgent Alert</option>
            </select>
          </div>

          <!-- Exam Fields -->
          <div id="examFields" class="space-y-6 hidden">
            <input type="text" name="class" placeholder="Class (e.g. Year 2 - A)" class="w-full px-6 py-4 border-2 rounded-2xl" oninput="updatePreview()">
            <input type="date" name="exam_date" class="w-full px-6 py-4 border-2 rounded-2xl" oninput="updatePreview()">
            <input type="text" name="room" placeholder="Room" class="w-full px-6 py-4 border-2 rounded-2xl" oninput="updatePreview()">
            <input type="text" name="duration" placeholder="Duration" class="w-full px-6 py-4 border-2 rounded-2xl" oninput="updatePreview()">
            <input type="text" name="weight" placeholder="Weight" class="w-full px-6 py-4 border-2 rounded-2xl" oninput="updatePreview()">
            <textarea name="instructions" rows="3" placeholder="Instructions" class="w-full px-6 py-4 border-2 rounded-2xl" oninput="updatePreview()"></textarea>
          </div>

          <div>
            <label class="block text-xl font-bold mb-3">Short Message</label>
            <textarea name="message" rows="4" required class="w-full px-6 py-4 border-2 border-midblue rounded-2xl" placeholder="Brief description..." oninput="updatePreview()"></textarea>
          </div>

          <div>
            <label class="block text-xl font-bold mb-3">Attach Image (Optional)</label>
            <input type="file" name="image" accept="image/*" class="w-full text-lg file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:bg-deepblue file:text-white" onchange="previewImage(this)">
          </div>

          <div class="text-center pt-6">
            <button type="submit" name="submit" class="bg-gradient-to-r from-red-600 to-pink-600 text-white px-16 py-6 rounded-2xl text-2xl font-bold hover:from-red-700 hover:to-pink-700 transition shadow-2xl">
              Publish Announcement
            </button>
          </div>
        </form>
      </div>

      <!-- Live Preview -->
      <div class="bg-white rounded-3xl shadow-2xl p-10 sticky top-6">
        <h3 class="text-3xl font-bold text-deepblue mb-8 text-center">Live Preview</h3>
        <div id="preview" class="bg-gray-50 rounded-2xl p-8 min-h-96 border-2 border-dashed border-gray-300">
          <div class="text-center text-gray-500">
            <i class="fas fa-bell text-6xl mb-4"></i>
            <p class="text-xl">Start typing to see your announcement...</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    AOS.init();

    function toggleExamFields() {
      const type = document.querySelector('[name="type"]').value;
      document.getElementById('examFields').classList.toggle('hidden', type !== 'exam');
      updatePreview();
    }

    function previewImage(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'w-full rounded-xl mb-6 max-h-64 object-cover shadow-lg';
          const prev = document.querySelector('#preview img');
          if (prev) prev.remove();
          document.getElementById('preview').prepend(img);
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function updatePreview() {
      const title = document.querySelector('[name="title"]').value.trim() || 'Untitled';
      const type = document.querySelector('[name="type"]').value;
      const message = document.querySelector('[name="message"]').value.trim() || 'No message...';
      const cls = document.querySelector('[name="class"]').value;
      const date = document.querySelector('[name="exam_date"]').value;
      const room = document.querySelector('[name="room"]').value;
      const duration = document.querySelector('[name="duration"]').value;
      const weight = document.querySelector('[name="weight"]').value;
      const instructions = document.querySelector('[name="instructions"]').value;

      let card = '';

      if (type === 'exam' && cls && date) {
        card = `
          <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-l-8 border-green-600 p-8 rounded-r-3xl shadow-xl">
            <h3 class="text-3xl font-bold text-green-800 mb-6">Exam Published!</h3>
            <ul class="space-y-3 text-lg text-gray-800">
              <li><strong>Title:</strong> ${title}</li>
              <li><strong>Class:</strong> ${cls}</li>
              <li><strong>Date:</strong> ${date ? new Date(date).toLocaleDateString('en-GB', {day:'numeric', month:'long', year:'numeric'}) : ''}</li>
              <li><strong>Room:</strong> <span class="font-bold text-green-700">${room || 'TBA'}</span></li>
              <li><strong>Duration:</strong> ${duration || 'N/A'}</li>
              <li><strong>Weight:</strong> ${weight || 'N/A'}</li>
              ${instructions ? `<li><strong>Instructions:</strong> ${instructions}</li>` : ''}
            </ul>
            <p class="mt-6 text-blue-700 font-medium">Download from Student Portal → Exams</p>
          </div>`;
      } else {
        const colors = { general: 'blue', exam: 'green', event: 'purple', holiday: 'orange', urgent: 'red' };
        const bg = colors[type] || 'gray';
        card = `
          <div class="bg-gradient-to-br from-${bg}-50 to-${bg}-100 border-l-8 border-${bg}-600 p-8 rounded-r-3xl shadow-xl">
            <h3 class="text-3xl font-bold text-${bg}-800 mb-4">${title}</h3>
            <p class="text-lg text-gray-800 leading-relaxed">${message.replace(/\n/g, '<br>')}</p>
            <p class="mt-6 text-sm text-${bg}-600">Published today</p>
          </div>`;
      }

      document.getElementById('preview').innerHTML = card;
    }

    toggleExamFields();
    updatePreview();
    document.querySelectorAll('input, textarea, select').forEach(el => el.addEventListener('input', updatePreview));
  </script>
</body>
</html>