<?php
require_once 'config.php';

$error = '';
$success = '';

// Function to send OTP email
function sendOTP($email, $otp)
{
    $url = env('MAIL_SERVICE_URL', 'https://python-mailsend.onrender.com/send-email');

    $subject = 'Your Verification Code';
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $body = "
        <div style='font-family: sans-serif; padding: 15px;'>
            <p>Your verification code is: <strong style='font-size: 1.25rem;'>{$safeOtp}</strong></p>
            <p>This code will expire in 10 minutes.</p>
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_status === 200;
}

// Handle AJAX OTP verification
if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    header('Content-Type: application/json');

    if (!verifyCsrfToken()) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid session security token. Please refresh.']);
        exit();
    }

    if (!checkRateLimit('signup_verify_otp', 5, 600)) {
        $remaining = getRateLimitRemainingTime('signup_verify_otp');
        echo json_encode(['status' => 'error', 'message' => "Too many attempts. Please wait {$remaining} seconds."]);
        exit();
    }

    $user_otp = trim($_POST['otp'] ?? '');
    $stored_otp = $_SESSION['otp'] ?? '';
    $otp_expiry = $_SESSION['otp_expiry'] ?? 0;

    if (empty($user_otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter the OTP']);
        exit();
    } elseif (time() > $otp_expiry) {
        echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one.']);
        exit();
    } elseif (!hash_equals((string)$stored_otp, (string)$user_otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP. Please try again.']);
        exit();
    } else {
        $_SESSION['email_verified'] = true;
        resetRateLimit('signup_verify_otp');
        echo json_encode(['status' => 'success', 'message' => 'Email verified successfully!']);
        exit();
    }
}

// Handle AJAX send OTP request
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    header('Content-Type: application/json');

    if (!verifyCsrfToken()) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid session token. Please reload the page.']);
        exit();
    }

    if (!checkRateLimit('signup_send_otp', 3, 600)) {
        $remaining = getRateLimitRemainingTime('signup_send_otp');
        echo json_encode(['status' => 'error', 'message' => "Too many OTP requests. Please wait {$remaining} seconds."]);
        exit();
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
        exit();
    }

    // Check if email already exists
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists']);
            exit();
        }
    }

    // Generate secure OTP
    $otp = strval(random_int(100000, 999999));
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 600;
    $_SESSION['email_to_verify'] = $email;

    if (sendOTP($email, $otp)) {
        echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully!']);
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP. Please try again later.']);
        exit();
    }
}

