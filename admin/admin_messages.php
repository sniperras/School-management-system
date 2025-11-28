<?php
// admin_messages.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// --- Access control ---
if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$CSRF = csrf_token();
$flash = '';

// === Handle Actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf'] ?? '';

    if (!check_csrf($token)) {
        $flash = 'Security check failed.';
    } else {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
                $stmt->execute([$id]);
                $flash = $stmt->rowCount() ? 'Message deleted.' : 'Message not found.';
            }
        } elseif ($action === 'toggle_read') {
            $id = (int)($_POST['id'] ?? 0);
            $new = ($_POST['new_state'] ?? '0') === '1' ? 1 : 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
                $stmt->execute([$new, $id]);
                $flash = 'Status updated.';
            }
        }
    }
}

// === Filters ===
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$allowedSort = ['id','created_at','name','email','subject','is_read'];

// Get requested sort or default to 'created_at'
$requestedSort = $_GET['sort'] ?? 'created_at';

// Validate against allowed list
$sort = in_array($requestedSort, $allowedSort, true) ? $requestedSort : 'created_at';

// Direction: default 'DESC', allow only 'ASC'
$dir  = (($_GET['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';


$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// === Export CSV ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT id, name, email, subject, message, is_read, created_at 
             FROM contact_messages $whereClause 
             ORDER BY $sort $dir";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="messages_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    echo "\xEF\xBB\xBF"; // BOM
    fputcsv($out, ['ID','Name','Email','Subject','Message','Read','Date']);

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $row[4] = strip_tags($row[4]); // clean message
        $row[5] = $row[5] ? 'Yes' : 'No';
        fputcsv($out, $row);
    }
    exit;
}

// === Stats ===
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(is_read = 0 OR is_read IS NULL) as unread,
        SUM(DATE(created_at) = CURDATE()) as today,
        SUM(YEARWEEK(created_at) = YEARWEEK(NOW())) as this_week
    FROM contact_messages
");
$stats->execute();
$stats = $stats->fetch();

// === Pagination ===
// === Pagination ===
$stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereClause");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();


$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// === Fetch Messages ===
$sql = "SELECT id, name, email, subject, LEFT(message, 150) as excerpt, 
               is_read, created_at, (is_read = 0 OR is_read IS NULL) as unread
        FROM contact_messages $whereClause
        ORDER BY $sort $dir
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === View Single Message ===
$viewMessage = null;
if ($viewId = (int)($_GET['view'] ?? 0)) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$viewId]);
    $viewMessage = $stmt->fetch();
    if ($viewMessage && empty($viewMessage['is_read'])) {
        $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$viewId]);
    }
}

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function build_url($overrides = []) {
    $p = array_merge($_GET, $overrides);
    unset($p['view']); // don't carry view when paginating
    return '?' . http_build_query($p);
}
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Messages | Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .status-unread { @apply bg-red-100 text-red-800 border border-red-300 font-bold; }
    .status-read   { @apply bg-gray-100 text-gray-700 border border-gray-300; }
</style>

