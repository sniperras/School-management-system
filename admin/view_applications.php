<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admin
if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// === Handle Accept/Reject Actions (AJAX) ===
if (isset($_POST['action']) && isset($_POST['app_id'])) {
    $app_id = (int)$_POST['app_id'];
    $action = $_POST['action']; // 'accept' or 'reject'

    if (in_array($action, ['accept', 'reject'])) {
        $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $app_id]);
        echo json_encode(['success' => true, 'status' => $newStatus]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Search & Filter
$search   = trim($_GET['search'] ?? '');
$program  = $_GET['program'] ?? '';
$status   = $_GET['status'] ?? 'all';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(application_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}
if ($program !== '') {
    $where[] = "program = ?";
    $params[] = $program;
}
if ($status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM applications $whereClause ORDER BY applied_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN program = 'Bachelor' THEN 1 ELSE 0 END) as bachelor,
        SUM(CASE WHEN program = 'Masters' THEN 1 ELSE 0 END) as masters,
        SUM(CASE WHEN program = 'PhD' THEN 1 ELSE 0 END) as phd,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications
")->fetch();
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>View Applications | Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .status-pending   { @apply bg-yellow-100 text-yellow-800 border border-yellow-300; }
    .status-accepted  { @apply bg-green-100 text-green-800 border border-green-300; }
    .status-rejected  { @apply bg-red-100 text-red-800 border border-red-300; }
</style>

<div class="min-h-screen bg-gray-50">
    <header class="bg-deepblue text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <h1 class="text-3xl font-bold">All Applications (<?= $stats['total'] ?? 0 ?>)</h1>
            <a href="dashboard.php" class="bg-white text-deepblue px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
                Back to Dashboard
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6 mb-10">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-deepblue">
                <p class="text-gray-600 text-sm">Total</p>
                <p class="text-4xl font-bold text-deepblue"><?= $stats['total'] ?? 0 ?></p>
            </div>
            <div class="bg-yellow-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-yellow-500">
                <p class="text-gray-600 text-sm">Pending</p>
                <p class="text-4xl font-bold text-yellow-700"><?= $stats['pending'] ?? 0 ?></p>
            </div>
            <div class="bg-green-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-green-600">
                <p class="text-gray-600 text-sm">Accepted</p>
                <p class="text-4xl font-bold text-green-700"><?= $stats['accepted'] ?? 0 ?></p>
            </div>
            <div class="bg-red-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-red-600">
                <p class="text-gray-600 text-sm">Rejected</p>
                <p class="text-4xl font-bold text-red-700"><?= $stats['rejected'] ?? 0 ?></p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-blue-600">
                <p class="text-gray-600 text-sm">Bachelor's</p>
                <p class="text-3xl font-bold text-blue-700"><?= $stats['bachelor'] ?? 0 ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-purple-600">
                <p class="text-gray-600 text-sm">Master's/PhD</p>
                <p class="text-3xl font-bold text-purple-700"><?= ($stats['masters'] ?? 0) + ($stats['phd'] ?? 0) ?></p>
            </div>
        </div>

        <!-- Search & Filters -->
        <form method="get" class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Name, ID, Phone..." class="px-4 py-3 border rounded-lg focus:ring-2 focus:ring-deepblue">
                
                <select name="program" class="px-4 py-3 border rounded-lg focus:ring-2 focus:ring-deepblue">
                    <option value="">All Programs</option>
                    <option value="Bachelor" <?= $program==='Bachelor'?'selected':'' ?>>Bachelor's</option>
                    <option value="Masters" <?= $program==='Masters'?'selected':'' ?>>Master's</option>
                    <option value="PhD" <?= $program==='PhD'?'selected':'' ?>>PhD</option>
                </select>

                <select name="status" class="px-4 py-3 border rounded-lg focus:ring-2 focus:ring-deepblue">
                    <option value="all">All Status</option>
                    <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending</option>
                    <option value="accepted" <?= $status==='accepted'?'selected':'' ?>>Accepted</option>
                    <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
                </select>

                <button type="submit" class="bg-deepblue text-white font-bold py-3 rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
            </div>
        </form>

        <!-- Applications Table -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-deepblue text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">App ID</th>
                            <th class="px-6 py-4 text-left">Name</th>
                            <th class="px-6 py-4 text-left">Program</th>
                            <th class="px-6 py-4 text-left">Status</th>
                            <th class="px-6 py-4 text-left">Applied On</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($applications)): ?>
                            <tr><td colspan="6" class="text-center py-16 text-gray-500 text-xl">No applications found</td></tr>
                        <?php else: foreach ($applications as $app): 
                            $currentStatus = $app['status'] ?? 'pending';
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-5 font-mono font-bold text-deepblue">
                                    <?= htmlspecialchars($app['application_id']) ?>
                                </td>
                                <td class="px-6 py-5 font-semibold">
                                    <?= htmlspecialchars($app['first_name'] . ' ' . ($app['middle_name'] ? $app['middle_name'].' ' : '') . $app['last_name']) ?>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        <?= $app['program']=='Bachelor' ? 'bg-blue-100 text-blue-800' : 
                                           ($app['program']=='Masters' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800') ?>">
                                        <?= $app['program'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="px-4 py-2 rounded-full text-xs font-bold status-<?= $currentStatus ?>">
                                        <?= ucfirst($currentStatus) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-sm text-gray-600">
                                    <?= date('M j, Y g:i A', strtotime($app['applied_at'])) ?>
                                </td>
                                <td class="px-6 py-5 text-center space-x-2">
                                    <a href="view_single_application.php?id=<?= $app['id'] ?>" 
                                       class="bg-deepblue text-white px-4 py-2 rounded-lg hover:bg-blue-800 text-sm transition">
                                       View
                                    </a>
                                    <a href="download_documents.php?id=<?= $app['id'] ?>" 
                                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm transition">
                                       Download
                                    </a>

                                    <?php if ($currentStatus === 'pending'): ?>
                                        <button onclick="updateStatus(<?= $app['id'] ?>, 'accept')" 
                                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm transition">
                                            Accept
                                        </button>
                                        <button onclick="updateStatus(<?= $app['id'] ?>, 'reject')" 
                                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm transition">
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-xs">Finalized</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- AJAX Script for Accept/Reject -->
<script>
function updateStatus(appId, action) {
    if (!confirm(`Are you sure you want to ${action} this application?`)) return;

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=' + action + '&app_id=' + appId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating status');
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>