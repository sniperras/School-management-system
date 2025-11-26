<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in() || current_user_role() !== 'admin') {
    header("Location: ../login.php"); exit;
}

$message = '';

// ---------- Helper: Log actions to system_logs ----------
function log_action($pdo, $user_id, $action) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action, $ip]);
    } catch (Exception $e) {
        // Fail silently — logging should not break features
    }
}

// ============== SECURITY: CHANGE ADMIN PASSWORD ==============
if (isset($_POST['change_password'])) {
    $current = $_POST['current_pass'];
    $new     = $_POST['new_pass'];
    $confirm = $_POST['confirm_pass'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$_SESSION['user_id']]);
    $hash = $stmt->fetchColumn();

    if (password_verify($current, $hash) && $new === $confirm && strlen($new) >= 8) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $_SESSION['user_id']]);
        $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">Password changed successfully!</div>';
        log_action($pdo, $_SESSION['user_id'], 'Changed admin password');
    } else {
        $message = '<div class="bg-red-100 border border-red-400 text-red-800 p-4 rounded-xl">Invalid current password or passwords do not match (min 8 chars).</div>';
    }
}

// ============== 2FA TOGGLE ==============
if (isset($_POST['toggle_2fa'])) {
    $enabled = $_POST['2fa_enabled'] ?? '0';
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('2fa_enabled', ?) 
                   ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$enabled, $enabled]);
    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">2FA setting updated!</div>';
    log_action($pdo, $_SESSION['user_id'], 'Toggled 2FA to ' . $enabled);
}

// ============== NOTIFICATION SETTINGS ==============
if (isset($_POST['save_notifications'])) {
    $email_host = $_POST['email_host'] ?? '';
    $email_user = $_POST['email_user'] ?? '';
    $email_pass = $_POST['email_pass'] ?? '';
    $sms_api    = $_POST['sms_api_key'] ?? '';

    // Upsert settings individually to avoid the VALUES(...) trick complexity
    $up = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $up->execute(['email_host', $email_host, $email_host]);
    $up->execute(['email_user', $email_user, $email_user]);
    if (!empty($email_pass)) {
        $up->execute(['email_pass', password_hash($email_pass, PASSWORD_DEFAULT), password_hash($email_pass, PASSWORD_DEFAULT)]);
    }
    $up->execute(['sms_api_key', $sms_api, $sms_api]);

    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">Notification settings saved!</div>';
    log_action($pdo, $_SESSION['user_id'], 'Updated notification settings');
}

// ============== FULL PHPMYADMIN-STYLE BACKUP ==============
if (isset($_POST['full_backup'])) {
    function clean($string) {
        return preg_replace('/[^A-Za-z0-9\-]/', '_', $string);
    }

    $tables         = [];
    $result         = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    while ($row = $result->fetch(PDO::FETCH_NUM)) $tables[] = $row[0];

    $return = "-- ============================================================\n";
    $return .= "-- SMS Database Backup\n";
    $return .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $return .= "-- Host: " . $_SERVER['HTTP_HOST'] . " | PHP: " . phpversion() . "\n";
    $return .= "-- Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    $return .= "-- ============================================================\n\n";

    $return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $return .= "SET time_zone = \"+00:00\";\n\n";
    $return .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
    $return .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
    $return .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
    $return .= "/*!40101 SET NAMES utf8mb4 */;\n\n";

    foreach ($tables as $table) {
        $return .= "--\n-- Table structure for `$table`\n--\n\n";
        $return .= "DROP TABLE IF EXISTS `$table`;\n";

        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $return .= $create[1] . ";\n\n";

        $result_data = $pdo->query("SELECT * FROM `$table`");
        $num_fields  = $result_data->columnCount();

        while ($row = $result_data->fetch(PDO::FETCH_NUM)) {
            $return .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = $row[$j] === null ? 'NULL' : $pdo->quote($row[$j]);
                $return .= $row[$j];
                if ($j < ($num_fields - 1)) $return .= ',';
            }
            $return .= ");\n";
        }
        $return .= "\n\n";
    }

    $return .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

    $filename = "SMS_FULL_BACKUP_" . date('Y-m-d_H-i-s') . ".sql";
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $return;
    log_action($pdo, $_SESSION['user_id'], 'Exported full backup');
    exit;
}

