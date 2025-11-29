<?php
// includes/functions.php
declare(strict_types=1);

// -------------------------------
// Small, powerful helpers used across the app
// -------------------------------

// Safe HTML escape (used everywhere)
if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Write a record to the system_logs table.
 * Fits your current schema: (id, user_id, action, ip, created_at)
 * If you later add username/meta columns, you can expand this function.
 *
 * @param PDO|null $pdo   PDO instance (if null the global $pdo will be used)
 * @param int|null $userId
 * @param string   $action
 * @param array|null $meta  Optional meta array (will be json_encoded into a text field if you add a meta column)
 * @return void
 */
if (!function_exists('log_action')) {
    function log_action(?PDO $pdo, $userId, string $action, ?array $meta = null): void {
        try {
            if ($pdo === null) {
                global $pdo;
$pdo = $pdo ?? null;

            }
            if (!$pdo instanceof PDO) {
                // cannot log without DB connection; fallback to PHP error_log
                error_log("log_action: no PDO available. Action: " . $action);
                return;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // If the schema has a 'meta' column (JSON/TEXT) we will try to insert it, otherwise ignore.
            $hasMeta = false;
            try {
                $res = $pdo->query("SHOW COLUMNS FROM system_logs LIKE 'meta'")->fetch();
                if ($res) $hasMeta = true;
            } catch (Exception $e) {
                // ignore
            }

            if ($hasMeta) {
                $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
                $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip, meta) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $action, $ip, $metaJson]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, ip) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $action, $ip]);
            }
        } catch (Exception $e) {
            // logging must never break the application; write to PHP error log instead
            error_log('log_action error: ' . $e->getMessage());
        }
    }
}

/**
 * Ensure directory exists and is writable. Returns true on success.
 */
if (!function_exists('ensure_dir')) {
    function ensure_dir(string $path, int $mode = 0755): bool {
        if (is_dir($path)) return is_writable($path);
        return @mkdir($path, $mode, true);
    }
}

/**
 * Create a safe filename from arbitrary string (keeps extension if provided)
 */
if (!function_exists('safe_filename')) {
    function safe_filename(string $filename): string {
        $filename = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $filename);
        // collapse multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        return $filename;
    }
}

/**
 * Upload an image safely and return relative path on success or false on failure.
 * - $fieldName is the <input type="file" name="...">
 * - $targetDir is relative to project root (recommended: 'uploads/logos')
 */
if (!function_exists('upload_image')) {
    function upload_image(string $fieldName, string $targetDir = 'uploads', array $options = []) {
        // options: allowed_mimes, max_bytes
        $allowed = $options['allowed_mimes'] ?? ['image/png','image/jpeg','image/webp','image/svg+xml'];
        $maxBytes = $options['max_bytes'] ?? 2_000_000; // 2MB default

        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $file = $_FILES[$fieldName];
        $tmp  = $file['tmp_name'];
        $detected = mime_content_type($tmp) ?: '';
        if (!in_array($detected, $allowed, true)) {
            return false;
        }

        if ($file['size'] > $maxBytes) return false;

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'img';
        $name = pathinfo($file['name'], PATHINFO_FILENAME);
        $safe = safe_filename($name) . '_' . time() . '.' . $ext;

        // Ensure target dir exists (relative to this file)
        $projectRoot = dirname(__DIR__); // includes/ -> project root
        $fullDir = $projectRoot . DIRECTORY_SEPARATOR . trim($targetDir, '/\\');
        if (!ensure_dir($fullDir)) return false;

        $targetPath = $fullDir . DIRECTORY_SEPARATOR . $safe;
        if (!move_uploaded_file($tmp, $targetPath)) return false;

        // Return relative path from project root, using forward slashes for web
        return trim($targetDir, '/\\') . '/' . $safe;
    }
}

/**
 * Export rows (array of associative arrays) to CSV and force download.
 * Adds BOM for Excel compatibility.
 */
if (!function_exists('export_csv')) {
    function export_csv(array $rows, string $filename = null): void {
        if ($filename === null) $filename = 'export_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // BOM

        $out = fopen('php://output', 'w');
        if (empty($rows)) {
            fputcsv($out, ['no_rows']);
            fclose($out);
            exit;
        }

        // header
        fputcsv($out, array_keys(reset($rows)));
        foreach ($rows as $r) {
            // ensure scalar values
            $line = array_map(function($v){
                if (is_array($v) || is_object($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
                return $v;
            }, $r);
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }
}

/**
 * Clear cache directory and try to reset OPcache if available.
 * Returns array of strings indicating actions taken.
 */
if (!function_exists('clear_application_cache')) {
    function clear_application_cache(string $cacheDir = 'cache'): array {
        $projectRoot = dirname(__DIR__);
        $full = $projectRoot . DIRECTORY_SEPARATOR . trim($cacheDir, '/\\');
        $actions = [];

        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $actions[] = 'opcache_reset';
        }

        if (is_dir($full)) {
            $files = glob($full . DIRECTORY_SEPARATOR . '*');
            foreach ($files as $f) {
                if (is_file($f)) @unlink($f);
            }
            $actions[] = 'cache_dir_cleared';
        }

        return $actions;
    }
}

/**
 * Small helper: redirect and exit
 */
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Flash messages using session (simple). Boot session before using.
 */
if (!function_exists('flash_set')) {
    function flash_set(string $key, string $message): void {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $_SESSION['_flash'][$key] = $message;
    }
}
if (!function_exists('flash_get')) {
    function flash_get(string $key): ?string {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        if (!empty($_SESSION['_flash'][$key])) {
            $m = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $m;
        }
        return null;
    }
}

/**
 * Human readable date (localized) - returns e.g. "Nov 26, 2025 07:42 PM"
 */
if (!function_exists('human_date')) {
    function human_date(string $datetime, string $format = 'M j, Y H:i'): string {
        if (empty($datetime)) return '';
        try {
            $d = new DateTime($datetime);
            return $d->format($format);
        } catch (Exception $e) {
            return $datetime;
        }
    }
}

/**
 * Simple paginator for SELECT queries (returns array with rows and meta).
 * - $sql: base SQL without LIMIT
 * - $params: bound params
 */
if (!function_exists('pagi_query')) {
    function pagi_query(PDO $pdo, string $sql, array $params = [], int $page = 1, int $perPage = 20): array {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") AS tmp_count";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare($sql . " LIMIT ? OFFSET ?");
        $allParams = array_merge($params, [$perPage, $offset]);
        $stmt->execute($allParams);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rows' => $rows,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int)ceil($total / $perPage),
            ]
        ];
    }
}
function get_grade($percent) {
    if ($percent >= 90) return "A+";
    if ($percent >= 80) return "A";
    if ($percent >= 70) return "B";
    if ($percent >= 60) return "C";
    if ($percent >= 50) return "D";
    return "F";
}

function calculate_gpa($percent) {
    if ($percent >= 90) return 4.0;
    if ($percent >= 80) return 3.7;
    if ($percent >= 70) return 3.3;
    if ($percent >= 60) return 3.0;
    if ($percent >= 50) return 2.0;
    return 0.0;
}
// You can add other small helper functions here later
// But never redeclare csrf_token(), check_csrf(), is_logged_in(), etc.
// → those live only in auth.php now

?>