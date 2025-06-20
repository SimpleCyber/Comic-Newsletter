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
<html>
<head>
    <title>Comic Newsletter</title>
    <style>
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 150px; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <h2>Comic Letter Subscription</h2>
    <?php if ($msg): ?>
        <p style="color: <?= strpos($msg, 'success') !== false ? 'green' : 'red' ?>;"><?= $msg ?></p>
    <?php endif; ?>

    <?php if (!isset($_SESSION['verified_email'])): ?>
        <div id="otp-section">
            <div class="form-group" id="send-otp-section">
                <h3> Send OTP</h3>
                <form method="POST">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Enter your email" required>
                    <button type="submit" name="action" value="send_otp">Send OTP</button>
                </form>
            </div>

            <?php if (isset($_SESSION['otp_email'])): ?>
                <div class="form-group" id="verify-otp-section">
                    <h3> Verify OTP</h3>
                    <form method="POST">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['otp_email']) ?>">
                        <label>OTP:</label>
                        <input type="text" name="otp" placeholder="Enter OTP" required>
                        <button type="submit" name="action" value="verify_otp">Verify OTP</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <h3>Account Information</h3>
        <p><b>Email:</b> <?= htmlspecialchars($current['email']) ?></p>
        <p><b>Status:</b> <?= $current['is_subscribed'] ? 'Subscribed' : 'Not Subscribed' ?></p>

        <?php if ($current['is_subscribed']): ?>
            <form method="POST">
                <button type="submit" name="action" value="unsubscribe">Unsubscribe</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <label>Preferred Time: </label>
                <input type="time" name="preferred_time" value="<?= htmlspecialchars($current['preferred_time']) ?>">
                <button type="submit" name="action" value="subscribe">Subscribe</button>
            </form>
        <?php endif; ?>

        <form method="POST" style="margin-top: 20px;">
            <button type="submit" name="action" value="logout">Exit</button>
        </form>
    <?php endif; ?>
</body>
</html>