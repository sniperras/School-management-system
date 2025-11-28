<?php 
require_once 'includes/head.php'; 
require_once 'includes/nav.php'; 

// Start session if not already started (safe way)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>

<style>
  .hero-bg {
    background: linear-gradient(to right, rgba(255,251,222,0.96), rgba(255,251,222,0.7)),
                url('img/school-building.jpg') center/cover no-repeat fixed;
    min-height: 100vh;
  }
  @media (max-width: 768px) {
    .hero-bg { 
      background: linear-gradient(rgba(255,251,222,0.98), rgba(255,251,222,0.9)), 
                  url('img/school-building.jpg') center/cover no-repeat;
      background-attachment: scroll;
    }
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
        A complete digital solution to manage students, teachers, attendance, marks, classes, and announcements — all in one powerful, easy-to-use platform.
      </p>

      <div class="mt-12 flex flex-wrap gap-6">
        <?php if (!$user): ?>
          <a href="login.php" class="inline-block bg-lightblue text-deepblue text-xl font-bold px-12 py-6 rounded-xl hover:bg-midblue hover:text-white transition transform hover:scale-105 shadow-2xl">
            Login to SMS
          </a>
        <?php else: ?>
          <?php 
            $dashboard = '';
            $btnText = '';
            if ($user['role'] === 'admin') {
              $dashboard = 'admin_dashboard.php';
              $btnText = 'Admin Panel';
            } elseif ($user['role'] === 'teacher') {
              $dashboard = 'teacher_dashboard.php';
              $btnText = 'Teacher Portal';
            } elseif ($user['role'] === 'student') {
              $dashboard = 'student_dashboard.php';
              $btnText = 'My Dashboard';
            }
          ?>
          <a href="<?= htmlspecialchars($dashboard) ?>" class="inline-block bg-deepblue text-white text-xl font-bold px-12 py-6 rounded-xl hover:bg-midblue transition shadow-2xl transform hover:scale-105">
            <?= htmlspecialchars($btnText) ?>
          </a>
        <?php endif; ?>

        <a href="announcements.php" class="inline-block bg-white border-2 border-deepblue text-deepblue text-xl font-bold px-10 py-6 rounded-xl hover:bg-deepblue hover:text-white transition shadow-xl transform hover:scale-105">
          View Announcements
        </a>
      </div>
    </div>

   <div data-aos="zoom-in-left" data-aos-delay="300" class="flex justify-center lg:justify-end">
  <img src="img/students-classroom.png" 
       alt="Happy students in classroom" 
       class="w-full max-w-2xl h-96 lg:h-[500px] drop-shadow-2xl rounded-2xl object-cover">
</div>

  </div>
</section>

<!-- Features Section -->
<section class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-4xl md:text-5xl font-bold text-deepblue mb-16" data-aos="fade-up">
      Everything You Need in One System
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 lg:gap-16">
      <div data-aos="fade-up" data-aos-delay="100">
        <i class="fas fa-users text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Students</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="200">
        <i class="fas fa-chalkboard-teacher text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Teachers</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="300">
        <i class="fas fa-clipboard-check text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Attendance</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="400">
        <i class="fas fa-chart-line text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Marks & Reports</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="500">
        <i class="fas fa-calendar-alt text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Timetable</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="600">
        <i class="fas fa-bullhorn text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Announcements</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="700">
        <i class="fas fa-envelope text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Messaging</h3>
      </div>
      <div data-aos="fade-up" data-aos-delay="800">
        <i class="fas fa-shield-alt text-6xl text-midblue mb-4"></i>
        <h3 class="font-bold text-deepblue text-xl">Secure & Fast</h3>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>