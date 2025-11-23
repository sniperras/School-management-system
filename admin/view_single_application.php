<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid application ID");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    die("Application not found");
}

// NEW: Helper function for single document download links
function singleDocUrl($field) {
    global $id;
    return "download_documents.php?id={$id}&single=" . urlencode($field);
}
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Application #<?= htmlspecialchars($app['application_id']) ?> | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-deepblue text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold">Application Details</h1>
                <p class="text-lg opacity-90">ID: <strong><?= htmlspecialchars($app['application_id']) ?></strong></p>
            </div>
            <a href="view_applications.php" class="bg-white text-deepblue px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
                ← All Applications
            </a>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-6 py-10">
        <!-- Personal Info -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-deepblue mb-6 flex items-center gap-3">
                <i class="fas fa-user"></i> Personal Information
            </h2>
            <div class="grid md:grid-cols-3 gap-8 text-lg">
                <div><p class="text-gray-600">Full Name</p><p class="font-bold"><?= htmlspecialchars($app['first_name'] . ' ' . ($app['middle_name'] ?: '') . ' ' . $app['last_name']) ?></p></div>
                <div><p class="text-gray-600">Mother's Name</p><p class="font-bold"><?= htmlspecialchars($app['mother_name'] ?: '—') ?></p></div>
                <div><p class="text-gray-600">Date of Birth</p><p class="font-bold"><?= date('F j, Y', strtotime($app['birth_date'])) ?></p></div>
                <div><p class="text-gray-600">Gender</p><p class="font-bold"><?= ucfirst($app['gender']) ?></p></div>
                <div><p class="text-gray-600">Fayda ID</p><p class="font-bold"><?= htmlspecialchars($app['fayda_id'] ?: '—') ?></p></div>
                <div><p class="text-gray-600">Phone</p><p class="font-bold"><?= htmlspecialchars($app['phone']) ?></p></div>
                <div><p class="text-gray-600">Email</p><p class="font-bold"><?= htmlspecialchars($app['email'] ?: '—') ?></p></div>
                <div class="md:col-span-3"><p class="text-gray-600">Address</p><p class="font-bold"><?= nl2br(htmlspecialchars($app['address'] ?: '—')) ?></p></div>
            </div>
        </div>

        <!-- Academic Info -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-deepblue mb-6 flex items-center gap-3">
                <i class="fas fa-graduation-cap"></i> Academic Information
            </h2>
            <div class="grid md:grid-cols-3 gap-8 text-lg">
                <div><p class="text-gray-600">Program</p><p class="font-bold"><?= htmlspecialchars($app['program']) ?></p></div>
                <div><p class="text-gray-600">Department</p><p class="font-bold"><?= htmlspecialchars($app['department']) ?></p></div>
                <div><p class="text-gray-600">Study Mode</p><p class="font-bold"><?= htmlspecialchars($app['study_mode']) ?></p></div>

                <?php if (in_array($app['program'], ['Masters', 'PhD'])): ?>
                <div><p class="text-gray-600">Previous University</p><p class="font-bold"><?= htmlspecialchars($app['previous_university'] ?: '—') ?></p></div>
                <div><p class="text-gray-600">Bachelor CGPA</p><p class="font-bold"><?= $app['bachelor_cgpa'] ? number_format((float)$app['bachelor_cgpa'], 2) : '—' ?></p></div>
                <?php endif; ?>

                <div><p class="text-gray-600">Applied On</p><p class="font-bold"><?= date('F j, Y g:i A', strtotime($app['applied_at'])) ?></p></div>
            </div>
        </div>

        <!-- Uploaded Documents -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-deepblue mb-6 flex items-center gap-3">
                <i class="fas fa-file-alt"></i> Uploaded Documents
            </h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">

                <!-- Passport Photo -->
                <?php if ($app['passport_photo']): ?>
                <div class="bg-gray-50 rounded-xl p-6 text-center border-2 border-dashed border-gray-300">
                    <p class="font-bold mb-4 text-lg">Passport Photo</p>
                    <img src="<?= singleDocUrl('passport_photo') ?>" 
                         alt="Passport Photo" 
                         class="w-32 h-32 object-cover rounded-lg mx-auto mb-4 shadow-lg border">
                    <a href="<?= singleDocUrl('passport_photo') ?>" 
                       class="block bg-blue-600 text-white font-medium py-2.5 rounded-lg hover:bg-blue-700 transition">
                        Download Photo
                    </a>
                </div>
                <?php endif; ?>

                <!-- Grade 12 Certificate -->
                <?php if ($app['grade_12_doc']): ?>
                <div class="bg-gray-50 rounded-xl p-6 text-center border-2 border-dashed border-gray-300">
                    <p class="font-bold mb-4 text-lg">Grade 12 Certificate</p>
                    <i class="fas fa-file-pdf text-red-600 text-6xl mb-4"></i>
                    <a href="<?= singleDocUrl('grade_12_doc') ?>" 
                       class="block bg-red-600 text-white font-medium py-2.5 rounded-lg hover:bg-red-700 transition">
                        Download Certificate
                    </a>
                </div>
                <?php endif; ?>

                <!-- Grade 10 Certificate -->
                <?php if ($app['grade_10_doc']): ?>
                <div class="bg-gray-50 rounded-xl p-6 text-center border-2 border-dashed border-gray-300">
                    <p class="font-bold mb-4 text-lg">Grade 10 Certificate</p>
                    <i class="fas fa-file-pdf text-red-600 text-6xl mb-4"></i>
                    <a href="<?= singleDocUrl('grade_10_doc') ?>" 
                       class="block bg-red-600 text-white font-medium py-2.5 rounded-lg hover:bg-red-700 transition">
                        Download Certificate
                    </a>
                </div>
                <?php endif; ?>

                <!-- Official Transcript -->
                <?php if ($app['transcript_doc']): ?>
                <div class="bg-gray-50 rounded-xl p-6 text-center border-2 border-dashed border-gray-300">
                    <p class="font-bold mb-4 text-lg">Official Transcript</p>
                    <i class="fas fa-file-lines text-purple-600 text-6xl mb-4"></i>
                    <a href="<?= singleDocUrl('transcript_doc') ?>" 
                       class="block bg-purple-600 text-white font-medium py-2.5 rounded-lg hover:bg-purple-700 transition">
                        Download Transcript
                    </a>
                </div>
                <?php endif; ?>

                <!-- Bachelor Degree (for Masters/PhD) -->
                <?php if ($app['bachelor_degree']): ?>
                <div class="bg-gray-50 rounded-xl p-6 text-center border-2 border-dashed border-gray-300">
                    <p class="font-bold mb-4 text-lg">Bachelor Degree Certificate</p>
                    <i class="fas fa-file-pdf text-green-600 text-6xl mb-4"></i>
                    <a href="<?= singleDocUrl('bachelor_degree') ?>" 
                       class="block bg-green-600 text-white font-medium py-2.5 rounded-lg hover:bg-green-700 transition">
                        Download Degree
                    </a>
                </div>
                <?php endif; ?>
                
            </div>

            <!-- Full Package Download Button -->
            <div class="mt-12 text-center">
                <a href="download_documents.php?id=<?= $id ?>" 
                   class="inline-block bg-deepblue text-white font-bold text-2xl px-16 py-6 rounded-2xl hover:scale-105 transition shadow-2xl">
                    <i class="fas fa-download mr-3"></i>
                    Download Complete Application Package (PDF)
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>