<?php
// functions.php

function fetchXkcdComic($comicId) {
    $url = "https://xkcd.com/{$comicId}/info.0.json";
    $json = @file_get_contents($url);
    
    if ($json === FALSE) {
        return null;
    }
    
    return json_decode($json, true);
}

function fetchRandomXkcdComic() {
    // Latest known comic ID (as per your requirement)
    $maxComicId = 3104;
    $randomId = rand(1, $maxComicId);
    
    // Try to fetch the random comic
    $comic = fetchXkcdComic($randomId);
    
    // If comic doesn't exist (unlikely but possible), try again
    $attempts = 0;
    while ($comic === null && $attempts < 5) {
        $randomId = rand(1, $maxComicId);
        $comic = fetchXkcdComic($randomId);
        $attempts++;
    }
    
    return $comic ?: null;
}

function fetchMultipleXkcdComics($startId, $count = 9) {
    $comics = [];
    $currentId = $startId;
    $attempts = 0;
    $maxAttempts = $count * 2; // Prevent infinite loops
    
    while (count($comics) < $count && $attempts < $maxAttempts) {
        $comic = fetchXkcdComic($currentId);
        
        if ($comic !== null) {
            $comics[] = $comic;
            $currentId--;
        } else {
            // If comic doesn't exist, try the previous one
            $currentId--;
        }
        
        $attempts++;
        
        // Don't go below comic #1
        if ($currentId < 1) {
            break;
        }
    }
    
    return $comics;
}
?>
