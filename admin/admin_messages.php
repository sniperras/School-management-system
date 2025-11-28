<?php
// admin_messages.php - FINAL FIXED VERSION (No errors, Reply + Search + Read Status)
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$CSRF = csrf_token();
$flash = '';

// === Handle POST Actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf'] ?? '';

    if (!check_csrf($token)) {
        $flash = 'Security check failed.';
    } else {
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            $flash = 'Message deleted permanently.';
        }

        elseif ($action === 'toggle_read' && $id > 0) {
            $is_read = (int)($_POST['is_read'] ?? 0);
            $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = ?, read_at = CASE WHEN ? = 1 THEN NOW() ELSE read_at END WHERE id = ?");
            $stmt->execute([$is_read, $is_read, $id]);
            $flash = $is_read ? 'Marked as Read' : 'Marked as Unread';
        }

        elseif ($action === 'send_reply' && $id > 0) {
            $reply = trim($_POST['reply_message'] ?? '');
            if ($reply === '') {
                $flash = 'Reply message cannot be empty.';
            } else {
                $stmt = $pdo->prepare("SELECT name, email, subject FROM contact_messages WHERE id = ?");
                $stmt->execute([$id]);
                $msg = $stmt->fetch();

                if ($msg) {
                    $to = $msg['email'];
                    $subject = "RE: " . ($msg['subject'] ?: 'Your Message');
                    $message = "Hello {$msg['name']},\n\nThank you for contacting us. Here is our reply:\n\n--------------------\n" . $reply . "\n--------------------\n\nBest regards,\nSchool Administration";

                    $headers = "From: no-reply@smschool.edu.et\r\nReply-To: info@smschool.edu.et\r\nContent-Type: text/plain; charset=UTF-8";

                    if (mail($to, $subject, $message, $headers)) {
                        $pdo->prepare("UPDATE contact_messages SET is_read = 1, read_at = NOW() WHERE id = ?")->execute([$id]);
                        $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at, is_read) VALUES (?, ?, ?, ?, NOW(), 1)")
                            ->execute(['Admin Reply', 'admin@smschool.edu.et', $subject, "→ Replied to #{$id}: " . substr($reply, 0, 200) . '...']);

                        $flash = "Reply sent successfully to <strong>{$to}</strong>!";
                    } else {
                        $flash = "Failed to send email. Check server mail settings.";
                    }
                }
            }
        }
    }
}

// === Filters & Pagination ===
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// === Stats ===
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(is_read = 0) as unread,
        SUM(DATE(created_at) = CURDATE()) as today,
        SUM(WEEK(created_at) = WEEK(NOW())) as this_week
    FROM contact_messages
");
$statsStmt->execute();
$stats = $statsStmt->fetch();

// === Pagination Count ===
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_MESSAGES $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// === Fetch Messages (Always newest first) ===
$sql = "SELECT id, name, email, subject, LEFT(message, 120) as excerpt, 
               is_read, created_at, read_at
        FROM contact_messages $whereClause
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === View Message ===
$viewMessage = null;
if ($viewId = (int)($_GET['view'] ?? 0)) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$viewId]);
    $viewMessage = $stmt->fetch();

    if ($viewMessage && !$viewMessage['is_read']) {
        $pdo->prepare("UPDATE contact_messages SET is_read = 1, read_at = NOW() WHERE id = ?")->execute([$viewId]);
    }
}

