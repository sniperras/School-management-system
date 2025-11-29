<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Security: Only logged-in students
if (!is_logged_in() || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch student info
$stmt = $pdo->prepare("SELECT student_id, first_name, last_name, department, current_year, section FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Fetch all exam results with subject names (via exam_name or better mapping later)
$stmt = $pdo->prepare("
    SELECT e.id AS exam_id, e.exam_name, e.exam_date, e.total_marks, 
           em.marks,
           (em.marks / e.total_marks * 100) AS percentage
    FROM exam_marks em
    JOIN exams e ON em.exam_id = e.id
    WHERE em.student_id = ?
    ORDER BY e.exam_date DESC
");
$stmt->execute([$student_id]);
$results = $stmt->fetchAll();

// Calculate statistics
$total_marks = 0;
$total_obtained = 0;
$subject_count = count($results);

foreach ($results as $r) {
    $total_obtained += $r['marks'];
    $total_marks += $r['total_marks'];
}

$overall_percentage = $subject_count > 0 ? ($total_obtained / $total_marks) * 100 : 0;
$gpa = calculate_gpa($overall_percentage);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exam Results | SMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .grade-card { transition: all 0.3s ease; }
        .grade-card:hover { transform: translateY(-10px) scale(1.03); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Minimal Custom Navbar -->
    <div class="bg-gradient-to-r from-indigo-700 to-blue-800 text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-wide">
                <i class="fas fa-chart-line mr-3"></i> My Exam Results
            </h1>
            <a href="student_dashboard.php" class="bg-white text-indigo-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-100 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Hero Greeting -->
    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white py-20">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold mb-4">
                Hello, <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>!
            </h1>
            <p class="text-xl opacity-90">Here are your latest exam results and performance overview</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center transform hover:scale-105 transition">
                <i class="fas fa-trophy text-5xl text-yellow-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= number_format($overall_percentage, 1) ?>%</h3>
                <p class="text-gray-600 font-medium">Overall Average</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center transform hover:scale-105 transition">
                <i class="fas fa-star text-5xl text-purple-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= number_format($gpa, 2) ?></h3>
                <p class="text-gray-600 font-medium">GPA</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center transform hover:scale-105 transition">
                <i class="fas fa-user-graduate text-5xl text-green-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= htmlspecialchars($student['current_year'] ?? 'N/A') ?></h3>
                <p class="text-gray-600 font-medium">Current Year</p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center transform hover:scale-105 transition">
                <i class="fas fa-users text-5xl text-blue-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= htmlspecialchars($student['section'] ?? 'N/A') ?></h3>
                <p class="text-gray-600 font-medium">Section</p>
            </div>
        </div>
    </div>

    <!-- Subject Results -->
    <div class="max-w-7xl mx-auto px-6 py-12">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Your Subject-wise Performance</h2>

        <?php if (empty($results)): ?>
            <div class="text-center py-20 bg-gray-100 rounded-3xl">
                <i class="fas fa-inbox text-8xl text-gray-400 mb-6"></i>
                <p class="text-2xl text-gray-600">No exam results available yet.</p>
                <p class="text-gray-500 mt-4">Results will appear here once your teachers publish them.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php foreach ($results as $r):
                    $gradeInfo = get_grade($r['percentage']);
                ?>
                    <div class="grade-card bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-200">
                        <div class="bg-gradient-to-r <?= $gradeInfo['color'] ?> text-white p-6 text-center">
                            <h3 class="text-2xl font-bold"><?= htmlspecialchars($r['exam_name']) ?></h3>
                            <p class="text-sm opacity-90"><?= date('d M Y', strtotime($r['exam_date'])) ?></p>
                        </div>
                        <div class="p-8 text-center">
                            <div class="text-6xl font-extrabold text-gray-800">
                                <?= number_format($r['percentage'], 1) ?><span class="text-3xl">%</span>
                            </div>
                            <div class="text-4xl font-bold text-gray-700 mt-4"><?= $gradeInfo['grade'] ?></div>
                            <p class="text-sm text-gray-500 mt-4">
                                <?= $r['marks'] ?> / <?= $r['total_marks'] ?> marks
                            </p>
                        </div>
                        <div class="bg-gray-50 px-8 py-5 text-center">
                            <span class="text-green-600 font-bold text-lg">
                                <?= $r['percentage'] >= 80 ? 'Excellent!' : ($r['percentage'] >= 60 ? 'Good Progress' : 'Keep Pushing!') ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Download Report Card -->
            <div class="text-center mt-16">
                <a href="download_report_card.php" class="inline-block bg-gradient-to-r from-indigo-600 to-purple-700 text-white px-12 py-6 rounded-full font-bold text-xl hover:shadow-2xl hover:scale-105 transition transform">
                    <i class="fas fa-download mr-4"></i>
                    Download Full Report Card (PDF)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Performance Trend Chart -->
    <?php if (!empty($results)): ?>
    <div class="max-w-7xl mx-auto px-6 py-16 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-3xl">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-10">Performance Trend</h2>
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <script>
        <?php if (!empty($results)): ?>
        const ctx = document.getElementById('trendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(fn($r) => date('M Y', strtotime($r['exam_date'])), $results)) ?>,
                datasets: [{
                    label: 'Score %',
                    data: <?= json_encode(array_map(fn($r) => round($r['percentage'], 1), $results)) ?>,
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 8,
                    pointHoverRadius: 12
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: false, min: 30, max: 100, ticks: { stepSize: 10 } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>