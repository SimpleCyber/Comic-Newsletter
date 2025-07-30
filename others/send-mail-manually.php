<?php
require_once '../config.php';
date_default_timezone_set('UTC');

// Step 1: Get all verified + subscribed users
$stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE is_subscribed = 1 AND is_verified = 1");
$stmt->execute();
$subscribers = $stmt->fetchAll();

if (!$subscribers) {
    echo "No subscribers found.\n";
    exit;
}

// ✅ Function moved outside loop
function sendComicEmail($email, $comic) {
    $url = 'https://python-mailsend.onrender.com/send-email';

    $subject = "📰 Your XKCD Comic of the Day: {$comic['safe_title']}";
    $comicHTML = "
    <div style='font-family: sans-serif; padding: 10px;'>
        <h2>XKCD Comic #{$comic['num']} – {$comic['safe_title']}</h2>
        <p><strong>Alt text:</strong> {$comic['alt']}</p>
        <img src='{$comic['img']}' alt='{$comic['safe_title']}' style='max-width:100%; height:auto;' />
        <p style='font-size: 12px; margin-top: 10px;'>Read more: <a href='https://xkcd.com/{$comic['num']}' target='_blank'>https://xkcd.com/{$comic['num']}</a></p>
        <hr>
        <p style='font-size: 11px;'>To unsubscribe, reply with 'UNSUBSCRIBE'.</p>
    </div>
    ";

    $data = [
        'to' => $email,
        'subject' => $subject,
        'body' => $comicHTML
    ];

    $headers = [
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 200) {
        echo "✅ Sent to: $email (Comic #{$comic['num']})\n";
    } else {
        error_log("❌ Failed to send to $email. Response: $response");
        echo "❌ Failed to $email\n";
    }
}

// ✅ Loop through subscribers and send
foreach ($subscribers as $subscriber) {
    $email = $subscriber['email'];

    // Step 2: Pick random XKCD comic
    $comicNum = rand(1, 3104);
    $comicURL = "https://xkcd.com/$comicNum/info.0.json";
    $json = @file_get_contents($comicURL);

    if (!$json) {
        echo "❌ Failed to fetch comic $comicNum for $email\n";
        continue;
    }

    $comic = json_decode($json, true);

    if (!isset($comic['img'])) {
        echo "❌ Invalid comic data for #$comicNum\n";
        continue;
    }

    // Step 3: Send email via Flask API
    sendComicEmail($email, $comic);
}
