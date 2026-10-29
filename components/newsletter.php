<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

function sendOTP($email, $otp)
{
    $url = env('MAIL_SERVICE_URL', 'https://python-mailsend.onrender.com/send-email');

    $subject = 'Your OTP for Comic Newsletter';
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $body = "Your OTP is: <b>{$safeOtp}</b>. It will expire in 5 minutes.";

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_status === 200;
}

$msg = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $msg = 'Invalid security token. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';
        $rawEmail = $_POST['email'] ?? $_SESSION['otp_email'] ?? '';
        $email = filter_var(trim($rawEmail), FILTER_VALIDATE_EMAIL);
        $otp_input = trim($_POST['otp'] ?? '');

        if ($action === 'send_otp') {
            if (!$email) {
                $msg = 'Please enter a valid email address.';
            } elseif (!checkRateLimit('newsletter_otp', 3, 300)) {
                $remaining = getRateLimitRemainingTime('newsletter_otp');
                $msg = "Too many OTP requests. Please wait {$remaining} seconds.";
            } elseif (!$pdo) {
                $msg = 'Database unavailable. Please try again later.';
            } else {
                $otp = strval(random_int(100000, 999999));
                $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                $stmt = $pdo->prepare("INSERT INTO comic_subscribers (email, otp, otp_expiry) 
                    VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = VALUES(otp), otp_expiry = VALUES(otp_expiry)");
                $stmt->execute([$email, $otp, $expiry]);

                if (sendOTP($email, $otp)) {
                    $_SESSION['otp_email'] = $email; 
                    $msg = 'OTP sent successfully!';
                } else {
                    $msg = 'Failed to send OTP. Please check service status.';
                }
            }
        }

        if ($action === 'verify_otp') {
            if (!checkRateLimit('newsletter_verify', 5, 600)) {
                $remaining = getRateLimitRemainingTime('newsletter_verify');
                $msg = "Too many failed verification attempts. Please wait {$remaining} seconds.";
            } elseif (!$pdo) {
                $msg = 'Database unavailable.';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE email = ?");
                $stmt->execute([$email]);
                $subscriber = $stmt->fetch();

                if ($subscriber && hash_equals((string)$subscriber['otp'], (string)$otp_input) && strtotime($subscriber['otp_expiry']) > time()) {
                    $pdo->prepare("UPDATE comic_subscribers SET is_verified = 1 WHERE email = ?")->execute([$email]);
                    session_regenerate_id(true);
                    $_SESSION['verified_email'] = $email;
                    unset($_SESSION['otp_email']);
                    resetRateLimit('newsletter_verify');
                    $msg = 'Email verified successfully.';
                } else {
                    $msg = 'Invalid or expired OTP.';
                }
            }
        }

        if ($action === 'subscribe' && isset($_SESSION['verified_email']) && $pdo) {
            $timeInput = $_POST['preferred_time'] ?? '08:00:00';
            // Validate time format (HH:MM:SS or HH:MM)
            if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $timeInput)) {
                $pdo->prepare("UPDATE comic_subscribers SET is_subscribed = 1, preferred_time = ? WHERE email = ?")
                    ->execute([$timeInput, $_SESSION['verified_email']]);
                $msg = 'Subscribed successfully.';
            } else {
                $msg = 'Invalid time format provided.';
            }
        }

        if ($action === 'unsubscribe' && isset($_SESSION['verified_email']) && $pdo) {
            $pdo->prepare("UPDATE comic_subscribers SET is_subscribed = 0 WHERE email = ?")
                ->execute([$_SESSION['verified_email']]);
            $msg = 'Unsubscribed successfully.';
        }

        if ($action === 'logout') {
            unset($_SESSION['verified_email'], $_SESSION['otp_email']);
            $msg = 'You have reset your subscriber session.';
        }
    }
}

// Fetch current user if verified session exists
$current = null;
if (isset($_SESSION['verified_email']) && $pdo) {
    $stmt = $pdo->prepare("SELECT email, is_subscribed, preferred_time FROM comic_subscribers WHERE email = ?");
    $stmt->execute([$_SESSION['verified_email']]);
    $current = $stmt->fetch();
    $email = $_SESSION['verified_email'];
}
?>

<div class="w-full max-w-md mx-auto">
    <div class="bg-card-light dark:bg-card-dark rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 flex items-center border-b border-gray-200 dark:border-gray-700">
            <i class="fas fa-book-open text-red-500 mr-2"></i>
            <h2 class="text-red-500 font-bold">Comic Newsletter</h2>
        </div>

        <div class="p-6">
            <?php if ($msg): ?>
                <div class="mb-4 p-3 rounded-lg text-sm <?= strpos(strtolower($msg), 'success') !== false ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' ?>">
                    <i class="fas <?= strpos(strtolower($msg), 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-1"></i>
                    <?php echo e($msg); ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['verified_email'])): ?>
                <div>
                    <form method="POST" class="space-y-4">
                        <?php echo getCsrfField(); ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <i class="fas fa-envelope mr-1 text-red-500"></i> Subscribe to get daily comics on mail
                            </label>
                            <input type="email" name="email" value="<?= e($email) ?>"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-400"
                                placeholder="your@email.com" required>
                        </div>

                        <?php if (isset($_SESSION['otp_email'])): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fas fa-lock mr-1 text-red-500"></i> Enter OTP Code
                                </label>
                                <input type="text" name="otp" maxlength="6" pattern="\d{6}"
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-400"
                                    placeholder="Enter 6-digit OTP" required autofocus>
                            </div>
                            <button type="submit" name="action" value="verify_otp"
                                class="w-full py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                                <i class="fas fa-check-circle mr-1"></i> Verify OTP
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="send_otp"
                                class="w-full py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                                <i class="fas fa-paper-plane mr-1"></i> Send Verification OTP
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <i class="fas fa-user-circle text-2xl text-red-500 mr-2"></i>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white"><?= e($current['email'] ?? '') ?></p>
                            <p class="text-sm <?= !empty($current['is_subscribed']) ? 'text-green-600 dark:text-green-400' : 'text-gray-500' ?>">
                                <i class="fas <?= !empty($current['is_subscribed']) ? 'fa-bell' : 'fa-bell-slash' ?> mr-1"></i>
                                <?= !empty($current['is_subscribed']) ? 'Subscribed' : 'Not Subscribed' ?>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($current['is_subscribed'])): ?>
                        <form method="POST">
                            <?php echo getCsrfField(); ?>
                            <button type="submit" name="action" value="unsubscribe"
                                class="w-full py-2 bg-gray-200 dark:bg-gray-700 text-red-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                                <i class="fas fa-times-circle mr-1"></i> Unsubscribe
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="space-y-4">
                            <?php echo getCsrfField(); ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <i class="fas fa-clock mr-1 text-red-500"></i> Preferred Time
                                </label>
                                <input type="time" name="preferred_time" value="<?= e($current['preferred_time'] ?? '08:00:00') ?>"
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-400">
                            </div>
                            <button type="submit" name="action" value="subscribe"
                                class="w-full py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                                <i class="fas fa-check-circle mr-1"></i> Save & Subscribe
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                <?php echo getCsrfField(); ?>
                <button type="submit" name="action" value="logout"
                    class="w-full py-2 text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
                    <i class="fas fa-sign-out-alt mr-1"></i> Reset / Change Email
                </button>
            </form>
        </div>
    </div>
</div>
