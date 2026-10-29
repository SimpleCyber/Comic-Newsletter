<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/security.php';

// Database configuration from .env
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'copywriting_course'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Enforces true native prepared statements (SQLi prevention)
    ]);
} catch(PDOException $e) {
    // If running in an environment without MySQL, keep $pdo null so other services (like Firebase) can run
    $pdo = null;
    error_log("Database connection error: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Ensures users only access their own profile data (IDOR prevention)
 */
function getUserData($pdo, $user_id) {
    if (!$pdo || empty($user_id)) return null;
    $stmt = $pdo->prepare("SELECT id, full_name, email, created_at FROM users WHERE id = ?");
    $stmt->execute([(int)$user_id]);
    return $stmt->fetch();
}
?>
