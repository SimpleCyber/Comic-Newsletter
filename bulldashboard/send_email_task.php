<?php

function sendComicEmail($email, $comic) {
    $url = 'https://python-mailsend.onrender.com/send-email';

    $subject = "📰 Your XKCD Comic: {$comic['safe_title']}";
    $body = "
        <div style='font-family: sans-serif; padding: 10px;'>
            <h2>XKCD Comic #{$comic['num']} – {$comic['safe_title']}</h2>
            <img src='{$comic['img']}' alt='{$comic['safe_title']}' style='max-width:100%; height:auto;' />
            <p><em>{$comic['alt']}</em></p>
            <p><a href='https://xkcd.com/{$comic['num']}'>View Comic on XKCD</a></p>
            <hr>
            <p style='font-size: 11px;'>You're receiving this because you're subscribed to Comic Letter.</p>
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

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 200) {
        echo "✅ Sent to $email (Comic #{$comic['num']})\n";
    } else {
        error_log("❌ Failed to send to $email. Response: $response");
        echo "❌ Failed to $email\n";
    }
}


function sendMail($pdo, $job) {
    $startTime = microtime(true);
    $jobId = $job['job_id'];
    $email = $job['recipient_email'];
    $comic = json_decode($job['comic_data'], true);

    try {
        sendComicEmail($email, $comic);

        // Update job as completed
        $pdo->prepare("UPDATE email_queue SET 
            status = 'completed',
            attempts = attempts + 1,
            completed_at = NOW(),
            process_time_ms = :pt
            WHERE job_id = :id
        ")->execute([
            'pt' => (microtime(true) - $startTime) * 1000,
            'id' => $jobId
        ]);
    } catch (Exception $e) {
        $nextRetry = (new DateTime())->modify('+5 minutes')->format('Y-m-d H:i:s');

        $status = ($job['attempts'] + 1 >= $job['max_attempts']) ? 'failed' : 'delayed';

        $pdo->prepare("UPDATE email_queue SET 
            status = :status,
            attempts = attempts + 1,
            error_message = :err,
            failed_at = NOW(),
            next_retry_at = :next
            WHERE job_id = :id
        ")->execute([
            'status' => $status,
            'err' => $e->getMessage(),
            'next' => $nextRetry,
            'id' => $jobId
        ]);
    }
}
