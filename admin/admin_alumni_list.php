<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$CSRF_TOKEN = csrf_token();
$flash = '';

// === Handle Actions (Delete + Edit) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['_csrf'] ?? '';

    if (!check_csrf($token)) {
        $flash = 'Security check failed.';
    } else {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM alumni WHERE id = ?");
                $stmt->execute([$id]);
                $flash = 'Alumni deleted successfully.';
            }
        }

        elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $fields = [
                    'official_id'      => trim($_POST['official_id'] ?? ''),
                    'first_name'       => trim($_POST['first_name'] ?? ''),
                    'last_name'        => trim($_POST['last_name'] ?? ''),
                    'email'            => trim($_POST['email'] ?? ''),
                    'phone'            => trim($_POST['phone'] ?? ''),
                    'graduation_year'  => (int)($_POST['graduation_year'] ?? 0),
                    'program'          => trim($_POST['program'] ?? ''),
                    'occupation'       => trim($_POST['occupation'] ?? ''),
                    'employer'         => trim($_POST['employer'] ?? ''),
                    'achievements'     => trim($_POST['achievements'] ?? '')
                ];

                $sql = "UPDATE alumni SET " . implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields))) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([...array_values($fields), $id]);

                $flash = 'Alumni record updated successfully!';
            }
        }
    }
}

// === Filters & Pagination (unchanged) ===
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

// === Export CSV (fixed) ===
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

// === Stats & Data Fetch (same as before) ===
$statsQuery = $pdo->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT graduation_year) as years, COUNT(DISTINCT program) as programs FROM alumni");
$statsQuery->execute();
$stats = $statsQuery->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM alumni $whereClause");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM alumni $whereClause ORDER BY $sort $dir LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function url($add = []) {
    $p = $_GET;
    return '?' . http_build_query(array_merge($p, $add));
}
?>

<?php require_once __DIR__ . '/../includes/head.php'; ?>
<title>Alumni Management | Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .modal { transition: all 0.3s; }
</style>

