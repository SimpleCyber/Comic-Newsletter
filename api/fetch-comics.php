<?php
header('Content-Type: application/json');

$startId = isset($_GET['startId']) ? (int)$_GET['startId'] : 3104;
$count = isset($_GET['count']) ? min((int)$_GET['count'], 9) : 3; // Max 10 at a time

require_once '../functions.php'; // Assuming your fetch function is here

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