// ============== FETCH SETTINGS ==============
function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/* ===========================
   NEW FEATURES ADDED BELOW
   =========================== */

/* --- 1) School Info (name, motto, contact, logo) --- */
if (isset($_POST['save_school_info'])) {
    $name    = trim($_POST['school_name'] ?? '');
    $motto   = trim($_POST['school_motto'] ?? '');
    $contact = trim($_POST['school_contact'] ?? '');

    $up = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $up->execute(['school_name', $name, $name]);
    $up->execute(['school_motto', $motto, $motto]);
    $up->execute(['school_contact', $contact, $contact]);

    // Logo upload
    if (!empty($_FILES['school_logo']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/logos/';
        if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
        $allowed = ['image/png','image/jpeg','image/webp','image/svg+xml'];
        $file = $_FILES['school_logo'];
        if ($file['error'] === UPLOAD_ERR_OK && in_array(mime_content_type($file['tmp_name']), $allowed)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'school_logo_' . time() . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // store relative path
                $rel = 'uploads/logos/' . $filename;
                $up->execute(['school_logo', $rel, $rel]);
                $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">School info and logo saved!</div>';
                log_action($pdo, $_SESSION['user_id'], 'Updated school info and logo');
            } else {
                $message = '<div class="bg-red-100 border border-red-400 text-red-800 p-4 rounded-xl">Failed to move uploaded logo.</div>';
            }
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-800 p-4 rounded-xl">Invalid logo type. Allowed: PNG, JPG, WEBP, SVG.</div>';
        }
    } else {
        if (empty($message)) $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">School info saved!</div>';
        log_action($pdo, $_SESSION['user_id'], 'Updated school info (no logo)');
    }
}

/* --- 2) Academic Year / Terms management --- */
/* Expected table (create if not exists):
CREATE TABLE IF NOT EXISTS academic_years (
  id INT AUTO_INCREMENT PRIMARY KEY,
  year_label VARCHAR(50) NOT NULL,
  term1_start DATE NULL,
  term1_end DATE NULL,
  term2_start DATE NULL,
  term2_end DATE NULL,
  active TINYINT(1) DEFAULT 0
);
*/
if (isset($_POST['add_academic_year'])) {
    $label = trim($_POST['year_label'] ?? '');
    $t1s = $_POST['term1_start'] ?: null;
    $t1e = $_POST['term1_end'] ?: null;
    $t2s = $_POST['term2_start'] ?: null;
    $t2e = $_POST['term2_end'] ?: null;
    $act = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO academic_years (year_label, term1_start, term1_end, term2_start, term2_end, active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$label, $t1s, $t1e, $t2s, $t2e, $act]);

    if ($act) {
        // ensure only one active
        $id = $pdo->lastInsertId();
        $pdo->prepare("UPDATE academic_years SET active = 0 WHERE id != ?")->execute([$id]);
    }

    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">Academic year added.</div>';
    log_action($pdo, $_SESSION['user_id'], 'Added academic year: ' . $label);
}

