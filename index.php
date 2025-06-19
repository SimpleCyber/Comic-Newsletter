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
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'sidebar-light': '#1b2435',
                        'sidebar-dark': '#1e2128',
                        'sidebar-hover': '#ff2d36',
                        'back-light': '#eff2f7',
                        'back-dark': '#141519',
                        'card-light': '#ffffff',
                        'card-dark': '#1e2128',
                        'footer-light': '#27364b',
                        'footer-dark': '#1e2128'
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --sidebar-light: #1b2435;
            --sidebar-dark: #1e2128;
            --sidebar-hover: #ff2d36;
            --back-light: #eff2f7;
            --back-dark: #141519;
            --card-light: #ffffff;
            --card-dark: #1e2128;
            --footer-light: #27364b;
            --footer-dark: #1e2128;
        }
        
        .dark {
            --sidebar-color: var(--sidebar-dark);
            --back-color: var(--back-dark);
            --card-color: var(--card-dark);
            --footer-color: var(--footer-dark);
        }
        
        :not(.dark) {
            --sidebar-color: var(--sidebar-light);
            --back-color: var(--back-light);
            --card-color: var(--card-light);
            --footer-color: var(--footer-light);
        }
    </style>
</head>
<body class="bg-back-light dark:bg-back-dark font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar Overlay for Mobile -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>
        
        <!-- Sidebar -->
        <div id="sidebar" class="fixed lg:relative w-72 h-screen bg-sidebar-light dark:bg-sidebar-dark text-white flex flex-col z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-hidden border-r border-gray-700 ">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-600 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-sidebar-hover rounded-lg flex items-center justify-center">
                            <i class="fas fa-pen-nib text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold">COPYWRITING</h1>
                            <p class="text-sm text-gray-300">COURSE</p>
                        </div>
                    </div>
                    <button id="close-sidebar" class="lg:hidden text-gray-300 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-6 overflow-y-auto">
                <div class="space-y-2 px-4">
                    <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-sidebar-hover text-white">
                        <i class="fas fa-chart-line mr-3 text-base"></i>
                        Dashboard
                    </a>
                    <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-sidebar-hover hover:text-white transition-colors">
                        <i class="fas fa-graduation-cap mr-3 text-base"></i>
                        Join Copywriting Course
                    </a>
                    <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-sidebar-hover hover:text-white transition-colors">
                        <i class="fas fa-newspaper mr-3 text-base"></i>
                        Weekly Newsletter
                    </a>
                    <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-sidebar-hover hover:text-white transition-colors">
                        <i class="fas fa-trophy mr-3 text-base"></i>
                        Member Wins
                    </a>
                    <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-sidebar-hover hover:text-white transition-colors">
                        <i class="fas fa-book-open mr-3 text-base"></i>
                        Courses
                    </a>
                    <a href="#" class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-sidebar-hover hover:text-white transition-colors">
                        <i class="fas fa-users mr-3 text-base"></i>
                        Feed (Members Only)
                    </a>
                </div>
            </nav>

            <!-- User Menu at Bottom -->
            <div class="p-4 dark:border-gray-700 flex-shrink-0">
                <div class="relative">
                    <button id="sidebar-user-menu-btn" class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-300 hover:bg-sidebar-hover hover:text-white transition-colors">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                            <span class="text-white text-xs font-medium">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </span>
                        </div>
                        <span class="flex-1 text-left"><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <i class="fas fa-chevron-up text-xs transform transition-transform duration-200" id="sidebar-user-menu-arrow"></i>
                    </button>
                    
                    <!-- User Dropdown -->
                    <div id="sidebar-user-dropdown" class="absolute bottom-full left-0 right-0 mb-2 bg-card-light dark:bg-card-dark rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 hidden">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-600">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="p-2">
                            <button class="w-full flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                                <i class="fas fa-user mr-2"></i>Profile
                            </button>
                            <button class="w-full flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </button>
                            <a href="logout.php" class="w-full flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen sm:px-0 lg:px-40">
            <!-- Top Navigation -->
            <header class=" dark: border-b border-gray-300 dark:border-gray-600  py-2 flex-shrink-0">

                <div class="flex items-center justify-between">
                    <!-- Left Side: Mobile Menu + Breadcrumb -->
                    <div class="flex items-center space-x-4">
                        <!-- Mobile Menu Button -->
                        <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <!-- Breadcrumb -->
                        <nav class="hidden sm:flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                            <a href="#" class="hover:text-gray-900 dark:hover:text-gray-200"><i class="fas fa-home"></i></a>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <span class="font-medium text-gray-900 dark:text-gray-100">Dashboard</span>
                        </nav>
                    </div>

                    <!-- Right Side: Theme Toggle + User Menu -->
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button id="theme-toggle" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-sun text-lg dark:hidden"></i>
                            <i class="fas fa-moon text-lg hidden dark:block"></i>
                        </button>
                        
                        <!-- Welcome Message + User Avatar -->
                        <div class="flex items-center space-x-3">
                            
                            <div class="relative group">
                                <button class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 focus:outline-none">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-medium">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div class="absolute right-0 mt-2 w-48 bg-card-light dark:bg-card-dark rounded-md shadow-lg py-1 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 border border-gray-200 dark:border-gray-600">
                                    <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-600">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-user mr-2"></i>Profile
                                    </a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-cog mr-2"></i>Settings
                                    </a>
                                    <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 p-6 bg-back-light dark:bg-back-dark overflow-y-auto">
                <!-- Welcome Section with Gradient -->
                <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-2xl p-8 text-white mb-8 shadow-lg">
                    <div class="flex flex-col md:flex-row items-center justify-between">
                        <div class="mb-4 md:mb-0">
                            <h1 class="text-3xl font-bold mb-2 flex items-center">
                                Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 
                                <i class="fas fa-hand-wave text-yellow-300 ml-2"></i>
                            </h1>
                            <p class="text-blue-100 text-lg">Ready to continue your copywriting journey?</p>
                        </div>
                        <div class="flex items-center space-x-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold">47%</div>
                                <div class="text-sm text-blue-100">Overall Progress</div>
                            </div>
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Lessons Completed</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">23</p>
                                <p class="text-sm text-green-600 font-medium">+3 this week</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Study Hours</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">156</p>
                                <p class="text-sm text-blue-600 font-medium">12h this week</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clock text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Assignments</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">18</p>
                                <p class="text-sm text-purple-600 font-medium">2 pending</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-file-alt text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Streak</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">12</p>
                                <p class="text-sm text-orange-600 font-medium">days strong!</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                                <i class="fas fa-fire text-orange-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Add your main content here -->
                </div>
            </main>
            <!-- Footer -->
            <footer class="bg-footer-light dark:bg-footer-dark text-white sm:px-20 lg:px-36 p-6 mt-auto flex-shrink-0 ">


                <button id="theme-toggle" class="flex space-x-4 p-2">
                    <i class="fas fa-lightbulb w-9 h-9 p-2 text-gray-400 light:bg-gray-600 rounded-md"></i>
                    <i class="fas fa-moon w-9 h-9 p-2 text-gray-400 dark:bg-gray-600 rounded-md"></i>
                    <i class="fas fa-adjust w-9 h-9 p-2 text-gray-400 "></i>
                </button>




                <div class="border-b border-gray-600 mb-6 mt-2"></div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-6">
                        <a href="#" class="text-gray-300 hover:text-white text-sm transition-colors">Contact Us</a>
                        <a href="#" class="text-gray-300 hover:text-white text-sm transition-colors">Cookies</a>
                    </div>
                    <p class="text-gray-400 text-sm">© Copywriting Course 2025</p>
                </div>
            </footer>
        </div>
    </div>

    <script>

        
        console.log('Copywriting Course Dashboard Loaded');

        // Dark mode toggle functionality
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;

        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        html.classList.toggle('dark', currentTheme === 'dark');

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const theme = html.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
        });

        // Mobile sidebar functionality
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const closeSidebar = document.getElementById('close-sidebar');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        function closeSidebarFn() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }

        mobileMenuBtn.addEventListener('click', openSidebar);
        closeSidebar.addEventListener('click', closeSidebarFn);
        sidebarOverlay.addEventListener('click', closeSidebarFn);

        // Sidebar user menu dropdown functionality
        const sidebarUserMenuBtn = document.getElementById('sidebar-user-menu-btn');
        const sidebarUserDropdown = document.getElementById('sidebar-user-dropdown');
        const sidebarUserMenuArrow = document.getElementById('sidebar-user-menu-arrow');

        sidebarUserMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = sidebarUserDropdown.classList.contains('hidden');
            sidebarUserDropdown.classList.toggle('hidden', !isHidden);
            sidebarUserMenuArrow.classList.toggle('rotate-180', isHidden);
        });

        // Close sidebar dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebarUserMenuBtn.contains(e.target) && !sidebarUserDropdown.contains(e.target)) {
                sidebarUserDropdown.classList.add('hidden');
                sidebarUserMenuArrow.classList.remove('rotate-180');
            }
        });

        // Close sidebar dropdown when sidebar is closed on mobile
        function closeSidebarFn() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            // Also close the user dropdown
            sidebarUserDropdown.classList.add('hidden');
            sidebarUserMenuArrow.classList.remove('rotate-180');
        }
    </script>
</body>
</html>