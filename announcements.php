<?php
// announcements.php
require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/nav.php'; // navbar

function safe_date($dt) {
    $ts = strtotime($dt);
    return $ts ? date('F j, Y', $ts) : '';
}

$items = [];

// News
try {
    $stmtNews = $pdo->query("SELECT id, title, message, type, image, image_type, created_at FROM news ORDER BY created_at DESC LIMIT 50");
    foreach ($stmtNews as $row) {
        $row['source'] = 'news';
        $row['exam_id'] = null;
        $items[] = $row;
    }
} catch (Exception $e) {
    echo '<pre style="color:red;">News SQL Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}

// Exams
try {
    $stmtExams = $pdo->query("SELECT id, exam_name, exam_date, COALESCE(published_at, created_at) AS created_at FROM exams WHERE status='published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 50");
    foreach ($stmtExams as $exam) {
        $items[] = [
            'source'     => 'exam',
            'id'         => $exam['id'],
            'title'      => $exam['exam_name'],
            'message'    => '<p class="text-green-600 font-bold">Exam on ' . safe_date($exam['exam_date']) . '</p>',
            'type'       => 'exam',
            'image'      => null,
            'image_type' => null,
            'created_at' => $exam['created_at'],
            'exam_id'    => $exam['id']
        ];
    }
} catch (Exception $e) {
    echo '<pre style="color:red;">Exams SQL Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}

usort($items, fn($a,$b)=>strtotime($b['created_at'])-strtotime($a['created_at']));
?>

<title>Announcements | School Management System</title>

<!-- Hero (compact) -->
<section class="bg-gradient-to-br from-deepblue to-midblue text-white py-10">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h1 class="text-3xl md:text-4xl font-extrabold">School Announcements</h1>
    <p class="text-base md:text-lg mt-3 opacity-95">
      Latest news, events, exams & important notices
    </p>
  </div>
</section>

<!-- Announcements List -->
<section class="py-12 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php if (empty($items)): ?>
        <div class="col-span-full text-center py-16">
          <i class="fas fa-bell-slash text-7xl text-gray-300 mb-6"></i>
          <h3 class="text-2xl font-bold text-gray-500">No announcements yet</h3>
          <p class="text-lg text-gray-600 mt-2">Check back later for updates!</p>
        </div>
      <?php else: foreach ($items as $item): 
        // Image
        if ($item['source'] === 'exam') {
            $imgSrc = 'img/default-announcement.jpg';
        } elseif (!empty($item['image'])) {
            $mime = $item['image_type'] ?: 'image/jpeg';
            $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($item['image']);
        } else {
            $imgSrc = 'img/default-announcement.jpg';
        }

        // Badge
        switch ($item['type']) {
            case 'exam':   $badge = ['bg-green-600', 'Exam']; break;
            case 'event':  $badge = ['bg-purple-600', 'Event']; break;
            case 'notice': $badge = ['bg-red-600', 'Notice']; break;
            default:       $badge = ['bg-deepblue', 'General'];
        }
      ?>
        <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition transform hover:-translate-y-1 flex flex-col h-full">
          <div class="relative">
            <!-- Smaller image height -->
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="w-full h-48 object-cover">
            <span class="<?= $badge[0] ?> text-white px-3 py-1 rounded-full text-xs font-bold absolute top-3 right-3 shadow">
              <?= $badge[1] ?>
            </span>
          </div>
          <div class="p-6 flex-1 flex flex-col justify-between">
            <div>
              <span class="text-xs text-midblue font-semibold"><?= safe_date($item['created_at']) ?></span>
              <h3 class="text-lg font-bold text-deepblue mt-1"><?= htmlspecialchars($item['title']) ?></h3>
              <div class="text-gray-700 mt-3 text-sm leading-relaxed"><?= $item['message'] ?></div>
            </div>

            
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
