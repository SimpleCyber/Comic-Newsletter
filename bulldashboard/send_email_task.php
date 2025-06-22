<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php';

function sendMail($pdo, $job) {
    $startTime = microtime(true);
    $jobId = $job['job_id'];
    $email = $job['recipient_email'];
    $comic = json_decode($job['comic_data'], true);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_HOST_USER;
        $mail->Password = EMAIL_KEY;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom(EMAIL_HOST_USER, 'Comic Letter');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "📰 Your XKCD Comic: {$comic['safe_title']}";
        $mail->Body = "
            <h2>XKCD Comic #{$comic['num']} – {$comic['safe_title']}</h2>
            <img src='{$comic['img']}' style='max-width:100%'>
            <p>{$comic['alt']}</p>
            <a href='https://xkcd.com/{$comic['num']}'>View Comic</a>
        ";

        $mail->send();

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
