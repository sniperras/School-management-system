<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// --- Admin only ---
if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$CSRF_TOKEN = csrf_token();
$flash = '';

// === Delete Handler ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $token = $_POST['_csrf'] ?? '';
    if (!check_csrf($token)) {
        $flash = 'Security check failed.';
        
    } elseif ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM alumni WHERE id = ?");
        $stmt->execute([$id]);
        $flash = $stmt->rowCount() ? 'Alumni record deleted.' : 'Record not found.';
    }
}

// === Filters & Pagination ===
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$allowedSort = ['id','official_id','first_name','last_name','email','graduation_year','program','created_at'];
$sort = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'id';
$dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(official_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR program LIKE ?)";
    $like = "%$search%";
    $params = array_fill(0, 6, $like);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// === Export CSV ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT id, official_id, first_name, last_name, email, phone, graduation_year, program, occupation, employer, achievements, created_at 
            FROM alumni $whereClause ORDER BY $sort $dir";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="alumni_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    echo "\xEF\xBB\xBF";
    fputcsv($out, ['ID','Official ID','First Name','Last Name','Email','Phone','Grad Year','Program','Occupation','Employer','Achievements','Created At']);

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($out, $row);
    }
    exit;
}

// === Stats ===
$statsQuery = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT graduation_year) as years,
        COUNT(DISTINCT program) as programs
    FROM alumni
");
$statsQuery->execute();
$stats = $statsQuery->fetch();

// === Total & Pagination ===
$stmt = $pdo->prepare("SELECT COUNT(*) FROM alumni $whereClause");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$totalPages = max(1, ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page > 0) ? ($page - 1) * $perPage : 0;


// === Fetch Alumni ===
$sql = "SELECT id, official_id, first_name, last_name, email, phone, graduation_year, program, occupation, employer, created_at
        FROM alumni $whereClause
        ORDER BY $sort $dir
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function build_url($overrides = []) {
    $p = array_merge($_GET, $overrides);
    return '?' . http_build_query($p);
}
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Alumni List | Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .status-pending   { @apply bg-yellow-100 text-yellow-800 border border-yellow-300; }
    .status-accepted  { @apply bg-green-100 text-green-800 border border-green-300; }
    .status-rejected  { @apply bg-red-100 text-red-800 border border-red-300; }
</style>

<div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <header class="bg-deepblue text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <h1 class="text-3xl font-bold flex items-center gap-4">
                <i class="fas fa-graduation-cap"></i> Alumni Management
                <span class="text-xl opacity-90">(<?= $stats['total'] ?>)</span>
            </h1>
            <div class="flex gap-3">
                <!-- Messages Button with Unread Badge -->
                <?php
                try {
                    $unread = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_at IS NULL")->fetchColumn();
                } catch (Exception $e) {
                    $unread = 0;
                }
                ?>
                <a href="admin_messages.php" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-bold flex items-center gap-2 relative">
                    <i class="fas fa-envelope"></i> Messages
                    <?php if ($unread > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-6 w-6 flex items-center justify-center animate-pulse">
                            <?= $unread ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a href="<?= build_url(['export' => 'csv']) ?>" class="bg-white text-deepblue px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition flex items-center gap-2">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>

                <a href="dashboard.php" class="bg-white text-deepblue px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="mb-8 p-5 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center gap-3 shadow-md">
                <i class="fas fa-bell"></i> <?= $flash ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-deepblue">
                <p class="text-gray-600 text-sm">Total Alumni</p>
                <p class="text-4xl font-bold text-deepblue"><?= $stats['total'] ?></p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-blue-600">
                <p class="text-gray-600 text-sm">Graduation Years</p>
                <p class="text-4xl font-bold text-blue-700"><?= $stats['years'] ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-purple-600">
                <p class="text-gray-600 text-sm">Programs</p>
                <p class="text-4xl font-bold text-purple-700"><?= $stats['programs'] ?></p>
            </div>
            <div class="bg-green-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-green-600">
                <p class="text-gray-600 text-sm">Active Records</p>
                <p class="text-4xl font-bold text-green-700"><?= number_format($stats['total']) ?></p>
            </div>
        </div>

        <!-- Search Bar -->
        <form method="get" class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <input type="text" name="q" value="<?= e($search) ?>" 
                       placeholder="Search by name, ID, email, phone, program..." 
                       class="flex-1 px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-deepblue focus:ring-4 focus:ring-blue-100 transition text-lg">
                <button type="submit" class="bg-deepblue text-white font-bold px-8 py-4 rounded-xl hover:bg-blue-800 transition flex items-center gap-3">
                    <i class="fas fa-search"></i> Search Alumni
                </button>
            </div>
        </form>

        <!-- Alumni Table -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-deepblue text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">#</th>
                            <th class="px-6 py-4 text-left">Official ID</th>
                            <th class="px-6 py-4 text-left">Full Name</th>
                            <th class="px-6 py-4 text-left">Contact</th>
                            <th class="px-6 py-4 text-left">Grad Year</th>
                            <th class="px-6 py-4 text-left">Program</th>
                            <th class="px-6 py-4 text-left">Added</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-16 text-gray-500 text-xl">
                                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i><br>
                                    No alumni found
                                </td>
                            </tr>
                        <?php else: foreach ($rows as $idx => $r): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-5 text-sm font-medium"><?= $offset + $idx + 1 ?></td>
                                <td class="px-6 py-5 font-mono text-deepblue font-bold">
                                    <?= e($r['official_id'] ?: '—') ?>
                                </td>
                                <td class="px-6 py-5 font-semibold">
                                    <?= e($r['first_name'] . ' ' . $r['last_name']) ?>
                                </td>
                                <td class="px-6 py-5 text-sm">
                                    <?= e($r['email']) ?><br>
                                    <span class="text-gray-500"><?= e($r['phone'] ?: '—') ?></span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="px-4 py-2 bg-midblue text-white rounded-full text-sm font-bold">
                                        <?= e($r['graduation_year']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5"><?= e($r['program']) ?></td>
                                <td class="px-6 py-5 text-sm text-gray-600">
                                    <?= $r['created_at'] ? date('M j, Y', strtotime($r['created_at'])) : '—' ?>
                                </td>
                                <td class="px-6 py-5 text-center space-x-3">
                                    <a href="admin_alumni_view.php?id=<?= e($r['id']) ?>" 
                                       class="bg-deepblue text-white px-5 py-2 rounded-lg hover:bg-blue-800 text-sm transition">
                                        View
                                    </a>
                                    <a href="alumni_edit.php?id=<?= e($r['id']) ?>" 
                                       class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 text-sm transition">
                                        Edit
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this alumni permanently?')">
                                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="_csrf" value="<?= e($CSRF_TOKEN) ?>">
                                        <button class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 text-sm transition">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-10 flex justify-center gap-2">
            <?php if ($page > 1): ?>
                <a href="<?= build_url(['page' => $page-1]) ?>" class="px-6 py-3 bg-white border rounded-xl hover:bg-gray-100">Previous</a>
            <?php endif; ?>

            <?php for ($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++): ?>
                <a href="<?= build_url(['page' => $i]) ?>"
                   class="px-5 py-3 rounded-xl font-medium <?= $i==$page ? 'bg-deepblue text-white' : 'bg-white border hover:bg-gray-100' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= build_url(['page' => $page+1]) ?>" class="px-6 py-3 bg-white border rounded-xl hover:bg-gray-100">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>