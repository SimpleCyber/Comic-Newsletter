<?php
require_once '../config.php';
date_default_timezone_set('Asia/Kolkata');

$stmt = $pdo->query("SELECT * FROM comic_subscribers WHERE is_verified = 1 AND is_subscribed = 1");

while ($row = $stmt->fetch()) {
    $comicNum = rand(1, 3100);
    $comicJson = @file_get_contents("https://xkcd.com/$comicNum/info.0.json");
    if (!$comicJson) continue;

    $job_id = uniqid("job_", true);
    $stmt2 = $pdo->prepare("INSERT INTO email_queue 
        (job_id, recipient_email, comic_data, status, created_at) 
        VALUES (?, ?, ?, 'waiting', NOW())");

    $stmt2->execute([$job_id, $row['email'], $comicJson]);
}

?>
