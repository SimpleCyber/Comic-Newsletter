<?php
require_once 'config.php';

// Check if already authenticated
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    header('Location: /bulldashboard/bull-dashboard.php');
    exit();
}

// Function to generate a secure 6-digit OTP
function generateOTP()
{
    return strval(random_int(100000, 999999));
}

// Function to send OTP
function sendOTP($email, $otp)
{
    $url = env('MAIL_SERVICE_URL', 'https://python-mailsend.onrender.com/send-email');

    $data = [
        'to' => $email,
        'subject' => 'Your OTP for Admin Login',
        'body' => "Your OTP is: <b>" . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "</b><br>This OTP is valid for 10 minutes."
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

// Handle OTP request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    if (!verifyCsrfToken()) {
        $_SESSION['error'] = 'Invalid request token. Please try again.';
        header('Location: /admin.php');
        exit();
    }

    if (!checkRateLimit('admin_otp_request', 3, 300)) {
        $remaining = getRateLimitRemainingTime('admin_otp_request');
        $_SESSION['error'] = "Too many OTP requests. Please wait {$remaining} seconds before trying again.";
        header('Location: /admin.php');
        exit();
    }

    $email = env('ADMIN_EMAIL', 'admin@example.com');
    $otp = generateOTP();

    // Store OTP in session with expiration time (10 minutes)
    $_SESSION['admin_otp'] = $otp;
    $_SESSION['admin_otp_expiry'] = time() + 600;

    if (sendOTP($email, $otp)) {
        $_SESSION['otp_sent'] = true;
        $_SESSION['message'] = "OTP has been sent to the configured admin email.";
    } else {
        $_SESSION['error'] = "Failed to send OTP email. Please check mail service configuration.";
    }

    header('Location: /admin.php');
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    if (!verifyCsrfToken()) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: /admin.php');
        exit();
    }

    if (!checkRateLimit('admin_otp_verify', 5, 600)) {
        $remaining = getRateLimitRemainingTime('admin_otp_verify');
        $_SESSION['error'] = "Too many failed attempts. Locked for {$remaining} seconds.";
        header('Location: /admin.php');
        exit();
    }

    $user_otp = trim($_POST['otp'] ?? '');

    if (empty($user_otp)) {
        $_SESSION['error'] = 'Please enter the OTP.';
    } elseif (!isset($_SESSION['admin_otp']) || !isset($_SESSION['admin_otp_expiry'])) {
        $_SESSION['error'] = 'OTP expired or not requested. Please request a new OTP.';
    } elseif (time() > $_SESSION['admin_otp_expiry']) {
        $_SESSION['error'] = 'OTP has expired. Please request a new OTP.';
    } elseif (hash_equals((string)$_SESSION['admin_otp'], (string)$user_otp)) {
        // OTP is valid - regenerate session to protect against session fixation
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        unset($_SESSION['admin_otp'], $_SESSION['admin_otp_expiry'], $_SESSION['otp_sent']);
        resetRateLimit('admin_otp_verify');
        resetRateLimit('admin_otp_request');

        header('Location: /bulldashboard/bull-dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = 'Invalid OTP. Please try again.';
    }

    header('Location: /admin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Comic Byte</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #141519;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #f3f4f6;
        }

        .login-container {
            background-color: #1e2128;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid #2d3139;
        }

        h2 {
            margin-top: 0;
            color: #ffffff;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .otp-input {
            margin: 1.5rem 0;
        }

        .otp-input input {
            width: 100%;
            height: 48px;
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            border: 1px solid #374151;
            border-radius: 8px;
            background-color: #111827;
            color: #ffffff;
            box-sizing: border-box;
        }

        .otp-input input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }

        .btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #2563eb;
        }

        .message {
            margin: 1rem 0;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .success {
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .error {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .resend {
            margin-top: 1.25rem;
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .resend a {
            color: #60a5fa;
            text-decoration: none;
        }

        .resend a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="message success"><?php echo e($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="message error"><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (empty($_SESSION['otp_sent'])): ?>
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <p style="color: #9ca3af; margin-bottom: 1.5rem;">Click the button below to receive an authentication OTP on your verified admin address.</p>
                <button type="submit" name="request_otp" class="btn">Send Verification Code</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <p style="color: #9ca3af; margin-bottom: 1rem;">Enter the 6-digit OTP sent to your admin email:</p>
                <div class="otp-input">
                    <input type="text" name="otp" maxlength="6" pattern="\d{6}" title="Please enter exactly 6 digits" required autofocus autocomplete="one-time-code">
                </div>
                <button type="submit" name="verify_otp" class="btn">Verify & Sign In</button>
                <div class="resend">
                    Didn't receive OTP? <a href="/admin.php">Request again</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
