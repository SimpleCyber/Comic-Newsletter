<?php

function sendComicEmail($email, $comic) {
    $url = env('MAIL_SERVICE_URL', 'https://python-mailsend.onrender.com/send-email');

    $safeTitle = htmlspecialchars($comic['safe_title'] ?? 'Daily Comic', ENT_QUOTES, 'UTF-8');
    $safeAlt = htmlspecialchars($comic['alt'] ?? '', ENT_QUOTES, 'UTF-8');
    $safeImg = htmlspecialchars($comic['img'] ?? '', ENT_QUOTES, 'UTF-8');
    $comicNum = (int)($comic['num'] ?? 0);

    $subject = "📰 Your XKCD Comic: {$safeTitle}";
    $body = "
        <div style='font-family: sans-serif; padding: 15px; max-width: 600px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 8px;'>
            <h2 style='color: #1f2937;'>XKCD #{$comicNum} – {$safeTitle}</h2>
            <img src='{$safeImg}' alt='{$safeAlt}' style='max-width:100%; height:auto; border-radius: 6px; margin: 10px 0;' />
            <p style='color: #4b5563; font-style: italic;'>{$safeAlt}</p>
            <p><a href='https://xkcd.com/{$comicNum}' style='color: #3b82f6;'>View on XKCD</a></p>
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
            <p style='font-size: 11px; color: #9ca3af;'>You're receiving this because you're subscribed to Comic Byte.</p>
        </div>
    ";

    $data = [
        'to' => $email,
        'subject' => $subject,
        'body' => $body
    ];

    $headers = [
        'Content-Type: application/json'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($http_status === 200) {
        return true;
    } else {
        $errorMsg = $curlErr ? "cURL error: {$curlErr}" : "Mail API returned status {$http_status}: " . substr((string)$response, 0, 100);
        error_log("❌ Failed to send to $email: {$errorMsg}");
        throw new Exception($errorMsg);
    }
}

function sendMail($pdo, $job) {
    $startTime = microtime(true);
    $jobId = $job['job_id'];
    $email = $job['recipient_email'];
    $comic = is_array($job['comic_data']) ? $job['comic_data'] : json_decode($job['comic_data'], true);

    try {
        sendComicEmail($email, $comic);

        // Update job as completed
        if ($pdo) {
            $pdo->prepare("UPDATE email_queue SET 
                status = 'completed',
                attempts = attempts + 1,
                completed_at = NOW(),
                process_time_ms = :pt,
                error_message = NULL
                WHERE job_id = :id
            ")->execute([
                'pt' => (int)((microtime(true) - $startTime) * 1000),
                'id' => $jobId
            ]);
        }
        return ['success' => true, 'email' => $email, 'job_id' => $jobId];
    } catch (Exception $e) {
        $nextRetry = (new DateTime())->modify('+10 minutes')->format('Y-m-d H:i:s');
        $attempts = ($job['attempts'] ?? 0) + 1;
        $maxAttempts = $job['max_attempts'] ?? 5;
        $status = ($attempts >= $maxAttempts) ? 'failed' : 'delayed';

        if ($pdo) {
            $pdo->prepare("UPDATE email_queue SET 
                status = :status,
                attempts = :attempts,
                error_message = :err,
                failed_at = NOW(),
                next_retry_at = :next
                WHERE job_id = :id
            ")->execute([
                'status' => $status,
                'attempts' => $attempts,
                'err' => substr($e->getMessage(), 0, 255),
                'next' => $nextRetry,
                'id' => $jobId
            ]);
        }
        return ['success' => false, 'email' => $email, 'job_id' => $jobId, 'error' => $e->getMessage()];
    }
}
?>
