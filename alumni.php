<?php 
require_once __DIR__ . '/includes/head.php'; 
require_once __DIR__ . '/includes/nav.php'; 
?>

<!-- Page-specific title (optional – overrides the default in head.php) -->
<title>Alumni | School Management System</title>

<style>
  .hero-bg {
    background: linear-gradient(to right, rgba(70,130,169,0.95), rgba(116,155,194,0.9)),
                url('img/alumni-hero.jpg') center/cover no-repeat;
  }
</style>

<!-- Hero Section -->
<section class="hero-bg text-white py-32 relative overflow-hidden">
  <div class="absolute inset-0 bg-black opacity-30"></div>
  <div class="max-w-7xl mx-auto px-6 text-center relative z-10">
    <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold leading-tight" data-aos="fade-up">
      Once a Student,<br>
      <span class="text-yellow-300">Always Family</span>
    </h1>
    <p class="text-xl md:text-3xl mt-8 max-w-4xl mx-auto opacity-95" data-aos="fade-up" data-aos-delay="200">
      Welcome home, Alumni! You are the living proof of our legacy of excellence.
    </p>
  </div>
</section>

<!-- Stats -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-10 text-center">
      <div data-aos="fade-up"><p class="text-6xl font-extrabold text-deepblue">25+</p><p class="text-xl text-midblue mt-2">Years of Excellence</p></div>
      <div data-aos="fade-up" data-aos-delay="100"><p class="text-6xl font-extrabold text-deepblue">150+</p><p class="text-xl text-midblue mt-2">Proud Alumni</p></div>
      <div data-aos="fade-up" data-aos-delay="200"><p class="text-6xl font-extrabold text-deepblue">40+</p><p class="text-xl text-midblue mt-2">Countries Represented</p></div>
      <div data-aos="fade-up" data-aos-delay="300"><p class="text-6xl font-extrabold text-deepblue">100%</p><p class="text-xl text-midblue mt-2">Success Stories</p></div>
    </div>
  </div>
</section>

<!-- Notable Alumni -->
<section class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-5xl font-bold text-center text-deepblue mb-16" data-aos="fade-up">
      Our Pride: Notable Alumni
    </h2>
    <div class="grid md:grid-cols-3 gap-12">
      <div data-aos="fade-up" data-aos-delay="100" class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center hover:-translate-y-4 transition">
        <img src="img/alumni1.jpg" alt="Dr. Aisha Bello" class="w-full h-80 object-cover">
        <div class="p-8">
          <h3 class="text-2xl font-bold text-deepblue">Dr. Aisha Bello</h3>
          <p class="text-midblue font-semibold">Class of 2008</p>
          <p class="text-gray-700 mt-4">Neurosurgeon • Harvard Medical School<br>Rhodes Scholar</p>
        </div>
      </div>
      <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center hover:-translate-y-4 transition">
        <img src="img/alumni2.jpg" alt="Engr. Chike Obi" class="w-full h-80 object-cover">
        <div class="p-8">
          <h3 class="text-2xl font-bold text-deepblue">Engr. Chike Obi</h3>
          <p class="text-midblue font-semibold">Class of 2012</p>
          <p class="text-gray-700 mt-4">CEO, TechNova Africa<br>Forbes 30 Under 30</p>
        </div>
      </div>
      <div data-aos="fade-up" data-aos-delay="300" class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center hover:-translate-y-4 transition">
        <img src="img/alumni3.jpg" alt="Barr. Funmi Adeyemi" class="w-full h-80 object-cover">
        <div class="p-8">
          <h3 class="text-2xl font-bold text-deepblue">Barr. Funmi Adeyemi</h3>
          <p class="text-midblue font-semibold">Class of 2005</p>
          <p class="text-gray-700 mt-4">Senior Advocate of Nigeria (SAN)<br>Human Rights Advocate</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stay Connected -->
<section class="py-24 bg-gradient-to-r from-deepblue to-midblue text-white">
  <div class="max-w-5xl mx-auto px-6 text-center">
    <h2 class="text-5xl md:text-6xl font-extrabold mb-8" data-aos="fade-up">Stay Connected</h2>
    <p class="text-xl md:text-2xl mb-12 opacity-95" data-aos="fade-up" data-aos-delay="200">
      Join thousands of alumni who give back, mentor students, and celebrate our shared legacy
    </p>
    <div class="grid md:grid-cols-3 gap-8">
      <a href="alumni_register.php" class="bg-white/10 backdrop-blur-lg p-10 rounded-3xl hover:bg-white/20 transition transform hover:scale-105">
        <i class="fas fa-user-plus text-6xl mb-6"></i>
        <h3 class="text-2xl font-bold">Register as Alumni</h3>
      </a>
      <a href="alumni_events.php" class="bg-white/10 backdrop-blur-lg p-10 rounded-3xl hover:bg-white/20 transition transform hover:scale-105">
        <i class="fas fa-calendar-heart text-6xl mb-6"></i>
        <h3 class="text-2xl font-bold">Upcoming Reunions</h3>
      </a>
      <a href="alumni_giveback.php" class="bg-white/10 backdrop-blur-lg p-10 rounded-3xl hover:bg-white/20 transition transform hover:scale-105">
        <i class="fas fa-hands-helping text-6xl mb-6"></i>
        <h3 class="text-2xl font-bold">Give Back</h3>
      </a>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-5xl font-bold text-center text-deepblue mb-16" data-aos="fade-up">What Our Alumni Say</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
      <div data-aos="fade-up" class="bg-cream p-8 rounded-3xl shadow-xl">
        <p class="text-lg italic text-gray-700">"This school didn’t just teach me academics — it taught me resilience and leadership."</p>
        <div class="mt-6 flex items-center gap-4">
          <div class="w-16 h-16 bg-gray-300 rounded-full"></div>
          <div><p class="font-bold text-deepblue">Tolu Adekunle</p><p class="text-sm text-midblue">Class of 2010 • Google Engineer</p></div>
        </div>
      </div>
      <div data-aos="fade-up" data-aos-delay="100" class="bg-cream p-8 rounded-3xl shadow-xl">
        <p class="text-lg italic text-gray-700">"The foundation I received here opened doors to Oxford and beyond. Forever grateful."</p>
        <div class="mt-6 flex items-center gap-4">
          <div class="w-16 h-16 bg-gray-300 rounded-full"></div>
          <div><p class="font-bold text-deepblue">Chioma Okonkwo</p><p class="text-sm text-midblue">Class of 2015 • Oxford PhD</p></div>
        </div>
      </div>
      <div data-aos="fade-up" data-aos-delay="200" class="bg-cream p-8 rounded-3xl shadow-xl">
        <p class="text-lg italic text-gray-700">"From prefect to CEO — everything started here. Proud to call this my alma mater."</p>
        <div class="mt-6 flex items-center gap-4">
          <div class="w-16 h-16 bg-gray-300 rounded-full"></div>
          <div><p class="font-bold text-deepblue">David Okafor</p><p class="text-sm text-midblue">Class of 2007 • CEO, PayStack</p></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Final CTA -->
<section class="py-20 bg-gradient-to-br from-purple-600 to-deepblue text-white text-center">
  <div class="max-w-4xl mx-auto px-6">
    <h2 class="text-5xl font-extrabold mb-8">You Belong Here — Forever</h2>
    <p class="text-2xl mb-12">Join the Alumni Association today and stay part of the family</p>
    <a href="alumni_register.php" class="inline-block bg-yellow-400 text-deepblue text-2xl font-bold px-16 py-8 rounded-2xl hover:bg-white transition transform hover:scale-110 shadow-2xl">
      Become a Member Today
    </a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>