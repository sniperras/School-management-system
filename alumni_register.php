<?php
// alumni_register.php

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php'; // defines $pdo

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $official_id     = trim($_POST['official_id']);
    $first_name      = trim($_POST['first_name']);
    $last_name       = trim($_POST['last_name']);
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $graduation_year = trim($_POST['graduation_year']);
    $program         = trim($_POST['program']);
    $occupation      = trim($_POST['occupation']);
    $employer        = trim($_POST['employer']);
    $achievements    = trim($_POST['achievements']);

    // Validate Official ID format: adma/xxxx/xx
    if (!preg_match("/^adma\/\d{4}\/\d{2}$/", $official_id)) {
        $message = "Error: Official Student ID must follow the format adma/xxxx/xx.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO alumni 
                (official_id, first_name, last_name, email, phone, graduation_year, program, occupation, employer, achievements) 
                VALUES 
                (:official_id, :first_name, :last_name, :email, :phone, :graduation_year, :program, :occupation, :employer, :achievements)");

            $stmt->execute([
                ':official_id'     => $official_id,
                ':first_name'      => $first_name,
                ':last_name'       => $last_name,
                ':email'           => $email,
                ':phone'           => $phone,
                ':graduation_year' => $graduation_year,
                ':program'         => $program,
                ':occupation'      => $occupation,
                ':employer'        => $employer,
                ':achievements'    => $achievements
            ]);

            $message = "Registration successful! Welcome to the Alumni Association.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // duplicate entry
                $message = "Error: This Official Student ID or Email is already registered.";
            } else {
                $message = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/head.php'; ?>
<?php require_once __DIR__ . '/includes/nav.php'; ?>

<title>Alumni Registration | School Management System</title>

<section class="bg-gradient-to-r from-deepblue to-midblue text-white py-20">
  <div class="max-w-4xl mx-auto px-6 text-center">
    <h1 class="text-5xl font-extrabold mb-6">Alumni Registration</h1>
    <p class="text-xl opacity-90">Register today to join the alumni network and stay informed about reunions, events, and opportunities.</p>
  </div>
</section>

<main class="max-w-4xl mx-auto px-6 py-16">
  <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-xl bg-green-100 text-green-800 font-semibold">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="space-y-6 bg-white p-10 rounded-3xl shadow-2xl">
    <!-- Official Student ID -->
    <div>
      <label class="block text-gray-700 font-bold mb-2">
        Official Student ID (Format: adma/xxxx/xx) <span style="color:red">*</span>
      </label>
      <input type="text" name="official_id" 
             pattern="^adma\/[0-9]{4}\/[0-9]{2}$" 
             required 
             class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue"
             placeholder="adma/1234/56">
    </div>

    <!-- Name -->
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-bold mb-2">First Name <span style="color:red">*</span></label>
        <input type="text" name="first_name" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
      <div>
        <label class="block text-gray-700 font-bold mb-2">Last Name <span style="color:red">*</span></label>
        <input type="text" name="last_name" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
    </div>

    <!-- Contact -->
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-bold mb-2">Email <span style="color:red">*</span></label>
        <input type="email" name="email" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
      <div>
        <label class="block text-gray-700 font-bold mb-2">Phone <span style="color:red">*</span></label>
        <input type="text" name="phone" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
    </div>

    <!-- Graduation Year & Program -->
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-bold mb-2">Graduation Year <span style="color:red">*</span></label>
        <input type="number" name="graduation_year" min="1950" max="2099" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
      <div>
        <label class="block text-gray-700 font-bold mb-2">Program/Degree <span style="color:red">*</span></label>
        <input type="text" name="program" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
    </div>

    <!-- Occupation & Employer -->
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-bold mb-2">Occupation <span style="color:red">*</span></label>
        <input type="text" name="occupation" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
      <div>
        <label class="block text-gray-700 font-bold mb-2">Employer <span style="color:red">*</span></label>
        <input type="text" name="employer" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue">
      </div>
    </div>

    <!-- Achievements -->
    <div>
      <label class="block text-gray-700 font-bold mb-2">Achievements <span style="color:red">*</span></label>
      <textarea name="achievements" rows="4" required class="w-full border rounded-xl px-4 py-3 focus:ring focus:ring-deepblue"></textarea>
    </div>

    <!-- Submit -->
    <div class="text-center">
      <button type="submit" class="bg-deepblue text-white px-10 py-4 rounded-xl font-bold hover:bg-midblue transition transform hover:scale-105">
        Register
      </button>
    </div>
  </form>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
