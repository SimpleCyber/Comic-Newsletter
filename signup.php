<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
$email_verified = false;

// Function to send OTP email using PHPMailer
function sendOTP($email, $otp)
{
    $url = 'https://python-mailsend.onrender.com/send-email';

    $subject = 'Your Verification Code';
    $body = "
        <div style='font-family: sans-serif;'>
            <p>Your verification code is: <strong>$otp</strong></p>
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

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 200) {
        return true;
    } else {
        error_log("❌ Failed to send OTP to $email. Response: $response");
        return false;
    }
}


// Handle AJAX OTP verification
if (isset($_POST['action']) && $_POST['action'] == 'verify_otp') {
    $user_otp = trim($_POST['otp']);
    $stored_otp = $_SESSION['otp'] ?? '';
    $otp_expiry = $_SESSION['otp_expiry'] ?? 0;

    if (empty($user_otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter the OTP']);
        exit();
    } elseif (time() > $otp_expiry) {
        echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one.']);
        exit();
    } elseif ($user_otp !== $stored_otp) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP. Please try again.']);
        exit();
    } else {
        $_SESSION['email_verified'] = true;
        echo json_encode(['status' => 'success', 'message' => 'Email verified successfully!']);
        exit();
    }
}

// Handle AJAX send OTP request
if (isset($_POST['action']) && $_POST['action'] == 'send_otp') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address']);
        exit();
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
        exit();
    }

    // Generate OTP
    $otp = strval(rand(100000, 999999));
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 600;
    $_SESSION['email_to_verify'] = $email;

    // Send OTP via PHPMailer
    if (sendOTP($email, $otp)) {
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_code'] = $otp;
    $_SESSION['otp_expires'] = time() + 600;

    echo json_encode([
        'status' => 'success',
        'message' => 'OTP sent successfully.'
    ]);
    exit();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send OTP. Please try again.'
    ]);
    exit();
}

}

// Handle final form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true || $_SESSION['email_to_verify'] !== $email) {
        $error = 'Please verify your email first';
    } else {
        // Create the account
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");

        if ($stmt->execute([$full_name, $email, $hashed_password])) {
            // Clean session
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['email_to_verify'], $_SESSION['email_verified']);

            // Auto login
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['full_name'] = $full_name;
            header('Location: index.php');
            exit();
        } else {
            $error = 'Error creating account. Please try again.';
        }
    }
}

// Redirect if already logged in
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Copywriting Course</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50">
    <div class="flex min-h-screen">
        <!-- Left side - Image/Illustration -->
        <div class="hidden lg:block lg:flex-1 relative">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-blue-700"></div>
            <div class="relative h-full flex items-center justify-center p-12">
                <div class="text-center text-white">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-rocket text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold mb-4">Start Your Journey Today</h2>
                    <p class="text-xl opacity-90 mb-8">Join our community and unlock your copywriting potential</p>
                    <div class="flex items-center justify-center space-x-8">
                        <div class="text-center">
                            <div class="text-2xl font-bold">50+</div>
                            <div class="text-sm opacity-75">Lessons</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">24/7</div>
                            <div class="text-sm opacity-75">Support</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">100%</div>
                            <div class="text-sm opacity-75">Practical</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Form -->
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Title -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
                        <p class="text-gray-600 mt-2">Join our copywriting community</p>
                    </div>

                    <!-- Error/Success Message -->
                    <?php if ($error): ?>
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-green-600 text-sm"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Form -->
                    <form method="POST" class="space-y-4" id="registrationForm">
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
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
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
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Enter your email"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <button type="button" id="verifyEmailBtn" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-purple-600 text-white px-3 py-1 rounded text-sm hover:bg-purple-700 focus:outline-none">
                                    Verify Email
                                </button>
                            </div>
                            <div id="emailVerifyStatus" class="text-sm mt-1"></div>
                        </div>

                        <!-- OTP Verification Field (hidden by default) -->
                        <div id="otpField" class="hidden">
                            <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">
                                6-digit Verification Code
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400"></i>
                                </div>
                                <input type="text" id="otp" name="otp" maxlength="6"
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Enter 6-digit code">
                                <div id="otpVerifyStatus" class="text-sm mt-1"></div>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" id="password" name="password" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Create a password">
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
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Confirm your password">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 transition-colors font-medium">
                            Create Account
                        </button>
                    </form>

                    <!-- Sign in link -->
                    <p class="text-center text-sm text-gray-600 mt-6">
                        Already have an account?
                        <a href="login.php" class="text-purple-600 hover:text-purple-500 font-medium">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Handle Verify Email button click
            $('#verifyEmailBtn').click(function() {
                const email = $('#email').val();

                if (!email) {
                    $('#emailVerifyStatus').html('<span class="text-red-600">Please enter your email first</span>');
                    return;
                }

                $('#emailVerifyStatus').html('<span class="text-purple-600">Sending OTP...</span>');

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        action: 'send_otp',
                        email: email
                    },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            $('#emailVerifyStatus').html('<span class="text-green-600">' + res.message + '</span>');
                            $('#otpField').removeClass('hidden');
                            $('#verifyEmailBtn').prop('disabled', true).addClass('bg-gray-400').removeClass('bg-purple-600 hover:bg-purple-700');
                        } else {
                            $('#emailVerifyStatus').html('<span class="text-red-600">' + res.message + '</span>');
                        }
                    },
                    error: function() {
                        $('#emailVerifyStatus').html('<span class="text-red-600">Error sending OTP. Please try again.</span>');
                    }
                });
            });

            // Handle OTP input (real-time verification)
            $('#otp').on('input', function() {
                const otp = $(this).val();

                if (otp.length === 6) {
                    $('#otpVerifyStatus').html('<span class="text-purple-600">Verifying...</span>');

                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: {
                            action: 'verify_otp',
                            otp: otp
                        },
                        success: function(response) {
                            const res = JSON.parse(response);
                            if (res.status === 'success') {
                                $('#otpVerifyStatus').html('<span class="text-green-600">' + res.message + '</span>');
                            } else {
                                $('#otpVerifyStatus').html('<span class="text-red-600">' + res.message + '</span>');
                                $('#otp').val('').attr('placeholder', 'Try again');
                            }
                        },
                        error: function() {
                            $('#otpVerifyStatus').html('<span class="text-red-600">Verification failed. Please try again.</span>');
                        }
                    });
                } else if (otp.length > 6) {
                    $(this).val(otp.substring(0, 6));
                }
            });
        });
    </script>
</body>

</html>