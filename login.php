<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (!checkRateLimit('user_login', 5, 900)) {
        $remaining = getRateLimitRemainingTime('user_login');
        $error = "Too many failed login attempts. Please wait {$remaining} seconds before trying again.";
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            $error = 'Please fill in all fields';
        } elseif (!$pdo) {
            $error = 'Database service unavailable. Please check system configuration.';
        } else {
            $stmt = $pdo->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $login;
                if (isAdminEmail($login)) {
                    $_SESSION['admin_authenticated'] = true;
                    $_SESSION['is_admin'] = true;
                }
                resetRateLimit('user_login');
                
                header('Location: /index.php');
                exit();
            } else {
                $error = 'Invalid email or password';
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
    <title>Sign In - COMIC BYTE</title>
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
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    <div class="flex min-h-screen">
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-10">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Title -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
                        <p class="text-gray-600 mt-2">Sign in to your account</p>
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

                    <!-- Google Authentication Option -->
                    <button type="button" id="googleSignInBtn"
                        class="w-full flex items-center justify-center gap-3 bg-white border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-100 transition-all font-medium text-gray-700 shadow-sm cursor-pointer">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span id="googleBtnText">Continue with Google</span>
                    </button>

                    <div class="relative flex items-center justify-center my-6">
                        <div class="border-t border-gray-200 w-full"></div>
                        <span class="bg-white px-3 text-xs text-gray-500 uppercase tracking-wider">or email</span>
                        <div class="border-t border-gray-200 w-full"></div>
                    </div>

                    <!-- Email & Password Form -->
                    <form method="POST" class="space-y-4" id="loginForm">
                        <?php echo getCsrfField(); ?>
                        <div>
                            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="email" id="login" name="login" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                    placeholder="Enter your email"
                                    value="<?php echo isset($_POST['login']) ? e($_POST['login']) : ''; ?>">
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
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                    placeholder="Enter your password">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-colors font-medium">
                            Sign In
                        </button>
                    </form>

                    <!-- Sign up link -->
                    <p class="text-center text-sm text-gray-600 mt-6">
                        Don't have an account?
                        <a href="/signup.php" class="text-blue-600 hover:text-blue-500 font-medium">Sign up</a>
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

            // Google Sign-In click handler
            $('#googleSignInBtn').click(async function() {
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
                        showError(result.message || 'Authentication failed. Please try again.');
                        btn.prop('disabled', false).removeClass('opacity-75');
                        btnText.text('Continue with Google');
                    }
                } catch (err) {
                    console.error("Google Auth error:", err);
                    showError(err.message || 'Unable to connect with Google. Please check your connection.');
                    btn.prop('disabled', false).removeClass('opacity-75');
                    btnText.text('Continue with Google');
                }
            });
        });
    </script>
</body>
</html>
