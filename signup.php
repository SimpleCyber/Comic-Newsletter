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
</head>

<body class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="flex min-h-screen">
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Title -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Create Account</h1>
                        <p class="text-gray-600 mt-2">Join COMIC BYTE Short Comics today</p>
                    </div>

                    <!-- Error / Success Messages -->
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
