<?php
// includes/auth.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once __DIR__ . '/functions.php';

/** Path to users.json */
function users_file_path(): string {
    return __DIR__ . '/users.json';
}

/** Read all users from JSON file */
function get_all_users(): array {
    $path = users_file_path();
    if (!file_exists($path)) {
        return [];
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/** Find a user by username */
function find_user(string $username): ?array {
    foreach (get_all_users() as $u) {
        if (($u['username'] ?? null) === $username) {
            return $u;
        }
    }
    return null;
}

/** Attempt login; returns true on success */
function attempt_login_user(string $username, string $password): bool {
    $user = find_user($username);
    if (!$user || !isset($user['password'])) {
        return false;
    }
    // Plain text comparison (no hashing)
    if ($user['password'] !== $password) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_logged_in']   = true;
    $_SESSION['user_username']    = $user['username'];
    $_SESSION['user_role']        = $user['role'] ?? 'student';
    $_SESSION['user_display_name']= $user['display_name'] ?? $user['username'];

    return true;
}

/** Current user info (or null) */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'username'     => $_SESSION['user_username'] ?? null,
        'role'         => $_SESSION['user_role'] ?? null,
        'display_name' => $_SESSION['user_display_name'] ?? null,
    ];
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_logged_in']);
}

function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function redirect_to_login(string $next = '/'): void {
    header('Location: login.php?next=' . urlencode($next));
    exit;
}

function require_login(): void {
    if (!is_logged_in()) {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        redirect_to_login($next);
    }
}

function require_role(string $role): void {
    if (!is_logged_in() || current_user_role() !== $role) {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        redirect_to_login($next);
    }
}

function require_any_role(array $roles): void {
    if (!is_logged_in() || !in_array(current_user_role(), $roles, true)) {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        redirect_to_login($next);
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