<div class="min-h-screen bg-gray-50">

    <!-- Header -->
    <header class="bg-gradient-to-r from-deepblue to-midblue text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
            <h1 class="text-4xl font-bold flex items-center gap-4">
                Alumni Management
                <span class="text-2xl opacity-90">(<?= $stats['total'] ?> Total)</span>
            </h1>
            <div class="flex gap-4">
               <a href="admin_messages.php" 
   class="bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-600 
          px-6 py-3 rounded-xl 
          hover:from-yellow-500 hover:via-yellow-600 hover:to-yellow-700 
          transition duration-300 
          flex items-center gap-2 
          shadow-lg font-bold text-white tracking-wide">
   Messages

                    <?php if (($unread = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn()) > 0): ?>
                        <span class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url(['export' => 'csv']) ?>" class="bg-green-600 text-white px-6 py-3 rounded-xl hover:bg-green-700 font-bold">Export CSV</a>
                <a href="dashboard.php" class="bg-white/20 px-6 py-3 rounded-xl hover:bg-white/30 transition">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <?php if ($flash): ?>
            <div class="mb-8 p-6 rounded-2xl bg-green-100 border border-green-300 text-green-800 flex items-center gap-3 text-lg font-medium">
                <?= $flash ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-deepblue">
                <p class="text-gray-600">Total Alumni</p>
                <p class="text-5xl font-bold text-deepblue"><?= $stats['total'] ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-blue-600">
                <p class="text-gray-600">Grad Years</p>
                <p class="text-5xl font-bold text-blue-700"><?= $stats['years'] ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-purple-600">
                <p class="text-gray-600">Programs</p>
                <p class="text-5xl font-bold text-purple-700"><?= $stats['programs'] ?></p>
            </div>
            <div class="bg-white rounded-2xl shadow-xl p-6 text-center border-t-8 border-green-600">
                <p class="text-gray-600">Active</p>
                <p class="text-5xl font-bold text-green-700"><?= $stats['total'] ?></p>
            </div>
        </div>

        <!-- Search -->
        <form method="get" class="bg-white rounded-2xl shadow-xl p-6 mb-8">
            <div class="flex gap-4">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search alumni..." class="flex-1 px-6 py-4 border-2 rounded-xl focus:border-deepblue">
                <button class="bg-deepblue text-white px-10 py-4 rounded-xl hover:bg-blue-800 font-bold">Search</button>
                <?php if ($search): ?><a href="?" class="px-8 py-4 bg-gray-300 rounded-xl hover:bg-gray-400">Clear</a><?php endif; ?>
            </div>
        </form>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-deepblue to-midblue text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">#</th>
                        <th class="px-6 py-5 text-left">Official ID</th>
                        <th class="px-6 py-5 text-left">Name</th>
                        <th class="px-6 py-5 text-left">Contact</th>
                        <th class="px-6 py-5 text-left">Grad Year</th>
                        <th class="px-6 py-5 text-left">Program</th>
                        <th class="px-6 py-5 text-left">Added</th>
                        <th class="px-6 py-5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center py-20 text-2xl text-gray-400">No alumni found</td></tr>
                    <?php else: foreach ($rows as $i => $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-5"><?= $offset + $i + 1 ?></td>
                            <td class="px-6 py-5 font-mono font-bold text-deepblue"><?= e($r['official_id'] ?: '—') ?></td>
                            <td class="px-6 py-5 font-semibold"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td class="px-6 py-5 text-sm">
                                <?= e($r['email']) ?><br>
                                <span class="text-gray-500"><?= e($r['phone'] ?: '—') ?></span>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="px-4 py-2 bg-midblue text-white rounded-full font-bold"><?= e($r['graduation_year']) ?></span>
                            </td>
                            <td class="px-6 py-5"><?= e($r['program']) ?></td>
                            <td class="px-6 py-5 text-sm text-gray-600">
                                <?= date('M j, Y', strtotime($r['created_at'])) ?>
                            </td>
                            <td class="px-6 py-5 text-center space-x-2">
                                <button onclick="viewAlumni(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" 
                                        class="bg-deepblue text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm">View</button>
                                <button onclick="editAlumni(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" 
                                        class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 text-sm">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete permanently?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="_csrf" value="<?= $CSRF_TOKEN ?>">
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
        <div class="mt-10 flex justify-center gap-3 flex-wrap">
            <?php if ($page > 1): ?>
                <a href="<?= url(['page' => $page-1]) ?>" class="px-6 py-3 bg-white border-2 border-deepblue text-deepblue rounded-xl hover:bg-deepblue hover:text-white">Previous</a>
            <?php endif; ?>
            <?php for ($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++): ?>
                <a href="<?= url(['page' => $i]) ?>" class="px-6 py-3 rounded-xl <?= $i==$page ? 'bg-deepblue text-white' : 'bg-white border-2 border-deepblue text-deepblue hover:bg-deepblue hover:text-white' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= url(['page' => $page+1]) ?>" class="px-6 py-3 bg-white border-2 border-deepblue text-deepblue rounded-xl hover:bg-deepblue hover:text-white">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- VIEW MODAL -->
<div id="viewModal" class="fixed inset-0 bg-black/70 hidden flex items-center justify-center z-50 p-6">
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-screen overflow-y-auto p-10">
        <h2 class="text-4xl font-bold text-deepblue mb-8" id="viewName"></h2>
        <div class="grid md:grid-cols-2 gap-8 text-lg">
            <div><strong>Official ID:</strong> <span id="viewId"></span></div>
            <div><strong>Email:</strong> <span id="viewEmail"></span></div>
            <div><strong>Phone:</strong> <span id="viewPhone"></span></div>
            <div><strong>Graduation Year:</strong> <span id="viewYear"></span></div>
            <div><strong>Program:</strong> <span id="viewProgram"></span></div>
            <div><strong>Occupation:</strong> <span id="viewOccupation"></span></div>
            <div><strong>Employer:</strong> <span id="viewEmployer"></span></div>
            <div class="md:col-span-2"><strong>Achievements:</strong> <p id="viewAchievements" class="mt-2 bg-gray-50 p-4 rounded-xl"></p></div>
        </div>
        <div class="mt-10 text-right">
            <button onclick="document.getElementById('viewModal').classList.add('hidden')" class="px-10 py-4 bg-deepblue text-white rounded-xl hover:bg-blue-800 text-lg">Close</button>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 p-6">
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-screen overflow-y-auto p-10">
        <h2 class="text-4xl font-bold text-deepblue mb-8">Edit Alumni Record</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="_csrf" value="<?= $CSRF_TOKEN ?>">
            <input type="hidden" name="id" id="editId">

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <input type="text" name="official_id" id="editOfficialId" placeholder="Official ID" class="px-5 py-4 border-2 rounded-xl" required>
                <input type="text" name="first_name" id="editFirstName" placeholder="First Name" class="px-5 py-4 border-2 rounded-xl" required>
                <input type="text" name="last_name" id="editLastName" placeholder="Last Name" class="px-5 py-4 border-2 rounded-xl" required>
                <input type="email" name="email" id="editEmail" placeholder="Email" class="px-5 py-4 border-2 rounded-xl" required>
                <input type="text" name="phone" id="editPhone" placeholder="Phone" class="px-5 py-4 border-2 rounded-xl">
                <input type="number" name="graduation_year" id="editYear" placeholder="Graduation Year" class="px-5 py-4 border-2 rounded-xl" required>
                <input type="text" name="program" id="editProgram" placeholder="Program" class="px-5 py-4 border-2 rounded-xl" required>
                <input type="text" name="occupation" id="editOccupation" placeholder="Current Job" class="px-5 py-4 border-2 rounded-xl">
                <input type="text" name="employer" id="editEmployer" placeholder="Employer" class="px-5 py-4 border-2 rounded-xl">
                <div class="md:col-span-2">
                    <textarea name="achievements" id="editAchievements" rows="4" placeholder="Achievements & Notes" class="w-full px-5 py-4 border-2 rounded-xl"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-8 py-3 bg-gray-300 rounded-xl hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-10 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// View Modal
function viewAlumni(data) {
    document.getElementById('viewName').textContent = data.first_name + ' ' + data.last_name;
    document.getElementById('viewId').textContent = data.official_id || '—';
    document.getElementById('viewEmail').textContent = data.email;
    document.getElementById('viewPhone').textContent = data.phone || '—';
    document.getElementById('viewYear').textContent = data.graduation_year;
    document.getElementById('viewProgram').textContent = data.program;
    document.getElementById('viewOccupation').textContent = data.occupation || '—';
    document.getElementById('viewEmployer').textContent = data.employer || '—';
    document.getElementById('viewAchievements').textContent = data.achievements || 'No achievements recorded.';
    document.getElementById('viewModal').classList.remove('hidden');
}

// Edit Modal
function editAlumni(data) {
    document.getElementById('editId').value = data.id;
    document.getElementById('editOfficialId').value = data.official_id || '';
    document.getElementById('editFirstName').value = data.first_name;
    document.getElementById('editLastName').value = data.last_name;
    document.getElementById('editEmail').value = data.email;
    document.getElementById('editPhone').value = data.phone || '';
    document.getElementById('editYear').value = data.graduation_year;
    document.getElementById('editProgram').value = data.program;
    document.getElementById('editOccupation').value = data.occupation || '';
    document.getElementById('editEmployer').value = data.employer || '';
    document.getElementById('editAchievements').value = data.achievements || '';
    document.getElementById('editModal').classList.remove('hidden');
}

// Close modals when clicking outside
document.querySelectorAll('#viewModal, #editModal').forEach(m => {
    m.addEventListener('click', e => {
        if (e.target === m) m.classList.add('hidden');
    });
});
</script>