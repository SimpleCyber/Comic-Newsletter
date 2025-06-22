<?php
require_once '../config.php';

date_default_timezone_set('Asia/Kolkata');

// Move jobs to active if within 5 minutes of preferred time
$stmt = $pdo->query("SELECT cs.email FROM comic_subscribers cs WHERE cs.is_subscribed = 1 AND cs.is_verified = 1");
$emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($emails as $email) {
    $stmt = $pdo->prepare("
        UPDATE email_queue 
        SET status = 'active', started_at = NOW()
        WHERE recipient_email = ? 
        AND status = 'waiting'
        AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(CURDATE(), ' ', (SELECT preferred_time FROM comic_subscribers WHERE email = ?))) <= 5
    ");
    $stmt->execute([$email, $email]);
}

// Retry failed jobs (up to 5 attempts)
$stmt = $pdo->prepare("UPDATE email_queue 
    SET status = 'active', next_retry_at = NULL 
    WHERE status = 'failed' AND attempts < max_attempts 
    AND (next_retry_at IS NULL OR next_retry_at <= NOW())");
$stmt->execute();

?>