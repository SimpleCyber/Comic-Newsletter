<?php
require_once '../config.php';
require_once 'send_email_task.php'; // defined below

date_default_timezone_set('Asia/Kolkata');

// Fetch jobs with status = 'active'
$stmt = $pdo->prepare("SELECT * FROM email_queue WHERE status = 'active' AND attempts < max_attempts ORDER BY priority DESC, created_at ASC LIMIT 5");
$stmt->execute();
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    sendMail($pdo, $job);
}

?>