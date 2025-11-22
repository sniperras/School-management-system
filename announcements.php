<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Announcements | School Management System</title>

<!-- Hero -->
<section class="bg-gradient-to-br from-deepblue to-midblue text-white py-28">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h1 class="text-5xl md:text-7xl font-extrabold" data-aos="fade-up">School Announcements</h1>
    <p class="text-xl md:text-2xl mt-6 opacity-95" data-aos="fade-up" data-aos-delay="200">
      Stay updated with the latest news, events, and important notices
    </p>
  </div>
</section>

<!-- Announcements List -->
<section class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">

      <!-- Sample Announcement -->
      <div data-aos="fade-up" class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <img src="img/announcement1.jpg" alt="Event" class="w-full h-56 object-cover">
        <div class="p-8">
          <span class="text-sm text-midblue font-semibold">March 15, 2025</span>
          <h3 class="text-2xl font-bold text-deepblue mt-2">2025/2026 Resumption Date</h3>
          <p class="text-gray-700 mt-4">All students are expected to resume on Monday, September 8, 2025. Welcome back!</p>
          <a href="#" class="inline-block mt-6 text-lightblue font-bold hover:text-midblue transition">Read More →</a>
        </div>
      </div>

      <div data-aos="fade-up" data-aos-delay="100" class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <img src="img/announcement2.jpg" alt="Event" class="w-full h-56 object-cover">
        <div class="p-8">
          <span class="text-sm text-midblue font-semibold">March 10, 2025</span>
          <h3 class="text-2xl font-bold text-deepblue mt-2">Inter-House Sports 2025</h3>
          <p class="text-gray-700 mt-4">Get ready for the biggest sporting event of the year! March 27–29.</p>
          <a href="#" class="inline-block mt-6 text-lightblue font-bold hover:text-midblue transition">View Schedule →</a>
        </div>
      </div>

      <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <img src="img/announcement3.jpg" alt="Event" class="w-full h-56 object-cover">
        <div class="p-8">
          <span class="text-sm text-midblue font-semibold">March 5, 2025</span>
          <h3 class="text-2xl font-bold text-deepblue mt-2">Mid-Term Break</h3>
          <p class="text-gray-700 mt-4">Students will proceed on mid-term break from April 11–15, 2025.</p>
          <a href="#" class="inline-block mt-6 text-lightblue font-bold hover:text-midblue transition">Details →</a>
        </div>
      </div>

      <!-- Add more announcements as needed -->
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>