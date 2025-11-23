<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Timetable & Schedule | School Management System</title>

<style>
  .subject-card { transition: all 0.3s; }
  .subject-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(70,130,169,0.25)!important; }
  .day-header { background: linear-gradient(135deg, #4682A9, #749BC2); }
</style>

<!-- Hero -->
<section class="bg-gradient-to-br from-deepblue to-midblue text-white py-28 text-center">
  <div class="max-w-7xl mx-auto px-6">
    <h1 class="text-5xl md:text-7xl font-extrabold mb-6" data-aos="fade-up">
      Timetable & Schedule
    </h1>
    <p class="text-xl md:text-2xl opacity-95 max-w-4xl mx-auto" data-aos="fade-up" data-aos-delay="200">
      Stay organized with class schedules, exam dates, and school events — all in one place
    </p>
  </div>
</section>

<!-- Weekly Timetable -->
<section class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl font-bold text-center text-deepblue mb-12">Weekly Class Timetable</h2>

    <div class="grid grid-cols-1 lg:grid-cols-8 gap-6">
      <!-- Time Slots -->
      <div class="lg:col-span-1"></div>
      <?php 
      $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      foreach ($days as $day): ?>
        <div class="text-center">
          <div class="day-header text-white font-bold py-4 rounded-t-2xl text-lg"><?= $day ?></div>
        </div>
      <?php endforeach; ?>

      <?php
      $timeSlots = [
        '08:00 - 09:00' => ['Math', 'English', 'Physics', 'Chemistry', 'Biology', 'History', '—'],
        '09:00 - 10:00' => ['Physics', 'Math', 'English', 'Biology', 'Chemistry', 'Geography', '—'],
        '10:15 - 11:15' => ['Chemistry', 'Biology', 'Math', 'English', 'Physics', 'Literature', '—'],
        '11:15 - 12:15' => ['Break', 'Break', 'Break', 'Break', 'Break', 'Break', '—'],
        '12:15 - 13:15' => ['History', 'Geography', 'Chemistry', 'Physics', 'Math', 'Art', '—'],
        '13:15 - 14:15' => ['English', 'Physics', 'Biology', 'Math', 'English', 'PE', '—'],
      ];

      $colors = [
        'Math' => 'bg-blue-500', 'English' => 'bg-purple-500', 'Physics' => 'bg-red-500',
        'Chemistry' => 'bg-green-500', 'Biology' => 'bg-yellow-500', 'History' => 'bg-indigo-500',
        'Geography' => 'bg-pink-500', 'Literature' => 'bg-teal-500', 'Art' => 'bg-orange-500',
        'PE' => 'bg-cyan-500', 'Break' => 'bg-gray-400'
      ];

      foreach ($timeSlots as $time => $subjects): ?>
        <div class="text-center text-sm font-semibold text-deepblue bg-white rounded-l-2xl py-6 shadow-lg">
          <?= $time ?>
        </div>
        <?php foreach ($subjects as $index => $subject): 
          $bg = $subject === '—' ? 'bg-gray-100 text-gray-400' : ($colors[$subject] ?? 'bg-gray-300');
          $text = $subject === 'Break' ? 'Lunch Break' : $subject;
        ?>
          <div class="subject-card bg-white rounded-2xl shadow-lg p-6 text-center border border-gray-200">
            <?php if ($subject !== '—'): ?>
              <div class="text-xs text-gray-500 mb-1"><?= $time ?></div>
              <div class="font-bold text-deepblue <?= $subject === 'Break' ? 'text-gray-600' : '' ?>">
                <?= $text ?>
              </div>
              <div class="text-xs mt-2 text-gray-600">Room 301</div>
            <?php else: ?>
              <div class="text-gray-400 italic">Free Period</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>

    <div class="mt-12 text-center">
      <p class="text-sm text-gray-600">
        Last updated: <strong><?= date('d M Y, h:i A') ?></strong> | 
        <a href="#" class="text-midblue font-bold hover:underline">Download PDF Version</a>
      </p>
    </div>
  </div>
</section>

<!-- Exam Schedule -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl font-bold text-center text-deepblue mb-12">Upcoming Exam Schedule</h2>

    <div class="grid md:grid-cols-3 gap-10">
      <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-3xl p-8 shadow-xl hover:scale-105 transition">
        <div class="text-6xl font-bold mb-4">15</div>
        <h3 class="text-2xl font-bold">Mid-Term Exams</h3>
        <p class="mt-4 opacity-90">March 15 – March 22, 2025</p>
        <p class="text-sm mt-6 bg-white/20 rounded-xl p-3">All subjects • Grade 9–12</p>
      </div>

      <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-3xl p-8 shadow-xl hover:scale-105 transition">
        <div class="text-6xl font-bold mb-4">28</div>
        <h3 class="text-2xl font-bold">Final Exams</h3>
        <p class="mt-4 opacity-90">June 28 – July 10, 2025</p>
        <p class="text-sm mt-6 bg-white/20 rounded-xl p-3">National Examination Board</p>
      </div>

      <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white rounded-3xl p-8 shadow-xl hover:scale-105 transition">
        <div class="text-6xl font-bold mb-4">05</div>
        <h3 class="text-2xl font-bold">Entrance Test</h3>
        <p class="mt-4 opacity-90">May 5, 2025</p>
        <p class="text-sm mt-6 bg-white/20 rounded-xl p-3">New Student Admission</p>
      </div>
    </div>
  </div>
</section>

<!-- School Events Calendar -->
<section class="py-20 bg-gradient-to-r from-lightblue to-cream">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-4xl font-bold text-deepblue mb-12">School Events Calendar</h2>

    <div class="grid md:grid-cols-4 gap-8">
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <div class="text-5xl text-deepblue font-bold">27</div>
        <p class="text-xl font-bold text-gray-700 mt-4">Sports Day</p>
        <p class="text-sm text-gray-600 mt-2">March 27, 2025</p>
      </div>
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <div class="text-5xl text-deepblue font-bold">12</div>
        <p class="text-xl font-bold text-gray-700 mt-4">Science Fair</p>
        <p class="text-sm text-gray-600 mt-2">April 12, 2025</p>
      </div>
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <div class="text-5xl text-deepblue font-bold">01</div>
        <p class="text-xl font-bold text-gray-700 mt-4">Graduation</p>
        <p class="text-sm text-gray-600 mt-2">July 1, 2025</p>
      </div>
      <div class="bg-white rounded-3xl shadow-2xl p-8 hover:shadow-3xl transition">
        <div class="text-5xl text-deepblue font-bold">08</div>
        <p class="text-xl font-bold text-gray-700 mt-4">New Session</p>
        <p class="text-sm text-gray-600 mt-2">September 8, 2025</p>
      </div>
    </div>

    <a href="calendar.php" class="inline-block mt-12 bg-deepblue text-white px-10 py-5 rounded-xl font-bold text-xl hover:bg-midblue transition shadow-xl">
      View Full Calendar
    </a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>