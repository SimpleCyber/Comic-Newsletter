<?php
/**
 * Firebase Firestore REST API Helper for PHP
 * Allows PHP backend to save and retrieve data in Firestore without hardcoding credentials
 */

require_once __DIR__ . '/env.php';

define('FIREBASE_PROJECT_ID', env('FIREBASE_PROJECT_ID', ''));
define('FIREBASE_API_KEY', env('FIREBASE_API_KEY', ''));

/**
 * Convert PHP array into Firestore REST field format
 */
function firestore_format_fields(array $data) {
    $fields = [];
    foreach ($data as $key => $val) {
        if (is_int($val)) {
            $fields[$key] = ['integerValue' => (string)$val];
        } elseif (is_float($val)) {
            $fields[$key] = ['doubleValue' => $val];
        } elseif (is_bool($val)) {
            $fields[$key] = ['booleanValue' => $val];
        } elseif (is_null($val)) {
            $fields[$key] = ['nullValue' => null];
        } elseif (is_array($val)) {
            $fields[$key] = ['stringValue' => json_encode($val)];
        } else {
            $fields[$key] = ['stringValue' => (string)$val];
        }
    }
    return $fields;
}

/**
 * Save a document into a Firestore collection
 * 
 * @param string $collection Collection name (e.g. 'users', 'subscribers', 'comics')
 * @param array $data Associative array of data
 * @param string|null $documentId Optional document ID (if null, Firestore generates one)
 * @return array ['success' => bool, 'code' => int, 'data' => array|string]
 */
function firestore_save_document($collection, array $data, $documentId = null) {
    $projectId = FIREBASE_PROJECT_ID;
    $apiKey = FIREBASE_API_KEY;

    if (empty($projectId) || empty($apiKey)) {
        return [
            'success' => false,
            'code' => 500,
            'error' => 'Firebase credentials are missing in .env'
        ];
    }

    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}?key={$apiKey}";
    if ($documentId) {
        $url .= "&documentId=" . urlencode($documentId);
    }

    $payload = json_encode([
        'fields' => firestore_format_fields($data)
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $isSuccess = ($httpCode >= 200 && $httpCode < 300);
    return [
        'success' => $isSuccess,
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

/**
 * Retrieve all documents from a Firestore collection
 * 
 * @param string $collection Collection name
 * @return array Array of documents
 */
function firestore_get_collection($collection) {
    $projectId = FIREBASE_PROJECT_ID;
    $apiKey = FIREBASE_API_KEY;

    if (empty($projectId) || empty($apiKey)) {
        return [];
    }

    $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}?key={$apiKey}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        return $decoded['documents'] ?? [];
    }
    return [];
}
?>
