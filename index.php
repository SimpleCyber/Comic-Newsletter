<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get user data
$user = getUserData($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Copywriting Course</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-pen-nib text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900">COPYWRITING COURSE</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#" class="text-gray-900 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-blog mr-2"></i>Blogs
                    </a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-book mr-2"></i>Courses
                    </a>
                    <a href="#" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-trophy mr-2"></i>Member Wins
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <span class="hidden sm:block text-sm text-gray-600">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</span>
                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-medium">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <button class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobileMenu" class="md:hidden hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="#" class="bg-gray-100 text-gray-900 block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="#" class="text-gray-600 hover:text-gray-900 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-blog mr-2"></i>Blogs
                </a>
                <a href="#" class="text-gray-600 hover:text-gray-900 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-book mr-2"></i>Courses
                </a>
                <a href="#" class="text-gray-600 hover:text-gray-900 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-trophy mr-2"></i>Member Wins
                </a>
                <div class="border-t border-gray-200 pt-4 pb-3">
                    <div class="px-3 py-2">
                        <p class="text-base font-medium text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="mt-3 space-y-1">
                        <a href="#" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                            <i class="fas fa-user mr-2"></i>Profile
                        </a>
                        <a href="#" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                        <a href="logout.php" class="block px-3 py-2 text-base font-medium text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 text-white mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
                    <p class="text-blue-100 text-lg">Ready to continue your copywriting journey?</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold">47%</div>
                        <div class="text-sm text-blue-100">Progress</div>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Lessons Completed</p>
                        <p class="text-2xl font-bold text-gray-900">23</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Hours Studied</p>
                        <p class="text-2xl font-bold text-gray-900">156</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Assignments Done</p>
                        <p class="text-2xl font-bold text-gray-900">18</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Streak Days</p>
                        <p class="text-2xl font-bold text-gray-900">12</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-fire text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Activity</h2>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Completed "Email Marketing Fundamentals"</p>
                                <p class="text-xs text-gray-500">2 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-play text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Started "Sales Page Psychology"</p>
                                <p class="text-xs text-gray-500">Yesterday</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-trophy text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Earned "Copywriting Novice" badge</p>
                                <p class="text-xs text-gray-500">3 days ago</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Continue Learning -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Continue Learning</h2>
                    <div class="space-y-4">
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium text-gray-900">Headlines That Convert</h3>
                                <span class="text-sm text-gray-500">67% complete</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: 67%"></div>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">Learn the psychology behind compelling headlines that grab attention and drive action.</p>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                Continue Lesson
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- User Profile Card -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-2xl font-bold">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                        <p class="text-sm text-gray-600">@<?php echo htmlspecialchars($user['username']); ?></p>
                        <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Member since:</span>
                                <span class="font-medium"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-play mr-2"></i>
                            Start New Lesson
                        </button>
                        <button class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-pen mr-2"></i>
                            Submit Copy for Review
                        </button>
                        <button class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-users mr-2"></i>
                            Join Community
                        </button>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upcoming Events</h3>
                    <div class="space-y-3">
                        <div class="flex items-start space-x-3">
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-red-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Office Hours</p>
                                <p class="text-xs text-gray-500">June 26th, 2pm CST</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-video text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Live Workshop</p>
                                <p class="text-xs text-gray-500">June 28th, 7pm CST</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobileMenu');
            const button = event.target.closest('button');
            
            if (!menu.contains(event.target) && !button?.onclick?.toString().includes('toggleMobileMenu')) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