if (isset($_POST['update_academic_year'])) {
    $id = (int)($_POST['ay_id'] ?? 0);
    $label = trim($_POST['year_label'] ?? '');
    $t1s = $_POST['term1_start'] ?: null;
    $t1e = $_POST['term1_end'] ?: null;
    $t2s = $_POST['term2_start'] ?: null;
    $t2e = $_POST['term2_end'] ?: null;
    $act = isset($_POST['active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE academic_years SET year_label=?, term1_start=?, term1_end=?, term2_start=?, term2_end=?, active=? WHERE id=?");
    $stmt->execute([$label, $t1s, $t1e, $t2s, $t2e, $act, $id]);

    if ($act) {
        $pdo->prepare("UPDATE academic_years SET active = 0 WHERE id != ?")->execute([$id]);
    }

    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">Academic year updated.</div>';
    log_action($pdo, $_SESSION['user_id'], 'Updated academic year ID ' . $id);
}

if (isset($_POST['delete_academic_year'])) {
    $id = (int)($_POST['del_ay_id'] ?? 0);
    $pdo->prepare("DELETE FROM academic_years WHERE id = ?")->execute([$id]);
    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">Academic year deleted.</div>';
    log_action($pdo, $_SESSION['user_id'], 'Deleted academic year ID ' . $id);
}

/* --- 3) System Logs viewer and cache clear --- */
/* Expected table (create if not exists):
CREATE TABLE IF NOT EXISTS system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action TEXT NOT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
*/
if (isset($_POST['clear_cache'])) {
    $cleared = [];
    // Try OPcache reset
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $cleared[] = 'OPcache reset';
    }

    // Clear application cache directory (adjust path as needed)
    $cacheDir = __DIR__ . '/../cache/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*');
        foreach ($files as $f) {
            if (is_file($f)) @unlink($f);
        }
        $cleared[] = 'Cache directory cleared';
    }

    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">Cache cleared: ' . implode(', ', $cleared) . '</div>';
    log_action($pdo, $_SESSION['user_id'], 'Cleared cache: ' . implode(', ', $cleared));
}

if (isset($_POST['purge_logs'])) {
    // Optionally limit or export before deleting in production. Here we delete all logs.
    $pdo->query("TRUNCATE TABLE system_logs");
    $message = '<div class="bg-green-100 border border-green-500 text-green-800 p-4 rounded-xl">System logs purged.</div>';
    log_action($pdo, $_SESSION['user_id'], 'Purged system logs');
}

