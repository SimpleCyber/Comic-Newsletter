<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $otp_input = $_POST['otp'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $pdo->prepare("INSERT INTO comic_subscribers (email, otp, otp_expiry) 
            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE otp = VALUES(otp), otp_expiry = VALUES(otp_expiry)");
        $stmt->execute([$email, $otp, $expiry]);

        sendOTP($email, $otp);
        $msg = 'OTP sent to your email.';
    }

    if ($action === 'verify_otp') {
        $stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['otp'] === $otp_input && strtotime($user['otp_expiry']) > time()) {
            $pdo->prepare("UPDATE comic_subscribers SET is_verified = 1 WHERE email = ?")->execute([$email]);
            $_SESSION['email'] = $email;
            $msg = 'Email verified successfully.';
        } else {
            $msg = 'Invalid or expired OTP.';
        }
    }

    if ($action === 'subscribe' && isset($_SESSION['email'])) {
        $time = $_POST['preferred_time'] ?? '08:00:00';
        $pdo->prepare("UPDATE comic_subscribers SET is_subscribed = 1, preferred_time = ? WHERE email = ?")
            ->execute([$time, $_SESSION['email']]);
        $msg = 'Subscribed successfully.';
    }

    if ($action === 'unsubscribe' && isset($_SESSION['email'])) {
        $pdo->prepare("UPDATE comic_subscribers SET is_subscribed = 0 WHERE email = ?")
            ->execute([$_SESSION['email']]);
        $msg = 'Unsubscribed.';
    }
}

// Fetch current user if session exists
$current = null;
if (isset($_SESSION['email'])) {
    $stmt = $pdo->prepare("SELECT * FROM comic_subscribers WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $current = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Comic Newsletter</title>
</head>
<body>
    <h2>Comic Letter Subscription</h2>
    <p style="color: green;"><?= $msg ?></p>

    <?php if (!$current): ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button name="action" value="send_otp">Send OTP</button>
        </form>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email again" required>
            <input type="text" name="otp" placeholder="Enter OTP" required>
            <button name="action" value="verify_otp">Verify OTP</button>
        </form>
    <?php elseif ($current['is_verified']): ?>
        <p><b>Email:</b> <?= $current['email'] ?></p>
        <p><b>Status:</b> <?= $current['is_subscribed'] ? 'Subscribed' : 'Not Subscribed' ?></p>

        <?php if ($current['is_subscribed']): ?>
            <form method="POST">
                <button name="action" value="unsubscribe">Unsubscribe</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <label>Preferred Time: </label>
                <input type="time" name="preferred_time" value="<?= $current['preferred_time'] ?>">
                <button name="action" value="subscribe">Subscribe</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
