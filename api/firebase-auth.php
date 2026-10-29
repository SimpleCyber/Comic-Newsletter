<?php
/**
 * Firebase Authentication Bridge Endpoint
 * Syncs client-side Firebase Auth (Google Sign-In, Email/Password) with PHP session
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../firebase.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// Read JSON input or POST body
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF security token. Please refresh the page.']);
    exit();
}

$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$name = cleanInput($input['name'] ?? '');
$uid = cleanInput($input['uid'] ?? '');
$provider = cleanInput($input['provider'] ?? 'google.com');
$fromAdmin = !empty($input['from_admin']);

if (!$email || empty($uid)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing valid email or UID']);
    exit();
}

// Check if user is an admin
$isAdmin = isAdminEmail($email);

// If the user is trying to access via admin portal but is not an admin, block them
if ($fromAdmin && !$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => "Access denied: {$email} does not have administrator privileges."
    ]);
    exit();
}

$userId = null;
$finalName = $name ?: explode('@', $email)[0];

// 1. Sync with MySQL database if active
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $userId = $existing['id'];
            $finalName = $existing['full_name'] ?: $finalName;
        } else {
            // Create user account for Google / Firebase authentication
            $dummyPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $insertStmt->execute([$finalName, $email, $dummyPass]);
            $userId = $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log("Database user sync error: " . $e->getMessage());
    }
}

// 2. Sync to Firebase Firestore cloud database
firestore_save_document('users', [
    'uid' => $uid,
    'email' => $email,
    'full_name' => $finalName,
    'last_login' => date('Y-m-d H:i:s'),
    'auth_provider' => $provider,
    'is_admin' => $isAdmin
], $uid);

// 3. Establish authenticated PHP session
session_regenerate_id(true);
$_SESSION['user_id'] = $userId ?: $uid;
$_SESSION['firebase_uid'] = $uid;
$_SESSION['full_name'] = $finalName;
$_SESSION['email'] = $email;

if ($isAdmin) {
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['is_admin'] = true;
}

// Determine redirect destination
$redirectUrl = ($fromAdmin || $isAdmin) ? '/bulldashboard/bull-dashboard.php' : '/index.php';

echo json_encode([
    'status' => 'success',
    'message' => 'Authenticated successfully',
    'is_admin' => $isAdmin,
    'redirect' => $redirectUrl
]);
exit();