// === CSV Export (FIXED: No undefined $sort/$dir) ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT id, name, email, subject, message, is_read, created_at 
            FROM contact_messages $whereClause 
            ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="contact_messages_' . date('Y-m-d_His') . '.csv"');
    $out = fopen('php://output', 'w');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    fputcsv($out, ['ID', 'Name', 'Email', 'Subject', 'Message', 'Status', 'Received At']);

    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $row[4] = strip_tags($row[4]);
        $row[5] = $row[5] ? 'Read' : 'Unread';
        fputcsv($out, $row);
    }
    exit;
}

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function url($add = []) {
    $p = $_GET;
    unset($p['view']);
    return '?' . http_build_query(array_merge($p, $add));
}
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Contact Messages | Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .unread-row { background: #fee2e2; border-left: 6px solid #ef4444; font-weight: 600; }
    .read-row   { transition: background 0.2s; }
    .read-row:hover { background: #f3f4f6; }
    .badge-new { @apply inline-block px-3 py-1 text-xs font-bold bg-red-600 text-white rounded-full animate-pulse; }
</style>

<div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <header class="bg-gradient-to-r from-deepblue to-midblue text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <h1 class="text-4xl font-bold flex items-center gap-4">
                Contact Messages
                <span class="text-2xl opacity-90">(<?= $stats['total'] ?> Total)</span>
                <?php if ($stats['unread'] > 0): ?>
                    <span class="bg-red-600 px-4 py-2 rounded-full text-lg font-bold animate-pulse">
                        <?= $stats['unread'] ?> Unread
                    </span>
                <?php endif; ?>
            </h1>
            <div class="flex gap-4">
                <a href="admin_alumni_list.php" 
   class="bg-gradient-to-r from-cyan-400 via-cyan-500 to-cyan-600 
          px-6 py-3 rounded-xl 
          hover:from-cyan-500 hover:via-cyan-600 hover:to-cyan-700 
          transition duration-300 
          flex items-center gap-2 
          shadow-lg shadow-cyan-500/50 
          border border-cyan-300 
          font-extrabold text-white tracking-wide uppercase">
   <-- Back to Admin Alumni List
</a>


                <a href="<?= url(['export' => 'csv']) ?>" class="bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700 flex items-center gap-2 font-bold">
                    Export CSV
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <?php if ($flash): ?>
            <div class="mb-8 p-6 rounded-2xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center gap-3 text-lg font-medium">
                <?= $flash ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-deepblue">
                <p class="text-gray-600">Total</p>
                <p class="text-5xl font-bold text-deepblue"><?= $stats['total'] ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-red-600">
                <p class="text-gray-600">Unread</p>
                <p class="text-5xl font-bold text-red-600"><?= $stats['unread'] ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-blue-600">
                <p class="text-gray-600">Today</p>
                <p class="text-5xl font-bold text-blue-700"><?= $stats['today'] ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-purple-600">
                <p class="text-gray-600">This Week</p>
                <p class="text-5xl font-bold text-purple-700"><?= $stats['this_week'] ?></p>
            </div>
        </div>

        <!-- Search -->
        <form method="get" class="bg-white rounded-2xl shadow-xl p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search name, email, subject, message..." 
                       class="flex-1 px-6 py-4 border-2 rounded-xl focus:border-deepblue focus:ring-4 focus:ring-blue-100 text-lg">
                <button type="submit" class="bg-deepblue text-white font-bold px-10 py-4 rounded-xl hover:bg-blue-800 transition flex items-center gap-3">
                    Search
                </button>
                <?php if ($search): ?>
                    <a href="admin_messages.php" class="px-8 py-4 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Messages Table -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-deepblue to-midblue text-white">
                        <tr>
                            <th class="px-6 py-5 text-left">Date & Time</th>
                            <th class="px-6 py-5 text-left">From</th>
                            <th class="px-6 py-5 text-left">Subject</th>
                            <th class="px-6 py-5 text-left">Preview</th>
                            <th class="px-6 py-5 text-center">Status</th>
                            <th class="px-6 py-5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-20 text-gray-500 text-2xl">
                                    No messages found
                                </td>
                            </tr>
                        <?php else: foreach ($messages as $m): ?>
                            <tr class="<?= !$m['is_read'] ? 'unread-row' : 'read-row' ?>">
                                <td class="px-6 py-5 text-sm">
                                    <?= date('M j, Y', strtotime($m['created_at'])) ?><br>
                                    <span class="text-xs text-gray-500"><?= date('g:i A', strtotime($m['created_at'])) ?></span>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="font-bold"><?= e($m['name']) ?></div>
                                    <div class="text-sm text-gray-600"><?= e($m['email']) ?></div>
                                </td>
                                <td class="px-6 py-5 font-medium text-deepblue">
                                    <?= e($m['subject'] ?: '(No subject)') ?>
                                    <?php if (!$m['is_read']): ?> <span class="badge-new ml-2">NEW</span><?php endif; ?>
                                </td>
                                <td class="px-6 py-5 text-gray-700 max-w-md truncate">
                                    <?= e($m['excerpt']) ?>...
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                        <input type="hidden" name="action" value="toggle_read">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <input type="hidden" name="is_read" value="<?= $m['is_read'] ? '0' : '1' ?>">
                                        <button class="px-5 py-2 rounded-full text-xs font-bold <?= $m['is_read'] ? 'bg-gray-300 hover:bg-gray-400' : 'bg-red-100 text-red-800 border border-red-300 hover:bg-red-200' ?>">
                                            <?= $m['is_read'] ? 'READ' : 'UNREAD' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-5 text-center space-x-3">
                                    <a href="<?= url(['view' => $m['id']]) ?>" class="bg-deepblue text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm">View</a>
                                    <button onclick="openReplyModal(<?= $m['id'] ?>, '<?= e($m['name']) ?>', '<?= e($m['email']) ?>', '<?= e(addslashes($m['subject'])) ?>')" 
                                            class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 text-sm">Reply</button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete permanently?')">
                                        <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 text-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-8 flex justify-center gap-3 flex-wrap">
                <?php if ($page > 1): ?>
                    <a href="<?= url(['page' => $page-1]) ?>" class="px-6 py-3 bg-white border-2 border-deepblue text-deepblue rounded-xl hover:bg-deepblue hover:text-white transition">Previous</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++): ?>
                    <a href="<?= url(['page' => $i]) ?>" class="px-6 py-3 rounded-xl <?= $i==$page ? 'bg-deepblue text-white' : 'bg-white border-2 border-deepblue text-deepblue hover:bg-deepblue hover:text-white' ?> transition">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= url(['page' => $page+1]) ?>" class="px-6 py-3 bg-white border-2 border-deepblue text-deepblue rounded-xl hover:bg-deepblue hover:text-white transition">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Reply Modal -->
        <div id="replyModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 p-6">
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full p-8">
                <h3 class="text-3xl font-bold text-deepblue mb-6">Reply to Message</h3>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= $CSRF ?>">
                    <input type="hidden" name="action" value="send_reply">
                    <input type="hidden" name="id" id="reply_id">
                    <div class="space-y-5">
                        <div><strong>To:</strong> <span id="reply_to" class="text-deepblue text-lg"></span></div>
                        <div><strong>Subject:</strong> <input id="reply_subject" class="w-full px-4 py-2 border rounded-lg" readonly></div>
                        <div>
                            <textarea name="reply_message" rows="10" required placeholder="Write your reply..." class="w-full px-5 py-4 border-2 rounded-xl focus:border-deepblue"></textarea>
                        </div>
                        <div class="flex justify-end gap-4">
                            <button type="button" onclick="document.getElementById('replyModal').classList.add('hidden')" class="px-8 py-3 bg-gray-300 rounded-xl hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-10 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 font-bold">Send Reply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Modal -->
        <?php if ($viewMessage): ?>
        <div class="fixed inset-0 bg-black/70 flex items-center justify-center p-6 z-50" onclick="this.remove()">
            <div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full max-h-screen overflow-y-auto p-10" onclick="event.stopPropagation()">
                <div class="flex justify-between items-start mb-8">
                    <h2 class="text-4xl font-bold text-deepblue">Message Details</h2>
                    <button onclick="this.closest('.fixed').remove()" class="text-4xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div class="space-y-3">
                        <p><strong>From:</strong> <?= e($viewMessage['name']) ?></p>
                        <p><strong>Email:</strong> <a href="mailto:<?= e($viewMessage['email']) ?>" class="text-midblue hover:underline"><?= e($viewMessage['email']) ?></a></p>
                        <p><strong>Subject:</strong> <?= e($viewMessage['subject'] ?: '(No subject)') ?></p>
                        <p><strong>Received:</strong> <?= date('F j, Y \a\t g:i A', strtotime($viewMessage['created_at'])) ?></p>
                    </div>
                    <div class="text-right">
                        <button onclick="openReplyModal(<?= $viewMessage['id'] ?>, '<?= e($viewMessage['name']) ?>', '<?= e($viewMessage['email']) ?>', '<?= e(addslashes($viewMessage['subject'])) ?>')" 
                                class="bg-green-600 text-white px-8 py-4 rounded-xl hover:bg-green-700 text-lg font-bold">
                            Reply Now
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 p-8 rounded-2xl">
                    <pre class="whitespace-pre-wrap font-sans text-lg text-gray-800"><?= e($viewMessage['message']) ?></pre>
                </div>
                <div class="mt-8 text-right">
                    <button onclick="this.closest('.fixed').remove()" class="px-10 py-4 bg-deepblue text-white rounded-xl hover:bg-blue-800 text-lg">Close</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function openReplyModal(id, name, email, subject) {
    document.getElementById('replyModal').classList.remove('hidden');
    document.getElementById('reply_id').value = id;
    document.getElementById('reply_to').textContent = name + ' <' + email + '>';
    document.getElementById('reply_subject').value = subject ? 'RE: ' + subject : 'RE: Your Message';
}
</script>
