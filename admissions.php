<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<!-- Page Title -->
<title>Admissions | School Management System</title>

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-deepblue via-midblue to-indigo-800 text-white py-32 overflow-hidden">
  <div class="absolute inset-0 opacity-20">
    <img src="img/students-classroom.png" alt="Welcome Students" class="w-full h-full object-cover">
  </div>
  <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
    <h1 class="text-5xl md:text-7xl font-extrabold leading-tight" data-aos="fade-up">
      Join Our Family
    </h1>
    <p class="text-2xl md:text-3xl mt-6 max-w-4xl mx-auto opacity-95" data-aos="fade-up" data-aos-delay="200">
      Begin your journey toward academic excellence and personal growth
    </p>
    <div class="mt-12" data-aos="fade-up" data-aos-delay="400">
      <a href="#apply" class="inline-block bg-lightblue text-deepblue text-2xl font-bold px-16 py-7 rounded-2xl hover:bg-white hover:text-deepblue transition transform hover:scale-110 shadow-2xl">
        Apply Now for 2025/2026 Session
      </a>
    </div>
  </div>
</section>

<!-- Admissions Process -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl md:text-5xl font-bold text-center text-deepblue mb-16" data-aos="fade-up">
      Simple 4-Step Admission Process
    </h2>
    <div class="grid md:grid-cols-4 gap-10 text-center">
      <div data-aos="fade-up" data-aos-delay="100">
        <div class="bg-gradient-to-br from-deepblue to-midblue text-white w-24 h-24 rounded-full flex items-center justify-center mx-auto text-4xl font-bold mb-6 shadow-xl">1</div>
        <h3 class="text-2xl font-bold text-deepblue">Submit Application</h3>
        <p class="text-gray-600 mt-4">Fill out our online form in under 10 minutes</p>
      </div>
      <div data-aos="fade-up" data-aos-delay="200">
        <div class="bg-gradient-to-br from-midblue to-lightblue text-white w-24 h-24 rounded-full flex items-center justify-center mx-auto text-4xl font-bold mb-6 shadow-xl">2</div>
        <h3 class="text-2xl font-bold text-deepblue">Entrance Exam</h3>
        <p class="text-gray-600 mt-4">Take our assessment test (Math & English)</p>
      </div>
      <div data-aos="fade-up" data-aos-delay="300">
        <div class="bg-gradient-to-br from-lightblue to-cyan-500 text-white w-24 h-24 rounded-full flex items-center justify-center mx-auto text-4xl font-bold mb-6 shadow-xl">3</div>
        <h3 class="text-2xl font-bold text-deepblue">Interview</h3>
        <p class="text-gray-600 mt-4">Meet with our admissions team & tour campus</p>
      </div>
      <div data-aos="fade-up" data-aos-delay="400">
        <div class="bg-gradient-to-br from-cyan-500 to-teal-600 text-white w-24 h-24 rounded-full flex items-center justify-center mx-auto text-4xl font-bold mb-6 shadow-xl">4</div>
        <h3 class="text-2xl font-bold text-deepblue">Welcome Aboard!</h3>
        <p class="text-gray-600 mt-4">Receive acceptance letter & complete registration</p>
      </div>
    </div>
  </div>
</section>

