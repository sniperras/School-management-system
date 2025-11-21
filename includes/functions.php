<?php
// includes/functions.php
declare(strict_types=1);

/**
 * Read a JSON file and return decoded array (or default).
 */
function read_json(string $path, $default = []): array {
    if (!file_exists($path)) {
        return $default;
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : $default;
}

/**
 * Safe write JSON with exclusive lock.
 */
function write_json(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $tmp = $path . '.tmp';
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) return false;
    $fp = fopen($tmp, 'wb');
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    fwrite($fp, $encoded);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return rename($tmp, $path);
}

/**
 * Basic CSRF token helpers (session must be started in pages).
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}
function check_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Sanitize text for display.
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Simple pagination helper (not used heavy here).
 */
function paginate(array $items, int $page = 1, int $perPage = 10): array {
    $total = count($items);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $start = ($page - 1) * $perPage;
    $slice = array_slice($items, $start, $perPage);
    return ['data' => $slice, 'page' => $page, 'pages' => $pages, 'total' => $total];
}
/**
 * Admin credentials.
 * Change username and password_hash below.
 *
 * To create a new password hash, you can run:
 * php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT).PHP_EOL;"
 */
return [
    'admin_username' => 'admin',
    // password: admin123  (CHANGE THIS)
    'admin_password_hash' => '$2y$10$Q0f6gFq2e7Y2K8w2v5uP5eV3m0f0Gf7u2aZjx3Fv4m8Q6sL5QY5Sy'
];