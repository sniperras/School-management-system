<?php
if (!function_exists('current_user')) {
    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/functions.php';
}
$user = current_user();
?>

<header class="bg-deepblue text-white text-xs md:text-sm uppercase">
  <div class="max-w-7xl mx-auto px-6 py-3 flex flex-col md:flex-row items-center justify-between gap-4">
    <a href="index.php" class="flex items-center gap-3 hover:opacity-90 transition" data-aos="fade-down">
      <img src="img/school-logo.png" alt="School Logo" class="h-10 w-10 rounded-full border-2 border-white">
      <span class="font-bold text-base">SCHOOL MANAGEMENT SYSTEM</span>
    </a>

    <div class="flex flex-wrap items-center gap-4 justify-center md:justify-end">
      <?php if (!$user): ?>
        <a href="register.php" class="bg-lightblue text-deepblue px-6 py-2 rounded font-bold hover:bg-midblue hover:text-white transition">Register</a>
      <?php else: ?>
        <a href="logout.php" class="bg-lightblue text-deepblue px-6 py-2 rounded font-bold hover:bg-midblue hover:text-white transition">
          Logout (<?= htmlspecialchars($user['name'] ?? 'User') ?>)
        </a>
      <?php endif; ?>

      <form action="search.php" method="get" class="relative">
        <input name="q" type="text" placeholder="Search Keywords..." 
               class="px-4 py-2 rounded text-black text-xs w-48 focus:outline-none focus:ring-2 focus:ring-midblue">
        <button type="submit" class="absolute right-3 top-2.5 text-gray-600">
          <i class="fa fa-search"></i>
        </button>
      </form>
    </div>
  </div>
</header>

<nav class="bg-cream shadow-lg sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
    <div class="hidden lg:flex items-center gap-10 font-bold text-deepblue uppercase text-sm tracking-wider">
      <a href="programs.php" class="hover:text-lightblue transition">Programs & Degrees</a>
      <a href="admissions.php" class="hover:text-lightblue transition">Admissions</a>
      <a href="alumni.php" class="hover:text-lightblue transition">Alumni</a>
      <a href="announcements.php" class="hover:text-lightblue transition">Announcements</a>
      <a href="timetable.php" class="hover:text-lightblue transition">Timetable</a>
      <a href="contact_us.php" class="hover:text-lightblue transition">Contact Us</a>
    </div>

    <button id="mobile-menu-btn" class="lg:hidden text-deepblue text-3xl">
      <i class="fas fa-bars"></i>
    </button>

    <?php if ($user): ?>
      <div class="text-sm font-semibold text-deepblue">
        Welcome, <span class="text-midblue capitalize"><?= htmlspecialchars($user['role']) ?></span>
      </div>
    <?php else: ?>
      <a href="login.php" class="bg-lightblue text-deepblue px-8 py-4 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-xl">
        Login Now
      </a>
    <?php endif; ?>
  </div>

  <div id="mobile-menu" class="hidden lg:hidden bg-cream border-t">
    <div class="px-6 py-6 space-y-5 text-center font-bold text-deepblue uppercase text-sm">
      <a href="programs.php" class="block py-3 hover:text-midblue">Programs & Degrees</a>
      <a href="admissions.php" class="block py-3 hover:text-midblue">Admissions</a>
      <a href="alumni.php" class="block py-3 hover:text-midblue">Alumni</a>
      <a href="announcements.php" class="block py-3 hover:text-midblue">Announcements</a>
      <a href="contact_us.php" class="block py-3 hover:text-midblue">Contact Us</a>
      <a href="<?= $user ? 'index.php' : 'login.php' ?>" 
         class="block mt-6 bg-lightblue text-deepblue py-4 rounded-xl hover:bg-midblue hover:text-white">
        <?= $user ? 'Home' : 'Login Now' ?>
      </a>
    </div>
  </div>
</nav>

<script>
  AOS.init({ once: true, duration: 1000 });
  document.getElementById('mobile-menu-btn')?.addEventListener('click', function () {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
    this.querySelector('i').classList.toggle('fa-bars');
    this.querySelector('i').classList.toggle('fa-times');
  });
</script>