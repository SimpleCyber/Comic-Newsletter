<?php
require_once '../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

date_default_timezone_set('UTC');

// Step 1: Get all verified + subscribed users
$stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE is_subscribed = 1 AND is_verified = 1");
$stmt->execute();
$subscribers = $stmt->fetchAll();

if (!$subscribers) {
    echo "No subscribers found.\n";
    exit;
}

foreach ($subscribers as $subscriber) {
    $email = $subscriber['email'];

    // Step 2: Pick random XKCD comic
    $comicNum = rand(1, 3104);
    $comicURL = "https://xkcd.com/$comicNum/info.0.json";
    $json = @file_get_contents($comicURL);

    // Retry if broken
    if (!$json) {
        echo "❌ Failed to fetch comic $comicNum for $email\n";
        continue;
    }

    $comic = json_decode($json, true);

    if (!isset($comic['img'])) {
        echo "❌ Invalid comic data for #$comicNum\n";
        continue;
    }

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

    // Step 3: Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_HOST_USER;
        $mail->Password   = EMAIL_KEY;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom(EMAIL_HOST_USER, 'Comic Letter');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "📰 Your XKCD Comic of the Day: {$comic['safe_title']}";
        $mail->Body    = $comicHTML;
        $mail->AltBody = "{$comic['safe_title']} - {$comic['alt']} - https://xkcd.com/{$comic['num']}";

        $mail->send();
        echo "✅ Sent to: $email (Comic #{$comic['num']})\n";
    } catch (Exception $e) {
        error_log("❌ Failed to send to $email: " . $mail->ErrorInfo);
        echo "❌ Failed to $email\n";
    }
}