<div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <header class="bg-deepblue text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-6 py-5 flex justify-between items-center">
            <h1 class="text-3xl font-bold flex items-center gap-4">
                <i class="fas fa-envelope"></i> Contact Messages
                <span class="text-xl opacity-90">(<?= $stats['total'] ?>)</span>
                <?php if ($stats['unread'] > 0): ?>
                    <span class="bg-red-500 px-3 py-1 rounded-full text-sm animate-pulse">
                        <?= $stats['unread'] ?> New
                    </span>
                <?php endif; ?>
            </h1>
            <div class="flex gap-3">
                <a href="admin_alumni_list.php" class="bg-white text-deepblue px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition">
                    Back to Dashboard
                </a>
                <a href="<?= build_url(['export' => 'csv']) ?>" class="bg-white text-deepblue px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition flex items-center gap-2">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="mb-8 p-5 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center gap-3">
                <i class="fas fa-bell"></i> <?= $flash ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-deepblue">
                <p class="text-gray-600 text-sm">Total Messages</p>
                <p class="text-4xl font-bold text-deepblue"><?= $stats['total'] ?></p>
            </div>
            <div class="bg-red-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-red-600">
                <p class="text-gray-600 text-sm">Unread</p>
                <p class="text-4xl font-bold text-red-700"><?= $stats['unread'] ?></p>
            </div>
            <div class="bg-blue-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-blue-600">
                <p class="text-gray-600 text-sm">Today</p>
                <p class="text-4xl font-bold text-blue-700"><?= $stats['today'] ?></p>
            </div>
            <div class="bg-purple-50 rounded-xl shadow-lg p-6 text-center border-t-4 border-purple-600">
                <p class="text-gray-600 text-sm">This Week</p>
                <p class="text-4xl font-bold text-purple-700"><?= $stats['this_week'] ?></p>
            </div>
        </div>

        <!-- Search Bar -->
        <form method="get" class="bg-white rounded-2xl shadow-lg p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <input type="text" name="q" value="<?= e($search) ?>" 
                       placeholder="Search name, email, subject, message..." 
                       class="flex-1 px-5 py-4 border-2 border-gray-300 rounded-xl focus:border-deepblue focus:ring-4 focus:ring-blue-100 transition">
                <button type="submit" class="bg-deepblue text-white font-bold px-8 py-4 rounded-xl hover:bg-blue-800 transition flex items-center gap-3">
                    <i class="fas fa-search"></i> Search Messages
                </button>
            </div>
        </form>

        <!-- Messages Table -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-deepblue text-white">
                        <tr>
                            <th class="px-6 py-4 text-left">Date</th>
                            <th class="px-6 py-4 text-left">From</th>
                            <th class="px-6 py-4 text-left">Subject</th>
                            <th class="px-6 py-4 text-left">Message</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-16 text-gray-500 text-xl">
                                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i><br>
                                    No messages found
                                </td>
                            </tr>
                        <?php else: foreach ($messages as $m): ?>
                            <tr class="<?= $m['unread'] ? 'bg-red-50 font-medium' : 'hover:bg-gray-50' ?> transition">
                                <td class="px-6 py-5 text-sm text-gray-600">
                                    <?= date('M j, Y', strtotime($m['created_at'])) ?><br>
                                    <span class="text-xs"><?= date('g:i A', strtotime($m['created_at'])) ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="font-semibold"><?= e($m['name']) ?></div>
                                    <div class="text-sm text-gray-600"><?= e($m['email']) ?></div>
                                    <?php if ($m['unread']): ?>
                                        <span class="inline-block mt-1 px-2 py-1 text-xs bg-red-600 text-white rounded-full">NEW</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-5 font-medium text-deepblue">
                                    <?= e($m['subject'] ?: '(No subject)') ?>
                                </td>
                                <td class="px-6 py-5 text-gray-700 max-w-lg">
                                    <?= e($m['excerpt']) ?>...
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                        <input type="hidden" name="action" value="toggle_read">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <input type="hidden" name="new_state" value="<?= $m['unread'] ? '1' : '0' ?>">
                                        <button class="px-4 py-2 rounded-full text-xs font-bold <?= $m['unread'] ? 'status-unread' : 'status-read' ?>">
                                            <?= $m['unread'] ? 'UNREAD' : 'READ' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-5 text-center space-x-3">
                                    <a href="<?= build_url(['view' => $m['id']]) ?>" 
                                       class="bg-deepblue text-white px-5 py-2 rounded-lg hover:bg-blue-800 text-sm transition">
                                        View
                                    </a>
                                    <a href="mailto:<?= urlencode($m['email']) ?>?subject=RE: <?= urlencode($m['subject'] ?: 'Your Message') ?>"
                                       class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 text-sm transition">
                                        Reply
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete permanently?')">
                                        <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 text-sm">
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
                <a href="<?= build_url(['page' => $page-1]) ?>" class="px-5 py-3 bg-white border rounded-xl hover:bg-gray-100">Previous</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++): ?>
                <a href="<?= build_url(['page' => $i]) ?>" 
                   class="px-5 py-3 rounded-xl <?= $i==$page ? 'bg-deepblue text-white' : 'bg-white border hover:bg-gray-100' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= build_url(['page' => $page+1]) ?>" class="px-5 py-3 bg-white border rounded-xl hover:bg-gray-100">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- View Full Message Modal-Style Panel -->
        <?php if ($viewMessage): ?>
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-6 z-50" onclick="this.remove()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-screen overflow-y-auto" onclick="event.stopPropagation()">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-3xl font-bold text-deepblue">Message from <?= e($viewMessage['name']) ?></h2>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">×</button>
                    </div>
                    <div class="grid md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <p><strong>Name:</strong> <?= e($viewMessage['name']) ?></p>
                            <p><strong>Email:</strong> <a href="mailto:<?= e($viewMessage['email']) ?>" class="text-deepblue hover:underline"><?= e($viewMessage['email']) ?></a></p>
                            <p><strong>Subject:</strong> <?= e($viewMessage['subject'] ?: '(No subject)') ?></p>
                            <p><strong>Received:</strong> <?= date('M j, Y g:i A', strtotime($viewMessage['created_at'])) ?></p>
                        </div>
                        <div class="text-right">
                            <a href="mailto:<?= urlencode($viewMessage['email']) ?>?subject=RE: <?= urlencode($viewMessage['subject'] ?: 'Your Message') ?>"
                               class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 mb-3">
                                Reply via Email
                            </a>
                            <form method="POST" class="inline-block">
                                <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $viewMessage['id'] ?>">
                                <button class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
                                    Delete Message
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <pre class="whitespace-pre-wrap font-sans text-gray-800 leading-relaxed"><?= e($viewMessage['message']) ?></pre>
                    </div>
                    <div class="mt-6 text-right">
                        <button onclick="this.closest('.fixed').remove()" class="bg-gray-300 px-8 py-3 rounded-lg hover:bg-gray-400">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>