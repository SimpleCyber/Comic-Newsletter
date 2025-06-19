<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email or username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $error = 'Email or username already exists';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$full_name, $username, $email, $hashed_password])) {
                $success = 'Account created successfully! You can now sign in.';
                // Auto login
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                header('Location: index.php');
                exit();
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
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
                    <!-- Logo -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center space-x-2">
                            <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-pen-nib text-white text-sm"></i>
                            </div>
                            <span class="text-xl font-bold text-gray-900">COPYWRITING</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">COURSE</p>
                    </div>

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

                    <!-- Form -->
                    <form method="POST" class="space-y-4">
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
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                Username
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-at text-gray-400"></i>
                                </div>
                                <input type="text" id="username" name="username" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
                                    placeholder="Choose a username"
                                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
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

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" required class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                <span class="ml-2 text-sm text-gray-600">
                                    I agree to the <a href="#" class="text-purple-600 hover:text-purple-500">Terms of Service</a> and 
                                    <a href="#" class="text-purple-600 hover:text-purple-500">Privacy Policy</a>
                                </span>
                            </label>
                        </div>

                        <button type="submit"
                            class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 focus:ring-4 focus:ring-purple-200 transition-colors font-medium">
                            Create Account
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="my-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or sign up with</span>
                            </div>
                        </div>
                    </div>

                    <!-- Social Login -->
                    <div class="grid grid-cols-2 gap-3">
                        <button class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fab fa-google text-red-500 mr-2"></i>
                            <span class="text-sm text-gray-700">Google</span>
                        </button>
                        <button class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fab fa-facebook text-blue-600 mr-2"></i>
                            <span class="text-sm text-gray-700">Facebook</span>
                        </button>
                    </div>

                    <!-- Sign in link -->
                    <p class="text-center text-sm text-gray-600 mt-6">
                        Already have an account?
                        <a href="login.php" class="text-purple-600 hover:text-purple-500 font-medium">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>