<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// SECURITY: Only logged-in students can access this page
if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

if (current_user_role() !== 'student') {
    // If teacher/admin tries to access → send to their dashboard
    $redirect = match (current_user_role()) {
        'admin'   => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        default   => 'index.php'
    };
    header("Location: $redirect");
    exit;
}

$student = current_user(); // Get logged-in student data
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>My Exam Results | School Management System</title>

<style>
  .grade-a { background: linear-gradient(135deg, #10b981, #34d399); }
  .grade-b { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
  .grade-c { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
  .grade-d { background: linear-gradient(135deg, #f87171, #fca5a5); }
  .subject-card:hover { transform: translateY(-10px) scale(1.02); }
</style>

<!-- Hero -->
<section class="bg-gradient-to-br from-deepblue to-midblue text-white py-24 text-center">
  <div class="max-w-7xl mx-auto px-6">
    <h1 class="text-5xl md:text-7xl font-extrabold mb-4" data-aos="fade-up">
      My Exam Results
    </h1>
    <p class="text-xl md:text-2xl opacity-95" data-aos="fade-up" data-aos-delay="200">
      Hello <strong><?= htmlspecialchars($student['name'] ?? 'Student') ?></strong> — View your academic performance
    </p>
  </div>
</section>

<!-- Overall Performance Summary -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid md:grid-cols-4 gap-8 text-center">
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <i class="fas fa-trophy text-6xl text-yellow-500 mb-4"></i>
        <h3 class="text-3xl font-bold text-deepblue">89.4%</h3>
        <p class="text-gray-600 font-medium">Overall Average</p>
      </div>
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <i class="fas fa-star text-6xl text-purple-500 mb-4"></i>
        <h3 class="text-3xl font-bold text-deepblue">Rank 5</h3>
        <p class="text-gray-600 font-medium">Class Position</p>
      </div>
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <i class="fas fa-medal text-6xl text-green-500 mb-4"></i>
        <h3 class="text-3xl font-bold text-deepblue">A-</h3>
        <p class="text-gray-600 font-medium">Grade Point</p>
      </div>
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <i class="fas fa-calendar-check text-6xl text-blue-500 mb-4"></i>
        <h3 class="text-3xl font-bold text-deepblue">2024/2025</h3>
        <p class="text-gray-600 font-medium">Academic Year</p>
      </div>
    </div>
  </div>
</section>

<!-- Subject-wise Results -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl font-bold text-center text-deepblue mb-12">Mid-Term Results – Grade 11A</h2>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
      <?php
      $results = [
        ['subject' => 'Mathematics',     'score' => 94, 'grade' => 'A+',  'color' => 'grade-a'],
        ['subject' => 'Physics',         'score' => 88, 'grade' => 'A',   'color' => 'grade-a'],
        ['subject' => 'Chemistry',       'score' => 91, 'grade' => 'A',   'color' => 'grade-a'],
        ['subject' => 'English',         'score' => 85, 'grade' => 'B+',  'color' => 'grade-b'],
        ['subject' => 'Biology',         'score' => 82, 'grade' => 'B',   'color' => 'grade-b'],
        ['subject' => 'Civics',          'score' => 90, 'grade' => 'A',   'color' => 'grade-a'],
        ['subject' => 'History',         'score' => 78, 'grade' => 'B-',  'color' => 'grade-c'],
        ['subject' => 'ICT',             'score' => 96, 'grade' => 'A+',  'color' => 'grade-a'],
      ];

      foreach ($results as $res): ?>
        <div class="subject-card bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-200 transition-all duration-300">
          <div class="<?= $res['color'] ?> text-white p-6 text-center">
            <h3 class="text-2xl font-bold"><?= $res['subject'] ?></h3>
          </div>
          <div class="p-8 text-center">
            <div class="text-6xl font-extrabold text-deepblue"><?= $res['score'] ?><span class="text-3xl">%</span></div>
            <div class="text-3xl font-bold text-gray-700 mt-4"><?= $res['grade'] ?></div>
            <p class="text-sm text-gray-500 mt-4">Out of 100 marks</p>
          </div>
          <div class="bg-gray-50 px-8 py-4 text-center">
            <span class="text-green-600 font-bold">↑ Excellent Performance</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Download Report Button -->
    <div class="text-center mt-16">
      <a href="#" class="inline-block bg-gradient-to-r from-deepblue to-midblue text-white px-12 py-6 rounded-2xl font-bold text-xl hover:shadow-2xl hover:scale-105 transition transform">
        <i class="fas fa-download mr-3"></i> Download Full Report Card (PDF)
      </a>
    </div>
  </div>
</section>

<!-- Performance Trend -->
<section class="py-20 bg-gradient-to-r from-lightblue to-cream">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-4xl font-bold text-deepblue mb-12">Your Performance Trend</h2>
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-4xl mx-auto">
      <canvas id="performanceChart" height="300"></canvas>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Chart.js for Trend Graph -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('performanceChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Term 1', 'Mid-Term', 'Term 2', 'Final'],
      datasets: [{
        label: 'Average Score (%)',
        data: [82, 89.4, 91, 93],
        borderColor: '#4682A9',
        backgroundColor: 'rgba(70, 130, 169, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#4682A9',
        pointRadius: 8
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: false, min: 70, max: 100 }
      }
    }
  });
</script>
</body>
</html>