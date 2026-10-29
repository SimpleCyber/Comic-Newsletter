<?php
/**
 * Vercel Serverless Front-Controller Entry Point
 * Routes requests to the appropriate PHP pages on Vercel
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uri, '/');

// Handle root
if ($path === '' || $path === 'index.php') {
    require_once __DIR__ . '/../index.php';
    exit();
}

// Direct PHP file match in root directory (e.g. /login.php, /signup.php, /admin.php)
$candidate = __DIR__ . '/../' . $path;
if (is_file($candidate) && pathinfo($candidate, PATHINFO_EXTENSION) === 'php') {
    require_once $candidate;
    exit();
}

// Clean extensionless URLs (e.g. /login -> /login.php)
if (is_file($candidate . '.php')) {
    require_once $candidate . '.php';
    exit();
}

// Bull-dashboard route
if (strpos($path, 'bulldashboard/') === 0) {
    $subPath = __DIR__ . '/../' . $path;
    if (is_file($subPath) && pathinfo($subPath, PATHINFO_EXTENSION) === 'php') {
        require_once $subPath;
        exit();
    }
}

// Default fallback to index.php
require_once __DIR__ . '/../index.php';