// ============== NEW: Export logs to CSV before purge ==============
if (isset($_POST['export_logs_csv'])) {
    // Fetch logs (all)
    $stmt = $pdo->prepare("SELECT l.id, l.user_id, u.username, l.action, l.ip, l.created_at FROM system_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // CSV filename
    $filename = 'system_logs_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Output BOM for Excel compatibility
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    // Header row
    fputcsv($out, ['id', 'user_id', 'username', 'action', 'ip', 'created_at']);

    foreach ($rows as $r) {
        // Normalize fields to strings
        $line = [
            $r['id'],
            $r['user_id'],
            $r['username'],
            $r['action'],
            $r['ip'],
            $r['created_at']
        ];
        fputcsv($out, $line);
    }
    fclose($out);

    log_action($pdo, $_SESSION['user_id'], 'Exported system logs to CSV (' . count($rows) . ' rows)');
    exit;
}

/* --- Helper read data for UI --- */
$school_name = htmlspecialchars(get_setting('school_name', 'My School'));
$school_motto = htmlspecialchars(get_setting('school_motto', 'Knowledge and Virtue'));
$school_contact = htmlspecialchars(get_setting('school_contact', 'info@example.com'));
$school_logo = get_setting('school_logo', ''); // relative path

// Academic years list
$academic_years = $pdo->query("SELECT * FROM academic_years ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch last 200 logs for display
$logs = $pdo->prepare("SELECT l.*, u.username FROM system_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY created_at DESC LIMIT 200");
$logs->execute();
$system_logs = $logs->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Settings | School Management System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>
    tailwind.config = { theme: { extend: { colors: { deepblue: '#4682A9', lightblue: '#91C8E4', midblue: '#749BC2', cream: '#FFFBDE' } } } }
  </script>
</head>
<body class="bg-gradient-to-br from-cream via-white to-lightblue min-h-screen">

  <!-- Header -->
  <header class="bg-deepblue text-white shadow-2xl sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
      <h1 class="text-left-4 text-4xl font-extrabold flex items-center gap-4">
        <i class="fas fa-cogs"></i> System Settings
      </h1>
      <a href="dashboard.php" class="bg-lightblue text-deepblue px-8 py-3 rounded-xl font-bold hover:bg-midblue hover:text-white transition shadow-xl">
        Back to Dashboard
      </a>
    </div>
  </header>

  <div class="max-w-7xl mx-auto px-6 py-12">
    <?= $message ?>

    <div class="grid lg:grid-cols-2 gap-10">

      <!-- Security Panel -->
      <div class="bg-white rounded-3xl shadow-2xl p-8">
        <h2 class="text-3xl font-bold text-deepblue mb-8 flex items-center gap-4">
          <i class="fas fa-shield-alt text-4xl text-green-600"></i> Security Settings
        </h2>

        <!-- Change Password -->
        <div class="border-b pb-8 mb-8">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Change Admin Password</h3>
          <form method="POST" class="space-y-5">
            <input type="password" name="current_pass" placeholder="Current Password" required class="w-full p-4 border-2 border-gray-300 rounded-xl">
            <input type="password" name="new_pass" placeholder="New Password (min 8 chars)" required minlength="8" class="w-full p-4 border-2 border-gray-300 rounded-xl">
            <input type="password" name="confirm_pass" placeholder="Confirm New Password" required class="w-full p-4 border-2 border-gray-300 rounded-xl">
            <button name="change_password" class="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-xl font-bold shadow-lg transition">
              Change Password
            </button>
          </form>
        </div>

        <!-- 2FA Toggle -->
        <div>
          <h3 class="text-xl font-bold text-gray-800 mb-4">Two-Factor Authentication (2FA)</h3>
          <form method="POST">
            <label class="flex items-center gap-4 cursor-pointer">
              <input type="checkbox" name="2fa_enabled" value="1" <?= get_setting('2fa_enabled')=='1'?'checked':'' ?> class="w-6 h-6 text-deepblue">
              <span class="text-lg">Enable 2FA for all admin logins (Google Authenticator)</span>
            </label>
            <button name="toggle_2fa" class="mt-4 bg-deepblue text-white px-8 py-3 rounded-xl font-bold hover:bg-midblue transition">
              Save 2FA Setting
            </button>
          </form>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="bg-white rounded-3xl shadow-2xl p-8">
        <h2 class="text-3xl font-bold text-deepblue mb-8 flex items-center gap-4">
          <i class="fas fa-bell text-4xl text-yellow-600"></i> Notifications (Email & SMS)
        </h2>
        <form method="POST" class="space-y-6">
          <div>
            <label class="block text-lg font-semibold mb-2">SMTP Host</label>
            <input type="text" name="email_host" value="<?= htmlspecialchars(get_setting('email_host')) ?>" placeholder="e.g. smtp.gmail.com" class="w-full p-4 border-2 rounded-xl">
          </div>
          <div>
            <label class="block text-lg font-semibold mb-2">SMTP Username (Email)</label>
            <input type="text" name="email_user" value="<?= htmlspecialchars(get_setting('email_user')) ?>" placeholder="your-email@gmail.com" class="w-full p-4 border-2 rounded-xl">
          </div>
          <div>
            <label class="block text-lg font-semibold mb-2">SMTP Password</label>
            <input type="password" name="email_pass" placeholder="Leave blank to keep current" class="w-full p-4 border-2 rounded-xl">
          </div>
          <div>
            <label class="block text-lg font-semibold mb-2">SMS API Key (e.g. Termii, Twilio)</label>
            <input type="text" name="sms_api_key" value="<?= htmlspecialchars(get_setting('sms_api_key')) ?>" placeholder="Enter your SMS provider API key" class="w-full p-4 border-2 rounded-xl">
          </div>
          <button name="save_notifications" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-5 rounded-xl font-bold shadow-xl hover:shadow-2xl transition">
            Save Notification Settings
          </button>
        </form>
      </div>

      <!-- School Info Panel -->
      <div class="bg-white rounded-3xl shadow-2xl p-8">
        <h2 class="text-3xl font-bold text-deepblue mb-8 flex items-center gap-4">
          <i class="fas fa-school text-4xl text-indigo-600"></i> School Info
        </h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <div>
            <label class="block text-lg font-semibold mb-2">School Name</label>
            <input type="text" name="school_name" value="<?= $school_name ?>" class="w-full p-4 border-2 rounded-xl">
          </div>
          <div>
            <label class="block text-lg font-semibold mb-2">Motto</label>
            <input type="text" name="school_motto" value="<?= $school_motto ?>" class="w-full p-4 border-2 rounded-xl">
          </div>
          <div>
            <label class="block text-lg font-semibold mb-2">Contact Info</label>
            <input type="text" name="school_contact" value="<?= $school_contact ?>" class="w-full p-4 border-2 rounded-xl">
          </div>
          <div>
            <label class="block text-lg font-semibold mb-2">School Logo (PNG/JPG/WEBP/SVG)</label>
            <input type="file" name="school_logo" accept="image/*" class="w-full p-2">
            <?php if ($school_logo): ?>
              <div class="mt-4">
                <p class="text-sm text-gray-600 mb-2">Current Logo:</p>
                <img src="../<?= htmlspecialchars($school_logo) ?>" alt="School Logo" class="h-24 object-contain rounded-md border">
              </div>
            <?php endif; ?>
          </div>
          <button name="save_school_info" class="w-full bg-deepblue text-white py-4 rounded-xl font-bold">Save School Info</button>
        </form>
      </div>

      <!-- Academic Year Management -->
      <div class="bg-white rounded-3xl shadow-2xl p-8 lg:col-span-2">
        <h2 class="text-3xl font-bold text-deepblue mb-8 flex items-center gap-4">
          <i class="fas fa-calendar-alt text-4xl text-teal-600"></i> Academic Years & Terms
        </h2>

        <!-- Add new academic year -->
        <div class="border-b pb-6 mb-6">
          <form method="POST" class="grid md:grid-cols-2 gap-4">
            <input type="text" name="year_label" placeholder="e.g. 2025/2026" required class="p-3 border rounded-xl">
            <label class="flex items-center gap-2"><input type="checkbox" name="active" value="1"> Set as active</label>

            <div>
              <label class="block text-sm">Term 1 Start</label>
              <input type="date" name="term1_start" class="p-3 border rounded-xl w-full">
            </div>
            <div>
              <label class="block text-sm">Term 1 End</label>
              <input type="date" name="term1_end" class="p-3 border rounded-xl w-full">
            </div>

            <div>
              <label class="block text-sm">Term 2 Start</label>
              <input type="date" name="term2_start" class="p-3 border rounded-xl w-full">
            </div>
            <div>
              <label class="block text-sm">Term 2 End</label>
              <input type="date" name="term2_end" class="p-3 border rounded-xl w-full">
            </div>

            <div class="md:col-span-2">
              <button name="add_academic_year" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white py-3 rounded-xl font-bold">Add Academic Year</button>
            </div>
          </form>
        </div>

        <!-- Existing academic years -->
        <div>
          <h3 class="text-xl font-semibold mb-4">Existing Academic Years</h3>
          <div class="space-y-4">
            <?php foreach ($academic_years as $ay): ?>
              <form method="POST" class="bg-gray-50 p-4 rounded-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex-1">
                  <strong class="text-lg"><?= htmlspecialchars($ay['year_label']) ?> <?= $ay['active'] ? '<span class="text-green-600 font-bold">(Active)</span>' : '' ?></strong>
                  <div class="text-sm text-gray-600 mt-1">
                    Term1: <?= $ay['term1_start'] ?: '—' ?> to <?= $ay['term1_end'] ?: '—' ?> |
                    Term2: <?= $ay['term2_start'] ?: '—' ?> to <?= $ay['term2_end'] ?: '—' ?>
                  </div>
                </div>

                <div class="flex gap-2">
                  <input type="hidden" name="ay_id" value="<?= $ay['id'] ?>">
                  <input type="hidden" name="year_label" value="<?= htmlspecialchars($ay['year_label']) ?>">
                  <button formaction="" name="edit_show" class="px-4 py-2 bg-deepblue text-white rounded-xl">Edit</button>
                  <button formaction="" name="delete_academic_year" onclick="return confirm('Delete academic year?');" class="px-4 py-2 bg-red-600 text-white rounded-xl">Delete</button>
                </div>
              </form>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- FULL DATABASE BACKUP (phpMyAdmin Style) -->
      <div class="bg-white rounded-3xl shadow-2xl p-8 lg:col-span-2">
        <h2 class="text-3xl font-bold text-deepblue mb-8 flex items-center gap-4">
          <i class="fas fa-download text-4xl text-red-600"></i> Full Database Backup
        </h2>
        <div class="bg-gradient-to-r from-red-500 to-orange-500 text-white p-8 rounded-2xl text-center">
          <p class="text-2xl font-bold mb-4">Professional Grade Backup</p>
          <p class="text-lg opacity-90">Complete SQL dump with structure + data<br>Compatible with phpMyAdmin import</p>
        </div>
        <form method="POST" class="mt-8 text-center">
          <button name="full_backup" class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-pink-600 hover:to-red-600 text-white px-16 py-8 rounded-2xl text-2xl font-extrabold shadow-2xl transform hover:scale-105 transition duration-300">
            DOWNLOAD FULL BACKUP NOW (.sql)
          </button>
        </form>
        <p class="text-center text-gray-600 mt-6 text-sm">
          Backup includes all tables: students, exams, users, settings, news, attendance, etc.
        </p>
      </div>

      <!-- System Logs and Cache -->
      <div class="bg-white rounded-3xl shadow-2xl p-8 lg:col-span-2">
        <h2 class="text-3xl font-bold text-deepblue mb-8 flex items-center gap-4">
          <i class="fas fa-list text-4xl text-gray-700"></i> System Logs & Cache
        </h2>

        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <h3 class="text-lg font-semibold mb-3">Recent Activity</h3>
            <div class="max-h-72 overflow-y-auto bg-gray-50 p-4 rounded-xl space-y-3">
              <?php if (count($system_logs) === 0): ?>
                <p class="text-gray-600">No logs yet.</p>
              <?php else: ?>
                <?php foreach ($system_logs as $l): ?>
                  <div class="text-sm">
                    <div class="flex justify-between">
                      <div><strong><?= htmlspecialchars($l['username'] ?: 'System') ?></strong> — <?= htmlspecialchars($l['action']) ?></div>
                      <div class="text-gray-400"><?= htmlspecialchars($l['created_at']) ?></div>
                    </div>
                    <div class="text-xs text-gray-500">IP: <?= htmlspecialchars($l['ip'] ?? '') ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <form method="POST" class="mt-4 flex gap-2">
              <button name="export_logs_csv" class="bg-blue-600 text-white px-4 py-2 rounded-xl">Export Logs (CSV)</button>
              <button name="purge_logs" class="bg-red-600 text-white px-4 py-2 rounded-xl" onclick="return confirm('Really purge all logs? This cannot be undone.');">Purge Logs</button>
            </form>
          </div>

          <div>
            <h3 class="text-lg font-semibold mb-3">Cache</h3>
            <p class="text-sm text-gray-600 mb-4">Clear opcode cache and application cache files.</p>
            <form method="POST">
              <button name="clear_cache" class="bg-indigo-600 text-white px-6 py-3 rounded-xl">Clear Cache</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
