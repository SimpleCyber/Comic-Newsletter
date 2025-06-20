<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    if (empty($login) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'Invalid email or password';
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
    <title>Sign In - Copywriting Course</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    <div class="flex min-h-screen">
        <!-- Left side - Form -->
        <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                   

                    <!-- Title -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
                        <p class="text-gray-600 mt-2">Sign in to your account</p>
                    </div>

                    <!-- Error Message -->
                    <?php if ($error): ?>
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                                Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" id="login" name="login" required
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                    placeholder="Enter your email"
                                    value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
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

                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-600">Remember me</span>
                            </label>
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-500">Forgot password?</a>
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-colors font-medium">
                            Sign In
                        </button>
                    </form>

                   

                    

                    <!-- Sign up link -->
                    <p class="text-center text-sm text-gray-600 mt-6">
                        Don't have an account?
                        <a href="signup.php" class="text-blue-600 hover:text-blue-500 font-medium">Sign up</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right side - Image/Illustration -->
        <div class="hidden lg:block lg:flex-1 relative">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-purple-700"></div>
            <div class="relative h-full flex items-center justify-center p-12">
                <div class="text-center text-white">
                    <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-graduation-cap text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold mb-4">Master the Art of Copywriting</h2>
                    <p class="text-xl opacity-90 mb-8">Join thousands of writers who've transformed their careers</p>
                    <div class="flex items-center justify-center space-x-8">
                        <div class="text-center">
                            <div class="text-2xl font-bold">10K+</div>
                            <div class="text-sm opacity-75">Students</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">95%</div>
                            <div class="text-sm opacity-75">Success Rate</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">4.9★</div>
                            <div class="text-sm opacity-75">Rating</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>