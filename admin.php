<?php


require_once 'config.php';


// Function to generate a 6-digit OTP
function generateOTP()
{
    return strval(rand(100000, 999999));
}

// Function to send OTP
function sendOTP($email, $otp)
{
    $url = 'https://python-mailsend.onrender.com/send-email';

    $data = [
        'to' => $email,
        'subject' => 'Your OTP for Admin Login',
        'body' => "Your OTP is: <b>$otp</b><br>This OTP is valid for 10 minutes."
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
        return true;
    } else {
        error_log("Failed to send OTP via API. Response: $response");
        return false;
    }
}


// Handle OTP request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $email = 'satyamyadav9uv@gmail.com';
    $otp = generateOTP();

    // Store OTP in session with expiration time (10 minutes)
    $_SESSION['admin_otp'] = $otp;
    $_SESSION['admin_otp_expiry'] = time() + 600; // 10 minutes from now

    if (sendOTP($email, $otp)) {
        $_SESSION['otp_sent'] = true;
        echo "OTP sent!";
    } else {
        echo "Failed to send OTP.";
    }
    header('Location: admin.php');
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $user_otp = $_POST['otp'] ?? '';

    if (empty($user_otp)) {
        $_SESSION['error'] = 'Please enter the OTP.';
    } elseif (!isset($_SESSION['admin_otp']) || !isset($_SESSION['admin_otp_expiry'])) {
        $_SESSION['error'] = 'OTP expired or not requested. Please request a new OTP.';
    } elseif (time() > $_SESSION['admin_otp_expiry']) {
        $_SESSION['error'] = 'OTP has expired. Please request a new OTP.';
    } elseif ($user_otp === $_SESSION['admin_otp']) {
        // OTP is valid
        $_SESSION['admin_authenticated'] = true;
        unset($_SESSION['admin_otp']);
        unset($_SESSION['admin_otp_expiry']);
        header('Location: /bulldashboard/bull-dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = 'Invalid OTP. Please try again.';
    }
    header('Location: admin.php');
    exit();
}

// Check if already authenticated
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    header('Location: /bulldashboard/bull-dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 350px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 1.5rem;
            color: #1f2937;
        }

        .otp-input {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .otp-input input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 1.2rem;
            border: 1px solid #d1d5db;
            border-radius: 5px;
        }

        .btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #2563eb;
        }

        .btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .message {
            margin: 1rem 0;
            padding: 0.75rem;
            border-radius: 5px;
        }

        .success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .error {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .resend {
            margin-top: 1rem;
            color: #6b7280;
        }

        .resend a {
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message'];
                                            unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['otp_sent'])): ?>
            <form method="POST">
                <p>Click the button below to receive an OTP on your admin email.</p>
                <button type="submit" name="request_otp" class="btn">Send OTP</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <p>Enter the 6-digit OTP sent to your email:</p>
                <div class="otp-input ">
                    <input type="text" name="otp" maxlength="6" pattern="\d{6}" title="Please enter exactly 6 digits" required autofocus>
                </div>
                <button type="submit" name="verify_otp" class="btn">Verify OTP</button>
                <div class="resend">
                    Didn't receive OTP? <a href="admin.php">Resend</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>