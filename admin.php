<?php
require_once 'config.php';

// Check if user is already logged in and is an admin
if (!empty($_SESSION['email']) && isAdminEmail($_SESSION['email'])) {
    $_SESSION['admin_authenticated'] = true;
}

// Redirect if already authenticated
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 25); // Increased timeout for serverless/cold start

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

    $rawAdminEmails = env('ADMIN_EMAIL', 'satyamyada9uv@gmail.com');
    $adminEmailList = array_map('trim', explode(',', $rawAdminEmails));
    $email = $adminEmailList[0] ?? 'satyamyada9uv@gmail.com';
    $otp = generateOTP();

    // Store OTP in session with expiration time (10 minutes)
    $_SESSION['admin_otp'] = $otp;
    $_SESSION['admin_otp_expiry'] = time() + 600;

    if (sendOTP($email, $otp)) {
        $_SESSION['otp_sent'] = true;
        $_SESSION['message'] = "OTP has been sent to " . htmlspecialchars($email);
    } else {
        $_SESSION['error'] = "Mail service is currently unavailable or waking up. Please use 'Sign in with Google' above for instant admin access.";
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
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['is_admin'] = true;
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
    <title>Admin Login - COMIC BYTE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Firebase SDKs for Google Admin Authentication -->
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>
    <script src="./api/firebase-config.php"></script>
    <script src="./asset/firebase-config.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #141519;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #f3f4f6;
        }

        .login-container {
            background-color: #1e2128;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 440px;
            text-align: center;
            border: 1px solid #2d3139;
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
    </style>
</head>

<body>
    <div class="login-container">
        <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-shield-alt text-white text-xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-white mb-2">Admin Portal</h2>
        <p class="text-sm text-gray-400 mb-6">Manage comics, email queues, and subscribers</p>

        <!-- Alerts -->
        <div id="firebaseError" class="hidden mb-4 p-3 bg-red-900/30 border border-red-500/50 rounded-lg text-red-300 text-sm">
            <p id="firebaseErrorText"></p>
        </div>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="mb-4 p-3 bg-green-900/30 border border-green-500/50 rounded-lg text-green-300 text-sm">
                <?php echo e($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="mb-4 p-3 bg-red-900/30 border border-red-500/50 rounded-lg text-red-300 text-sm">
                <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Primary: Google Sign-In for Admin -->
        <button type="button" id="googleAdminBtn"
            class="w-full flex items-center justify-center gap-3 bg-white hover:bg-gray-100 text-gray-800 font-semibold py-3 px-4 rounded-xl transition duration-200 shadow-md cursor-pointer mb-6">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span id="googleAdminText">Sign in with Google (Admin)</span>
        </button>

        <div class="relative flex items-center justify-center mb-6">
            <div class="border-t border-gray-700 w-full"></div>
            <span class="bg-[#1e2128] px-3 text-xs text-gray-500 uppercase tracking-wider">or via email OTP</span>
            <div class="border-t border-gray-700 w-full"></div>
        </div>

        <!-- Secondary: Email OTP Fallback -->
        <?php if (empty($_SESSION['otp_sent'])): ?>
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <p class="text-xs text-gray-400 mb-4">Click below to send an emergency verification code to your verified admin mailbox.</p>
                <button type="submit" name="request_otp"
                    class="w-full bg-gray-800 hover:bg-gray-700 border border-gray-700 text-gray-200 font-medium py-2.5 px-4 rounded-xl transition duration-200">
                    <i class="fas fa-envelope mr-2 text-sm text-gray-400"></i>Send Verification Code
                </button>
            </form>
        <?php else: ?>
            <form method="POST">
                <?php echo getCsrfField(); ?>
                <p class="text-xs text-gray-300 mb-3">Enter the 6-digit code sent to your admin email:</p>
                <div class="otp-input mb-4">
                    <input type="text" name="otp" maxlength="6" pattern="\d{6}" title="Please enter 6 digits" required autofocus autocomplete="one-time-code">
                </div>
                <button type="submit" name="verify_otp"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 px-4 rounded-xl transition duration-200">
                    Verify Code & Access Panel
                </button>
                <div class="mt-4 text-xs text-gray-400">
                    Didn't receive code? <a href="/admin.php" class="text-blue-400 hover:underline">Request again</a>
                </div>
            </form>
        <?php endif; ?>

        <div class="mt-6 pt-4 border-t border-gray-800 text-xs text-gray-500">
            <a href="/index.php" class="hover:text-gray-300"><i class="fas fa-arrow-left mr-1"></i>Back to Home</a>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const csrfToken = $('input[name="csrf_token"]').val();

            function showError(msg) {
                $('#firebaseErrorText').text(msg);
                $('#firebaseError').removeClass('hidden');
            }

            // Handle Google Admin Login
            $('#googleAdminBtn').click(async function() {
                const btn = $(this);
                const btnText = $('#googleAdminText');

                $('#firebaseError').addClass('hidden');
                btn.prop('disabled', true).addClass('opacity-75');
                btnText.text('Verifying Admin Credentials...');

                try {
                    if (!auth) throw new Error("Firebase Auth is not initialized. Check .env");
                    const provider = new firebase.auth.GoogleAuthProvider();
                    provider.addScope('profile');
                    provider.addScope('email');

                    const result = await auth.signInWithPopup(provider);
                    const user = result.user;
                    const idToken = await user.getIdToken();

                    const response = await fetch('/api/firebase-auth.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            uid: user.uid,
                            email: user.email,
                            name: user.displayName || '',
                            photoURL: user.photoURL || '',
                            provider: 'google.com',
                            idToken: idToken,
                            csrf_token: csrfToken,
                            from_admin: true
                        })
                    });

                    const res = await response.json();
                    if (res.status === 'success' && res.is_admin) {
                        btnText.text('Access Granted! Redirecting...');
                        window.location.href = res.redirect || '/bulldashboard/bull-dashboard.php';
                    } else {
                        showError(res.message || 'Access Denied: This account is not an authorized administrator.');
                        btn.prop('disabled', false).removeClass('opacity-75');
                        btnText.text('Sign in with Google (Admin)');
                    }
                } catch (err) {
                    console.error("Admin Auth Error:", err);
                    showError(err.message || 'Authentication error. Please try again.');
                    btn.prop('disabled', false).removeClass('opacity-75');
                    btnText.text('Sign in with Google (Admin)');
                }
            });
        });
    </script>
</body>

</html>
