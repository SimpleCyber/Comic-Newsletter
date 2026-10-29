<?php
/**
 * Serves Firebase client configuration dynamically from .env
 */
require_once __DIR__ . '/../security.php';

header('Content-Type: application/javascript; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$config = [
    'apiKey' => env('FIREBASE_API_KEY', ''),
    'authDomain' => env('FIREBASE_AUTH_DOMAIN', ''),
    'projectId' => env('FIREBASE_PROJECT_ID', ''),
    'storageBucket' => env('FIREBASE_STORAGE_BUCKET', ''),
    'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID', ''),
    'appId' => env('FIREBASE_APP_ID', ''),
    'measurementId' => env('FIREBASE_MEASUREMENT_ID', '')
];
?>
window.firebaseConfig = <?php echo json_encode($config); ?>;
