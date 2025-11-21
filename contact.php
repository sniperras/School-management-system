<?php
// contact.php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$dataPath = __DIR__ . '/data/messages.json';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_message') {
    $token = $_POST['csrf'] ?? '';
    if (!check_csrf($token)) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? 'Contact');
        $message = trim($_POST['message'] ?? '');
        if ($name === '') $errors[] = 'Name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($message === '') $errors[] = 'Message is required.';
        if (empty($errors)) {
            $messages = read_json($dataPath, []);
            $entry = [
                'id' => bin2hex(random_bytes(6)),
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'created_at' => date('c'),
            ];
            array_unshift($messages, $entry);
            write_json($dataPath, $messages);
            // Optional: send email (uncomment and configure)
            /*
            $to = 'admin@youruniversity.edu';
            $headers = "From: " . $name . " <" . $email . ">\r\n";
            $body = "Subject: $subject\n\n$message\n\nFrom: $name <$email>";
            mail($to, $subject, $body, $headers);
            */
            $success = true;
            // prevent resubmission
            header('Location: contact.php?sent=1');
            exit;
        }
    }
}
$sent = isset($_GET['sent']) && $_GET['sent'] == '1';
?>
<section class="grid grid-cols-1 md:grid-cols-2 gap-8">
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-2">Contact Us</h2>
    <?php if ($sent): ?>
      <div class="p-4 bg-green-50 text-green-700 rounded">Thanks — your message was received.</div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="mb-3 text-sm text-red-700">
        <?php foreach ($errors as $err) echo '<div>'.e($err).'</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="send_message">
      <label class="block mb-2 text-sm">Name
        <input name="name" class="w-full border rounded px-2 py-1 mt-1" required>
      </label>
      <label class="block mb-2 text-sm">Email
        <input name="email" type="email" class="w-full border rounded px-2 py-1 mt-1" required>
      </label>
      <label class="block mb-2 text-sm">Subject
        <input name="subject" class="w-full border rounded px-2 py-1 mt-1">
      </label>
      <label class="block mb-2 text-sm">Message
        <textarea name="message" rows="6" class="w-full border rounded px-2 py-1 mt-1" required></textarea>
      </label>
      <button class="bg-blue-600 text-white px-4 py-2 rounded-md">Send Message</button>
    </form>
  </div>

  <aside class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-semibold mb-2">Office</h3>
    <p class="text-gray-600">University Administrative Office<br/>123 Campus Drive<br/>City, Country</p>
    <p class="mt-4"><strong>Phone:</strong> +1 (555) 555-5555</p>
    <p class="mt-2"><strong>Email:</strong> info@university.edu</p>
  </aside>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
