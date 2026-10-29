<?php
/**
 * Application Security Core
 * Centralizes Session Security, Headers, CSRF, Rate Limiting, and Access Control
 */

require_once __DIR__ . '/env.php';

// 1. Error Reporting & Debug Mode
$appEnv = env('APP_ENV', 'production');
if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
ini_set('log_errors', '1');

// 2. Hardened Session Settings (Must be set before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );

    session_set_cookie_params([
        'lifetime' => 86400 * 2, // 48 hours
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // Send cookie over HTTPS only if HTTPS is enabled
        'httponly' => true,       // Inaccessible to JavaScript (prevents XSS session theft)
        'samesite' => 'Lax'       // Protects against CSRF
    ]);

    session_start();
}

// 3. Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    
    // Content Security Policy allowing required assets (CDNs, Tailwind, FontAwesome, Firebase)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://www.gstatic.com; " .
           "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https://imgs.xkcd.com https://*.xkcd.com https://*.google.com https://*.googleapis.com; " .
           "connect-src 'self' https://xkcd.com https://*.xkcd.com https://firestore.googleapis.com https://identitytoolkit.googleapis.com https://securetoken.googleapis.com https://python-mailsend.onrender.com; " .
           "font-src 'self' https://cdnjs.cloudflare.com; " .
           "frame-ancestors 'self';";
    header("Content-Security-Policy: {$csp}");
}

// 4. CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time'] > 7200)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function getCsrfField() {
    $token = htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// 5. Rate Limiting (File & Session based)
function checkRateLimit($actionKey, $maxAttempts = 5, $decaySeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $sessionKey = 'rate_' . md5($actionKey . '_' . $ip);

    $now = time();
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [
            'attempts' => 1,
            'expires_at' => $now + $decaySeconds
        ];
        return true;
    }

    if ($now > $_SESSION[$sessionKey]['expires_at']) {
        $_SESSION[$sessionKey] = [
            'attempts' => 1,
            'expires_at' => $now + $decaySeconds
        ];
        return true;
    }

    if ($_SESSION[$sessionKey]['attempts'] >= $maxAttempts) {
        return false;
    }

    $_SESSION[$sessionKey]['attempts']++;
    return true;
}

function getRateLimitRemainingTime($actionKey) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $sessionKey = 'rate_' . md5($actionKey . '_' . $ip);
    if (!isset($_SESSION[$sessionKey])) return 0;
    return max(0, $_SESSION[$sessionKey]['expires_at'] - time());
}

function resetRateLimit($actionKey) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $sessionKey = 'rate_' . md5($actionKey . '_' . $ip);
    unset($_SESSION[$sessionKey]);
}

// 6. XSS Sanitization & HTML Output Helpers
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return trim(strip_tags((string)$data));
}

// 7. Role & Access Guards
function requireAdmin() {
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        $_SESSION['error'] = 'Access denied. Please log in as administrator.';
        header('Location: /admin.php');
        exit();
    }
}

function verifyCronSecret() {
    // Allow CLI execution directly
    if (php_sapi_name() === 'cli') {
        return true;
    }

    $secret = env('CRON_SECRET', '');
    if (empty($secret)) {
        // Fallback admin session check if cron secret is not set
        if (!empty($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
            return true;
        }
        http_response_code(403);
        die('Forbidden: CRON_SECRET not configured.');
    }

    $provided = $_GET['token'] ?? $_SERVER['HTTP_X_CRON_SECRET'] ?? '';
    if (!hash_equals($secret, $provided)) {
        http_response_code(403);
        die('Forbidden: Invalid CRON token.');
    }
    return true;
}
?>

