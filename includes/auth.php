<?php
// includes/auth.php
declare(strict_types=1);

// Safely start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

require_once __DIR__ . '/db.php';  // This gives us $pdo

// CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// function e(string $value): string {
//     return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
// }

// === Authentication Functions ===
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function current_user_role(): string {
    return $_SESSION['role'] ?? '';
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_name(): string {
    return $_SESSION['name'] ?? 'User';
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id' => current_user_id(),
        'name' => current_user_name(),
        'role' => current_user_role(),
    ];
}

// Login user (called after successful verification)
function login_user(int $user_id, string $name, string $role): void {
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user_id;
    $_SESSION['name']      = $name;
    $_SESSION['role']      = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
}

// Main login function (used in login.php)
function attempt_login_user(string $username, string $password): bool {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id, name, password_hash, role 
            FROM users 
            WHERE username = ? AND status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user(
                (int)$user['id'],
                $user['name'],
                $user['role']
            );
            return true;
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
    }

    return false;
}

// Logout
function logout_user(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// Redirect helpers (you already use these)
function redirect_to_login(string $next = ''): void {
    $location = 'login.php';
    if ($next) {
        $location .= '?next=' . urlencode($next);
    }
    header("Location: $location");
    exit;
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect_to_login($_SERVER['REQUEST_URI'] ?? '');
    }
}

function require_role(string $required_role): void {
    if (!is_logged_in() || current_user_role() !== $required_role) {
        redirect_to_login($_SERVER['REQUEST_URI'] ?? '');
    }
}

function require_any_role(array $allowed_roles): void {
    if (!is_logged_in() || !in_array(current_user_role(), $allowed_roles, true)) {
        redirect_to_login($_SERVER['REQUEST_URI'] ?? '');
    }
}