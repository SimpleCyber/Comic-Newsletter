<?php
/**
 * Daily Comic Automation Endpoint (24-Hour Cron Job)
 * 
 * Functions:
 * 1. Checks and reschedules remaining/delayed jobs
 * 2. Generates daily comic queue items for active subscribers
 * 3. Sends emails via the mail service
 * 4. Logs execution metrics and returns JSON status
 * 
 * Accessible via cron-job.org or GitHub Actions:
 * https://your-domain.com/api/cron.php?token=YOUR_CRON_SECRET
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../bulldashboard/send_email_task.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Verify authorization
verifyCronSecret();

$startTime = microtime(true);
$results = [
    'success' => true,
    'timestamp' => date('c'),
    'rescheduled_jobs' => 0,
    'new_jobs_created' => 0,
    'processed_jobs' => 0,
    'sent' => 0,
    'failed' => 0,
    'errors' => []
];

// ==========================================
// SCENARIO A: MySQL Database Available
// ==========================================
if ($pdo) {
    try {
        // --- 1. Reschedule remaining delayed/failed jobs ---
        $rescheduleStmt = $pdo->prepare("
            UPDATE email_queue 
            SET status = 'active', next_retry_at = NULL 
            WHERE (status = 'delayed' OR status = 'failed' OR status = 'waiting') 
              AND attempts < max_attempts 
              AND (next_retry_at IS NULL OR next_retry_at <= NOW())
        ");
        $rescheduleStmt->execute();
        $results['rescheduled_jobs'] = $rescheduleStmt->rowCount();

        // --- 2. Generate daily comic jobs for verified subscribers ---
        $subStmt = $pdo->query("SELECT id, email FROM comic_subscribers WHERE is_verified = 1 AND is_subscribed = 1");
        $subscribers = $subStmt->fetchAll(PDO::FETCH_ASSOC);

        $checkJobStmt = $pdo->prepare("
            SELECT id FROM email_queue 
            WHERE recipient_email = ? 
              AND created_at >= (NOW() - INTERVAL 20 HOUR)
            LIMIT 1
        ");

        $insertJobStmt = $pdo->prepare("
            INSERT INTO email_queue (job_id, recipient_email, comic_data, status, created_at) 
            VALUES (?, ?, ?, 'active', NOW())
        ");

        foreach ($subscribers as $sub) {
            $email = $sub['email'];
            $checkJobStmt->execute([$email]);

            // Only queue a new job if they haven't received one in the last 20 hours
            if (!$checkJobStmt->fetch()) {
                $comic = fetchRandomXkcdComic();
                if ($comic) {
                    $jobId = uniqid("job_", true);
                    $insertJobStmt->execute([$jobId, $email, json_encode($comic)]);
                    $results['new_jobs_created']++;
                }
            }
        }

        // --- 3. Process the active queue & send emails (Batch limit: 20 per trigger) ---
        $queueStmt = $pdo->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'active' AND attempts < max_attempts 
            ORDER BY priority DESC, created_at ASC 
            LIMIT 20
        ");
        $queueStmt->execute();
        $jobs = $queueStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($jobs as $job) {
            $results['processed_jobs']++;
            $sendRes = sendMail($pdo, $job);
            if ($sendRes['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed sending to {$job['recipient_email']}: " . ($sendRes['error'] ?? 'Unknown');
            }
        }

        // --- 4. Log execution in cron_logs ---
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO cron_logs (cron_type, status, jobs_created, jobs_processed, execution_time_ms) 
                VALUES ('daily_comics', 'completed', ?, ?, ?)
            ");
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $logStmt->execute([$results['new_jobs_created'], $results['processed_jobs'], $durationMs]);
        } catch (PDOException $e) {
            // cron_logs table is optional
        }

    } catch (Exception $e) {
        $results['success'] = false;
        $results['errors'][] = $e->getMessage();
    }
} 
// ==========================================
// SCENARIO B: Serverless / Firebase Firestore Fallback
// ==========================================
else {
    try {
        require_once __DIR__ . '/../firebase.php';
        $subscribers = firestore_get_collection('subscribers');

        foreach ($subscribers as $doc) {
            $fields = $doc['fields'] ?? [];
            $email = $fields['email']['stringValue'] ?? '';
            $status = $fields['status']['stringValue'] ?? 'active';

            if ($email && $status === 'active') {
                $comic = fetchRandomXkcdComic();
                if ($comic) {
                    $results['processed_jobs']++;
                    try {
                        sendComicEmail($email, $comic);
                        $results['sent']++;
                    } catch (Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "{$email}: " . $e->getMessage();
                    }
                }
            }
        }
    } catch (Exception $e) {
        $results['success'] = false;
        $results['errors'][] = $e->getMessage();
    }
}

$results['execution_time_ms'] = (int)((microtime(true) - $startTime) * 1000);

echo json_encode($results, JSON_PRETTY_PRINT);
exit();
