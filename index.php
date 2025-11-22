<?php require_once 'includes/head.php'; ?>
<?php require_once 'includes/nav.php'; ?>

<style>
  .hero-bg {
    background: linear-gradient(to right, rgba(255,251,222,0.96), rgba(255,251,222,0.7)),
                url('img/school-building.jpg') center/cover no-repeat;
    min-height: 100vh;
  }
  @media (max-width: 768px) {
    .hero-bg { background: linear-gradient(rgba(255,251,222,0.98), rgba(255,251,222,0.9)), url('img/school-building.jpg') center/cover no-repeat; }
  }
</style>

<section class="hero-bg flex items-center">
  <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center py-20">
    <div data-aos="fade-up">
      <h1 class="text-5xl md:text-6xl lg:text-7xl font-extrabold text-deepblue leading-tight">
        SCHOOL<br><span class="text-midblue">MANAGEMENT</span><br>SYSTEM
      </h1>
      <p class="text-2xl md:text-3xl font-bold text-midblue mt-6">Streamline. Educate. Excel.</p>
      <p class="text-lg md:text-xl text-gray-700 mt-8 max-w-2xl leading-relaxed">
        A complete digital solution to manage students, teachers, attendance, marks, classes, announcements, and parent communication — all in one powerful, easy-to-use platform.
      </p>

      <div class="mt-12 flex flex-wrap gap-4">
        <?php if (!$user): ?>
          <a href="login.php" class="inline-block bg-lightblue text-deepblue text-xl font-bold px-12 py-6 rounded-xl hover:bg-midblue hover:text-white transition transform hover:scale-105 shadow-2xl">
            Login to SMS
          </a>
        <?php else: ?>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="admin_dashboard.php" class="inline-block bg-deepblue text-white text-xl font-bold px-12 py-6 rounded-xl hover:bg-midblue transition shadow-2xl transform hover:scale-105">Admin Panel</a>
          <?php elseif ($user['role'] === 'teacher'): ?>
            <a href="teacher_dashboard.php" class="inline-block bg-deepblue text-white text-xl font-bold px-12 py-6 rounded-xl hover:bg-midblue transition shadow-2xl transform hover:scale-105">Teacher Portal</a>
          <?php elseif ($user['role'] === 'student'): ?>
            <a href="student_dashboard.php" class="inline-block bg-deepblue text-white text-xl font-bold px-12 py-6 rounded-xl hover:bg-midblue transition shadow-2xl transform hover:scale-105">My Dashboard</a>
          <?php endif; ?>
        <?php endif; ?>
        <a href="announcements.php" class="inline-block bg-white border-2 border-deepblue text-deepblue text-xl font-bold px-10 py-6 rounded-xl hover:bg-deepblue hover:text-white transition shadow-xl">
          View Announcements
        </a>
      </div>
    </div>
    <div data-aos="zoom-in-left" data-aos-delay="300" class="flex justify-center lg:justify-end">
      <img src="img/students-classroom.png" alt="Students" class="w-full max-w-2xl drop-shadow-2xl rounded-2xl">
    </div>
  </div>
</section>

<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-4xl font-bold text-deepblue mb-16" data-aos="fade-up">Everything You Need in One System</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10">
      <div data-aos="fade-up" data-aos-delay="100"><i class="fas fa-users text-5xl text-midblue mb-4"></i><h3 class="font-bold text-deepblue">Students</h3></div>
      <div data-aos="fade-up" data-aos-delay="200"><i class="fas fa-chalkboard-teacher text-5xl text-midblue mb-4"></i><h3 class="font-bold text-deepblue">Teachers</h3></div>
      <div data-aos="fade-up" data-aos-delay="300"><i class="fas fa-clipboard-check text-5xl text-midblue mb-4"></i><h3 class="font-bold text-deepblue">Attendance</h3></div>
      <div data-aos="fade-up" data-aos-delay="400"><i class="fas fa-chart-line text-5xl text-midblue mb-4"></i><h3 class="font-bold text-deepblue">Marks & Reports</h3></div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>