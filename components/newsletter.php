<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once 'config.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Send OTP
function sendOTP($email, $otp) {
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
        $mail->Subject = 'Your OTP for Comic Newsletter';
        $mail->Body = "Your OTP is: <b>$otp</b>. It will expire in 5 minutes.";

        $mail->send();
    } catch (Exception $e) {
        return false;
    }
    return true;
}

$msg = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = $_POST['email'] ?? $_SESSION['otp_email'] ?? '';
    $otp_input = $_POST['otp'] ?? '';

    if ($action === 'send_otp' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $pdo->prepare("INSERT INTO comic_subscribers (email, otp, otp_expiry) 
            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = VALUES(otp), otp_expiry = VALUES(otp_expiry)");
        $stmt->execute([$email, $otp, $expiry]);

        if (sendOTP($email, $otp)) {
            $_SESSION['otp_email'] = $email; // Store email in session
            $msg = 'OTP sent to your email.';
        } else {
            $msg = 'Failed to send OTP. Please try again.';
        }
    }

    if ($action === 'verify_otp') {
        $stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['otp'] === $otp_input && strtotime($user['otp_expiry']) > time()) {
            $pdo->prepare("UPDATE comic_subscribers SET is_verified = 1 WHERE email = ?")->execute([$email]);
            $_SESSION['verified_email'] = $email;
            unset($_SESSION['otp_email']); // Remove temporary session
            $msg = 'Email verified successfully.';
        } else {
            $msg = 'Invalid or expired OTP.';
        }
    }

    if ($action === 'subscribe' && isset($_SESSION['verified_email'])) {
        $time = $_POST['preferred_time'] ?? '08:00:00';
        $pdo->prepare("UPDATE comic_subscribers SET is_subscribed = 1, preferred_time = ? WHERE email = ?")
            ->execute([$time, $_SESSION['verified_email']]);
        $msg = 'Subscribed successfully.';
    }

    if ($action === 'unsubscribe' && isset($_SESSION['verified_email'])) {
        $pdo->prepare("UPDATE comic_subscribers SET is_subscribed = 0 WHERE email = ?")
            ->execute([$_SESSION['verified_email']]);
        $msg = 'Unsubscribed.';
    }

    if ($action === 'logout') {
        session_unset();
        session_destroy();
        $msg = 'You have been logged out. To access again, please verify with OTP.';
    }
}

// Fetch current user if verified session exists
$current = null;
if (isset($_SESSION['verified_email'])) {
    $stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE email = ?");
    $stmt->execute([$_SESSION['verified_email']]);
    $current = $stmt->fetch();
    $email = $_SESSION['verified_email'];
}
?>
<!DOCTYPE html>
<head>
    <title>Comic Newsletter</title>

</head>
<body class="bg-card-color dark:bg-gray-800 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class=" p-4 ml-2 flex items-center">
                <i class="fas fa-book-open text-red-500 mr-2"></i>
                <h2 class="text-red-500 font-bold">Comic Newsletter</h2>
            </div>
            
            <!-- Card Body -->
            <div class="p-6 -mt-5">
                <?php if ($msg): ?>
                    <div class="mb-4 p-3 rounded <?= strpos($msg, 'success') !== false ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <i class="fas <?= strpos($msg, 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-1"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>

                <?php if (!isset($_SESSION['verified_email'])): ?>
                    <!-- OTP Section -->
                    <div>
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-envelope mr-1 text-red-500"></i> Subscribe to get daily comics on mail !
                                </label>
                                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" 
                                       class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-red-300" 
                                       placeholder="your@email.com" required>
                            </div>
                            
                            <?php if (isset($_SESSION['otp_email'])): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-lock mr-1 text-red-500"></i> OTP Code
                                    </label>
                                    <input type="text" name="otp" 
                                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-red-300" 
                                           placeholder="Enter 6-digit OTP" required>
                                </div>
                                <button type="submit" name="action" value="verify_otp" 
                                        class="w-full py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                    <i class="fas fa-check-circle mr-1"></i> Verify OTP
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="send_otp" 
                                        class="w-full py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                    <i class="fas fa-paper-plane mr-1"></i> Send OTP
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Account Section -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <i class="fas fa-user-circle text-2xl text-red-500 mr-2"></i>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($current['email']) ?></p>
                                <p class="text-sm <?= $current['is_subscribed'] ? 'text-green-600' : 'text-gray-600' ?>">
                                    <i class="fas <?= $current['is_subscribed'] ? 'fa-bell' : 'fa-bell-slash' ?> mr-1"></i>
                                    <?= $current['is_subscribed'] ? 'Subscribed' : 'Not Subscribed' ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($current['is_subscribed']): ?>
                            <form method="POST">
                                <button type="submit" name="action" value="unsubscribe" 
                                        class="w-full py-2 bg-gray-200 text-red-500 rounded hover:bg-gray-300 transition">
                                    <i class="fas fa-times-circle mr-1"></i> Unsubscribe
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-clock mr-1 text-red-500"></i> Preferred Time
                                    </label>
                                    <input type="time" name="preferred_time" value="<?= htmlspecialchars($current['preferred_time']) ?>" 
                                           class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-red-300">
                                </div>
                                <button type="submit" name="action" value="subscribe" 
                                        class="w-full py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
                                    <i class="fas fa-check-circle mr-1"></i> Subscribe
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" class="pt-4 border-t">
                            <button type="submit" name="action" value="logout" 
                                    class="w-full py-2 text-gray-600 hover:text-gray-800 transition">
                                <i class="fas fa-sign-out-alt mr-1"></i> Exit
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>