// Handle final form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $full_name = cleanInput($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($full_name) || !$email || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields with valid information';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must be at least 8 characters long and contain both letters and numbers';
        } elseif (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true || $_SESSION['email_to_verify'] !== $email) {
            $error = 'Please verify your email with the OTP first';
        } elseif (!$pdo) {
            $error = 'Database service unavailable. Please check back shortly.';
        } else {
            // Check again for uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");

                if ($stmt->execute([$full_name, $email, $hashed_password])) {
                    $newUserId = $pdo->lastInsertId();

                    // Clean sensitive session state
                    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['email_to_verify'], $_SESSION['email_verified']);

                    // Regenerate session ID upon login
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;

                    header('Location: /index.php');
                    exit();
                } else {
                    $error = 'Error creating account. Please try again.';
                }
            }
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - COMIC BYTE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Firebase App, Auth & Firestore SDKs -->
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>
    <script src="./api/firebase-config.php"></script>
    <script src="./asset/firebase-config.js"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="flex min-h-screen">
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-10">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Title -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
                        <p class="text-gray-600 mt-2">Join COMIC BYTE Short Comics today</p>
                    </div>

                    <!-- Error Alert -->
                    <div id="firebaseError" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p id="firebaseErrorText" class="text-red-600 text-sm"></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-600 text-sm"><?php echo e($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-green-600 text-sm"><?php echo e($success); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Google Authentication Option -->
                    <button type="button" id="googleSignUpBtn"
                        class="w-full flex items-center justify-center gap-3 bg-white border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-100 transition-all font-medium text-gray-700 shadow-sm cursor-pointer">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span id="googleBtnText">Sign up with Google</span>
                    </button>

                    <div class="relative flex items-center justify-center my-6">
                        <div class="border-t border-gray-200 w-full"></div>
                        <span class="bg-white px-3 text-xs text-gray-500 uppercase tracking-wider">or with email verification</span>
                        <div class="border-t border-gray-200 w-full"></div>
                    </div>

                    <!-- Registration Form -->
                    <form method="POST" class="space-y-4" id="registrationForm">
                        <?php echo getCsrfField(); ?>
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" id="full_name" name="full_name" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Enter your full name"
                                    value="<?php echo isset($_POST['full_name']) ? e($_POST['full_name']) : ''; ?>">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" required
                                    class="w-full pl-10 pr-28 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Enter your email"
                                    value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>">
                                <button type="button" id="verifyEmailBtn"
                                    class="absolute right-2 top-2 px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-md hover:bg-purple-700 transition-colors">
                                    Send Code
                                </button>
                            </div>
                            <div id="emailVerifyStatus" class="text-xs mt-1 font-medium"></div>
                        </div>

                        <!-- OTP Verification Field (Hidden initially) -->
                        <div id="otpField" class="hidden">
                            <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">
                                6-Digit Verification Code
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input type="text" id="otp" name="otp" maxlength="6" pattern="\d{6}"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Enter 6-digit code">
                                <div id="otpVerifyStatus" class="text-xs mt-1 font-medium"></div>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password (min. 8 chars, letters & numbers)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" required minlength="8"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Create a strong password">
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Confirm your password">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 transition-colors font-medium">
                            Create Account
                        </button>
                    </form>

                    <p class="text-center text-sm text-gray-600 mt-6">
                        Already have an account?
                        <a href="/login.php" class="text-purple-600 hover:text-purple-500 font-medium">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const csrfToken = $('input[name="csrf_token"]').val();

            function showError(msg) {
                $('#firebaseErrorText').text(msg);
                $('#firebaseError').removeClass('hidden');
            }

            // Google Sign-Up click handler
            $('#googleSignUpBtn').click(async function() {
                const btn = $(this);
                const btnText = $('#googleBtnText');
                
                $('#firebaseError').addClass('hidden');
                btn.prop('disabled', true).addClass('opacity-75');
                btnText.text('Connecting with Google...');

                try {
                    const result = await signInWithGoogle(csrfToken);
                    if (result.status === 'success') {
                        btnText.text('Success! Redirecting...');
                        window.location.href = result.redirect || '/index.php';
                    } else {
                        showError(result.message || 'Google signup failed. Please try again.');
                        btn.prop('disabled', false).removeClass('opacity-75');
                        btnText.text('Sign up with Google');
                    }
                } catch (err) {
                    console.error("Google Auth error:", err);
                    showError(err.message || 'Unable to connect with Google.');
                    btn.prop('disabled', false).removeClass('opacity-75');
                    btnText.text('Sign up with Google');
                }
            });

            // Handle Verify Email button click
            $('#verifyEmailBtn').click(function() {
                const email = $('#email').val().trim();

                if (!email) {
                    $('#emailVerifyStatus').html('<span class="text-red-600">Please enter your email first</span>');
                    return;
                }

                $('#emailVerifyStatus').html('<span class="text-purple-600">Sending OTP...</span>');
                $('#verifyEmailBtn').prop('disabled', true).addClass('opacity-50');

                $.ajax({
                    url: '/signup.php',
                    type: 'POST',
                    data: {
                        action: 'send_otp',
                        email: email,
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        const res = typeof response === 'object' ? response : JSON.parse(response);
                        if (res.status === 'success') {
                            $('#emailVerifyStatus').html('<span class="text-green-600">' + res.message + '</span>');
                            $('#otpField').removeClass('hidden');
                        } else {
                            $('#emailVerifyStatus').html('<span class="text-red-600">' + res.message + '</span>');
                            $('#verifyEmailBtn').prop('disabled', false).removeClass('opacity-50');
                        }
                    },
                    error: function() {
                        $('#emailVerifyStatus').html('<span class="text-red-600">Error sending OTP. Please try again.</span>');
                        $('#verifyEmailBtn').prop('disabled', false).removeClass('opacity-50');
                    }
                });
            });

            // Handle OTP input (real-time verification)
            $('#otp').on('input', function() {
                const otp = $(this).val().trim();

                if (otp.length === 6) {
                    $('#otpVerifyStatus').html('<span class="text-purple-600">Verifying code...</span>');

                    $.ajax({
                        url: '/signup.php',
                        type: 'POST',
                        data: {
                            action: 'verify_otp',
                            otp: otp,
                            csrf_token: csrfToken
                        },
                        success: function(response) {
                            const res = typeof response === 'object' ? response : JSON.parse(response);
                            if (res.status === 'success') {
                                $('#otpVerifyStatus').html('<span class="text-green-600">' + res.message + '</span>');
                                $('#otp').prop('readonly', true).addClass('bg-gray-100');
                                $('#email').prop('readonly', true).addClass('bg-gray-100');
                            } else {
                                $('#otpVerifyStatus').html('<span class="text-red-600">' + res.message + '</span>');
                                $('#otp').val('').attr('placeholder', 'Try again');
                            }
                        },
                        error: function() {
                            $('#otpVerifyStatus').html('<span class="text-red-600">Verification request failed.</span>');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>
