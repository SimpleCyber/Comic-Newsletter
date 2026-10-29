<?php
require_once __DIR__ . '/../security.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Rate limit API fetches: 60 requests per minute per IP
if (!checkRateLimit('api_fetch_comics', 60, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please wait a moment.']);
    exit();
}

$startId = isset($_GET['startId']) ? max(1, (int)$_GET['startId']) : 3104;
$count = isset($_GET['count']) ? max(1, min((int)$_GET['count'], 9)) : 3;

require_once __DIR__ . '/../functions.php';

$comics = [];
for ($i = 0; $i < $count; $i++) {
    $comicId = $startId - $i;
    if ($comicId < 1) break;
    
    $comic = fetchXkcdComic($comicId);
    if ($comic) {
        $comics[] = $comic;
    }
}

echo json_encode($comics);
