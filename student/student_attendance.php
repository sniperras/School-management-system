<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student info
$stmt = $pdo->prepare("SELECT student_id, first_name, last_name, department, current_year, section FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Get all attendance records for this student
$stmt = $pdo->prepare("
    SELECT attendance_date, status, marked_at 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY attendance_date DESC
");
$stmt->execute([$student_id]);
$all_records = $stmt->fetchAll();

// Calculate monthly summary (last 12 months or current academic year)
$monthly_stats = [];
$today = date('Y-m-d');

foreach ($all_records as $rec) {
    $month_key = date('Y-m', strtotime($rec['attendance_date']));
    if (!isset($monthly_stats[$month_key])) {
        $monthly_stats[$month_key] = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
    }
    $monthly_stats[$month_key]['total']++;
    switch (strtolower($rec['status'])) {
        case 'present': $monthly_stats[$month_key]['present']++; break;
        case 'absent':  $monthly_stats[$month_key]['absent']++; break;
        case 'late':    $monthly_stats[$month_key]['late']++; break;
    }
}

// Overall stats
$total_days = count($all_records);
$present_days = array_sum(array_column($monthly_stats, 'present'));
$absent_days = array_sum(array_column($monthly_stats, 'absent'));
$late_days = array_sum(array_column($monthly_stats, 'late'));
$attendance_rate = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance | SMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .calendar-day-present { background: linear-gradient(135deg, #10b981, #34d399); }
        .calendar-day-absent { background: linear-gradient(135deg, #ef4444, #f87171); }
        .calendar-day-late { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Minimal Navbar -->
    <div class="bg-gradient-to-r from-teal-600 to-emerald-700 text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-wide">
                <i class="fas fa-calendar-check mr-3"></i> My Attendance Record
            </h1>
            <a href="student_dashboard.php" class="bg-white text-teal-700 px-6 py-3 rounded-full font-semibold hover:bg-gray-100 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Hero -->
    <div class="bg-gradient-to-br from-teal-500 to-cyan-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl md:text-6xl font-extrabold mb-4">
                Attendance Overview
            </h1>
            <p class="text-xl opacity-90">
                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> 
                • <?= htmlspecialchars($student['student_id']) ?> • <?= htmlspecialchars($student['current_year'] . ' ' . $student['section']) ?>
            </p>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="stat-card bg-white rounded-2xl shadow-xl p-8 text-center">
                <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= $present_days ?></h3>
                <p class="text-gray-600 font-medium">Present</p>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-xl p-8 text-center">
                <i class="fas fa-times-circle text-5xl text-red-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= $absent_days ?></h3>
                <p class="text-gray-600 font-medium">Absent</p>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-xl p-8 text-center">
                <i class="fas fa-clock text-5xl text-amber-500 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= $late_days ?></h3>
                <p class="text-gray-600 font-medium">Late</p>
            </div>
            <div class="stat-card bg-white rounded-2xl shadow-xl p-8 text-center">
                <i class="fas fa-percentage text-5xl text-blue-600 mb-4"></i>
                <h3 class="text-4xl font-bold text-gray-800"><?= $attendance_rate ?>%</h3>
                <p class="text-gray-600 font-medium">Attendance Rate</p>
                <p class="text-sm mt-2 <?= $attendance_rate >= 90 ? 'text-green-600' : ($attendance_rate >= 75 ? 'text-amber-600' : 'text-red-600') ?> font-bold">
                    <?= $attendance_rate >= 90 ? 'Excellent!' : ($attendance_rate >= 75 ? 'Good' : 'Needs Improvement') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Monthly Summary -->
    <?php if (!empty($monthly_stats)): ?>
    <div class="max-w-7xl mx-auto px-6 py-12">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-10">Monthly Attendance Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($monthly_stats as $month => $stats): 
                $rate = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;
                $monthName = date('F Y', strtotime($month . '-01'));
            ?>
                <div class="bg-white rounded-2xl shadow-xl p-8 border-l-8 <?= $rate >= 90 ? 'border-green-500' : ($rate >= 75 ? 'border-amber-500' : 'border-red-500') ?>">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4"><?= $monthName ?></h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Days</span>
                            <span class="font-bold"><?= $stats['total'] ?></span>
                        </div>
                        <div class="flex justify-between text-green-600">
                            <span>Present</span>
                            <span class="font-bold"><?= $stats['present'] ?> days</span>
                        </div>
                        <div class="flex justify-between text-red-600">
                            <span>Absent</span>
                            <span class="font-bold"><?= $stats['absent'] ?> days</span>
                        </div>
                        <?php if ($stats['late'] > 0): ?>
                        <div class="flex justify-between text-amber-600">
                            <span>Late</span>
                            <span class="font-bold"><?= $stats['late'] ?> days</span>
                        </div>
                        <?php endif; ?>
                        <div class="pt-4 border-t mt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Rate</span>
                                <span class="text-3xl font-bold <?= $rate >= 90 ? 'text-green-600' : ($rate >= 75 ? 'text-amber-600' : 'text-red-600') ?>">
                                    <?= $rate ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Attendance Log -->
    <div class="max-w-7xl mx-auto px-6 py-12">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-10">Recent Attendance Record</h2>
        
        <?php if (empty($all_records)): ?>
            <div class="text-center py-20 bg-gray-100 rounded-3xl">
                <i class="fas fa-calendar-times text-8xl text-gray-400 mb-6"></i>
                <p class="text-2xl text-gray-600">No attendance records found yet.</p>
                <p class="text-gray-500 mt-4">Your teachers will mark attendance daily.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                            <tr>
                                <th class="px-8 py-5 text-left">Date</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Marked At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach (array_slice($all_records, 0, 20) as $rec): 
                                $status = strtolower($rec['status']);
                                $badgeClass = $status === 'present' ? 'bg-green-100 text-green-800' : 
                                            ($status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800');
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-8 py-6 font-medium text-gray-800">
                                        <?= date('d M Y, l', strtotime($rec['attendance_date'])) ?>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold <?= $badgeClass ?>">
                                            <i class="fas fa-circle text-xs mr-2"></i>
                                            <?= ucfirst($rec['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center text-gray-600">
                                        <?= date('h:i A', strtotime($rec['marked_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>