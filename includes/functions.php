<?php
// includes/functions.php
declare(strict_types=1);

// Safe HTML escape (used everywhere)
if (!function_exists('e')) {
    function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// You can add other small helper functions here later
// But never redeclare csrf_token(), check_csrf(), is_logged_in(), etc.
// → those live only in auth.php now