<!-- Requirements by Level -->
<section class="py-20 bg-gray-50">
  <div class="max-w-7xl mx-auto px-6">
    <h2 class="text-4xl md:text-5xl font-bold text-center text-deepblue mb-16" data-aos="fade-up">
      Admission Requirements
    </h2>
    <div class="grid md:grid-cols-3 gap-10">
      <div data-aos="fade-up" data-aos-delay="100" class="bg-white rounded-2xl shadow-xl p-8 border-t-8 border-green-500">
        <h3 class="text-2xl font-bold text-deepblue mb-6">Certificate & Diploma Programs</h3>
        <ul class="space-y-4 text-gray-700">
          <li>Completed Junior or Senior Secondary Education (minimum Grade 9 or Grade 12, depending on program)</li>
          <li>Official School Leaving Certificate or Transcript</li>
          <li>Entrance Assessment or Aptitude Test (to evaluate practical skills and program suitability)</li>
          <li>Birth Certificate and National ID</li>
          <li>2 Passport Photographs</li>
          <li>Birth Certificate or National ID</li>
        </ul>
      </div>
      <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-2xl shadow-xl p-8 border-t-8 border-blue-500">
        <h3 class="text-2xl font-bold text-deepblue mb-6">Advanced Diploma / Higher National Diploma (HND)</h3>
        <ul class="space-y-4 text-gray-700">
          <li>Accredited Secondary School Certificate (Grade 12 completion)</li>
          <li>Strong Academic Record in relevant subjects (Math, Science, or Technical courses)</li>
          <li>Previous TVET Certificate/Diploma (if applying for advanced entry)</li>
          <li>Recommendation Letter from school or employer (for experienced applicants)</li>
        </ul>
      </div>
      <div data-aos="fade-up" data-aos-delay="300" class="bg-white rounded-2xl shadow-xl p-8 border-t-8 border-purple-600">
        <h3 class="text-2xl font-bold text-deepblue mb-6">Specialized Vocational Programs (e.g., Engineering Trades, ICT, Hospitality)</h3>
        <ul class="space-y-4 text-gray-700">
          <li>Minimum educational qualification as specified by program (Grade 9 or Grade 12)</li>
          <li>Practical Skills Assessment or Interview</li>
          <li>Transfer Certificate (if coming from another institution)</li>
          <li>Portfolio of prior work/experience (optional but advantageous)</li>
          <li>Interview with faculty panel (may be required)</li>
        </ul>
      </div>
    </div>
    <br><br><br>
    <div class="grid md:grid-cols-3 gap-10">
      <div data-aos="fade-up" data-aos-delay="100" class="bg-white rounded-2xl shadow-xl p-8 border-t-8 border-orange-500">
        <h3 class="text-2xl font-bold text-deepblue mb-6">Undergraduate Programs</h3>
        <ul class="space-y-4 text-gray-700">
          <li>Completed Senior Secondary/High School Certificate</li>
          <li>Official Academic Transcripts</li>
          <li>Minimum required grades in relevant subjects (depending on program)</li>
          <li>Entrance Examination or standardized test scores (if applicable)</li>
          <li>2 Passport Photographs</li>
          <li>Birth Certificate or National ID</li>
        </ul>
      </div>
      <div data-aos="fade-up" data-aos-delay="200" class="bg-white rounded-2xl shadow-xl p-8 border-t-8 border-cyan-500">
        <h3 class="text-2xl font-bold text-deepblue mb-6">Postgraduate Programs (Master’s Level)</h3>
        <ul class="space-y-4 text-gray-700">
          <li>Accredited Bachelor’s Degree Certificate</li>
          <li>Official Undergraduate Transcripts</li>
          <li>Minimum GPA requirement (varies by program)</li>
          <li>Statement of Purpose (explaining academic and career goals)</li>
          <li>Recommendation Letters (academic/professional referees)</li>
        </ul>
      </div>
      <div data-aos="fade-up" data-aos-delay="300" class="bg-white rounded-2xl shadow-xl p-8 border-t-8 border-red-600">
        <h3 class="text-2xl font-bold text-deepblue mb-6">Doctoral Programs (PhD Level)</h3>
        <ul class="space-y-4 text-gray-700">
          <li>Accredited Master’s Degree Certificate</li>
          <li>Official Postgraduate Transcripts</li>
          <li>Detailed Research Proposal aligned with faculty expertise</li>
          <li>Curriculum Vitae (CV) with academic publications (if any)</li>
          <li>Recommendation Letters from academic supervisors</li>
          <li>Interview with faculty panel (may be required)</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Application CTA -->
<section id="apply" class="py-24 bg-gradient-to-r from-deepblue to-midblue text-white">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <h2 class="text-4xl md:text-6xl font-extrabold mb-8" data-aos="fade-up">
      Start Your Application Today
    </h2>
    <p class="text-xl md:text-2xl mb-12 opacity-95" data-aos="fade-up" data-aos-delay="200">
      Secure your child's future in one of the best schools in the region
    </p>
    <div class="flex flex-col sm:flex-row gap-8 justify-center" data-aos="fade-up" data-aos-delay="400">
      <a href="admin/application_form.php" class="bg-lightblue text-deepblue text-2xl font-bold px-16 py-8 rounded-2xl hover:bg-white transition transform hover:scale-110 shadow-2xl">
        Start Online Application
      </a>
      <a href="contact.php" class="bg-transparent border-4 border-white text-white text-2xl font-bold px-16 py-8 rounded-2xl hover:bg-white hover:text-deepblue transition">
        Download Form (PDF)
      </a>
    </div>
    <p class="mt-12 text-lg opacity-90">
      Application Deadline: <strong>April 30, 2026</strong> • Limited Spaces Available
    </p>
  </div>
</section>

<!-- Contact Info -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h3 class="text-3xl font-bold text-deepblue mb-8">Need Help?</h3>
    <div class="grid md:grid-cols-3 gap-10">
      <div class="p-8 bg-cream rounded-2xl shadow-lg">
        <p class="font-bold text-deepblue text-xl">Call Admissions</p>
        <p class="text-2xl mt-2">0115-50-88-08/10 <br>
Fax: 0115-50-89-01</p>
      </div>
      <div class="p-8 bg-cream rounded-2xl shadow-lg">
        <p class="font-bold text-deepblue text-xl">Email Us</p>
        <p class="text-2xl mt-2">info@admasuniversity.edu.et</p>
      </div>
      <div class="p-8 bg-cream rounded-2xl shadow-lg">
        <p class="font-bold text-deepblue text-xl">Visit Campus</p>
        <p class="text-lg mt-2">Megenagna, Addis Ababa, Ethiopia</p>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>