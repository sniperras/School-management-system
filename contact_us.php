<?php
// contact_us.php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$dataPath = __DIR__ . '/data/messages.json';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_message') {
    $token = $_POST['csrf'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = 'Security check failed. Please try again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? 'General Inquiry');
        $message = trim($_POST['message'] ?? '');

        if ($name === '')    $errors[] = 'Please enter your full name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if ($message === '') $errors[] = 'Please write your message.';

        if (empty($errors)) {
            $messages = read_json($dataPath, []);
            $entry = [
                'id'         => bin2hex(random_bytes(8)),
                'name'       => $name,
                'email'      => $email,
                'subject'    => $subject,
                'message'    => $message,
                'created_at' => date('c'),
            ];
            array_unshift($messages, $entry);
            write_json($dataPath, $messages);

            $success = true;
            header('Location: contact_us.php?sent=1');
            exit;
        }
    }
}
$sent = isset($_GET['sent']) && $_GET['sent'] == '1';
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Contact Us | School Management System</title>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-deepblue to-midblue text-white py-24 text-center">
  <div class="max-w-7xl mx-auto px-6">
    <h1 class="text-5xl md:text-7xl font-extrabold leading-tight" data-aos="fade-up">
      Get in Touch
    </h1>
    <p class="text-xl md:text-2xl mt-6 max-w-3xl mx-auto opacity-95" data-aos="fade-up" data-aos-delay="200">
      Have a question? We’d love to hear from you. Send us a message and we’ll respond within 24 hours.
    </p>
  </div>
</section>

<!-- Contact Form + Info -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16">

    <!-- Contact Form -->
    <div data-aos="fade-right">
      <h2 class="text-4xl font-bold text-deepblue mb-8">Send Us a Message</h2>

      <?php if ($success || $sent): ?>
        <div class="mb-8 p-6 bg-green-50 border border-green-200 text-green-800 rounded-2xl flex items-center gap-4">
          <i class="fas fa-check-circle text-4xl"></i>
          <div>
            <strong>Thank you!</strong><br>
            Your message has been sent successfully. We’ll get back to you soon.
          </div>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="mb-6 p-5 bg-red-50 border border-red-200 text-red-700 rounded-2xl">
          <?php foreach ($errors as $err): ?>
            <div class="flex items-center gap-2">
              <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($err) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" novalidate class="space-y-6">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="send_message">

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-bold text-deepblue mb-2">Full Name</label>
            <input name="name" type="text" required value="<?= e($_POST['name'] ?? '') ?>"
                   class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition"
                   placeholder="John Doe">
          </div>
          <div>
            <label class="block text-sm font-bold text-deepblue mb-2">Email Address</label>
            <input name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>"
                   class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition"
                   placeholder="john@example.com">
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-deepblue mb-2">Subject</label>
          <input name="subject" type="text" value="<?= e($_POST['subject'] ?? '') ?>"
                 class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition"
                 placeholder="Admissions Inquiry">
        </div>

        <div>
          <label class="block text-sm font-bold text-deepblue mb-2">Your Message</label>
          <textarea name="message" rows="7" required
                    class="w-full px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-midblue focus:ring-4 focus:ring-lightblue/30 transition resize-none"
                    placeholder="Write your message here..."><?= e($_POST['message'] ?? '') ?></textarea>
        </div>

        <button type="submit"
                class="w-full bg-gradient-to-r from-deepblue to-midblue text-white font-bold text-xl py-6 rounded-xl hover:shadow-2xl hover:scale-105 transition transform duration-300 uppercase tracking-wider">
          Send Message
        </button>
      </form>
    </div>

    <!-- Contact Info + Map -->
    <div data-aos="fade-left" class="space-y-12">
      <div>
        <h2 class="text-4xl font-bold text-deepblue mb-8">Visit or Contact Us</h2>
        
        <div class="space-y-8">
          <div class="flex gap-6 bg-cream p-8 rounded-2xl shadow-xl">
            <div class="text-midblue text-5xl"><i class="fas fa-map-marker-alt"></i></div>
            <div>
              <h3 class="text-xl font-bold text-deepblue">Campus Address</h3>
              <p class="text-gray-700 mt-2">
                123 Excellence Avenue<br>
                Education District, Addis Ababa<br>
                Ethiopia
              </p>
            </div>
          </div>

          <div class="flex gap-6 bg-cream p-8 rounded-2xl shadow-xl">
            <div class="text-midblue text-5xl"><i class="fas fa-phone-volume"></i></div>
            <div>
              <h3 class="text-xl font-bold text-deepblue">Phone</h3>
              <p class="text-2xl text-deepblue font-semibold mt-2">+251 911 223 344</p>
              <p class="text-sm text-gray-600">Mon–Fri: 8:00 AM – 4:00 PM</p>
            </div>
          </div>

          <div class="flex gap-6 bg-cream p-8 rounded-2xl shadow-xl">
            <div class="text-midblue text-5xl"><i class="fas fa-envelope"></i></div>
            <div>
              <h3 class="text-xl font-bold text-deepblue">Email</h3>
              <p class="text-xl text-deepblue font-semibold mt-2">info@smschool.edu.et</p>
              <p class="text-xl text-deepblue">admissions@smschool.edu.et</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Maps Embed -->
      <div class="mt-12 rounded-3xl overflow-hidden shadow-2xl">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d8744.446101276882!2d38.79060932692608!3d9.021050312147501!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b857a588c696b%3A0x3704552a89c3f3ff!2sAdmas%20University%20%7C%20megenagna%20%7C!5e0!3m2!1sen!2set!4v1763832399394!5m2!1sen!2set"
          width="100%" height="400" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>
  </div>
</section>

<!-- Quick Action Buttons -->
<section class="py-16 bg-gradient-to-r from-deepblue to-midblue text-white">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-4xl font-bold mb-10">Need Help Faster?</h2>
    <div class="grid md:grid-cols-3 gap-8">
      <a href="tel:+251911223344" class="bg-white/10 backdrop-blur p-8 rounded-2xl hover:bg-white/20 transition transform hover:scale-105">
        <i class="fas fa-phone text-5xl mb-4"></i>
        <p class="text-xl font-bold">Call Admissions</p>
      </a>
      <a href="https://wa.me/251911223344" class="bg-white/10 backdrop-blur p-8 rounded-2xl hover:bg-white/20 transition transform hover:scale-105">
        <i class="fab fa-whatsapp text-5xl mb-4 text-green-400"></i>
        <p class="text-xl font-bold">Chat on WhatsApp</p>
      </a>
      <a href="admissions.php" class="bg-white/10 backdrop-blur p-8 rounded-2xl hover:bg-white/20 transition transform hover:scale-105">
        <i class="fas fa-graduation-cap text-5xl mb-4"></i>
        <p class="text-xl font-bold">Apply Now</p>
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>