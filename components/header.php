<!-- Top Navigation -->
<header class="px-6 sm:px-6 lg:px-40 py-2 flex-shrink-0">
    <div class="flex items-center justify-between lg:border-b border-gray-300 dark:border-gray-600 lg:pb-2">
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn"
                class="lg:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <!-- Home icon: hidden on md and smaller, visible on lg+ -->
                <a href="#" class="hidden lg:inline hover:text-gray-900 dark:hover:text-gray-200">
                    <i class="fas fa-home"></i>
                </a>

                <!-- Current page name: always visible -->
                <span id="current-page" class="font-medium text-gray-900 dark:text-gray-100">
                    <?php
                    $pageNames = [
                        'dashboard' => 'Dashboard',
                        'random-comic' => 'Get Random Comics',
                        'newsletter' => 'Daily Newsletter',
                        'bookmarked-comics' => 'Bookmarked Comics',
                    ];
                    echo $pageNames[$page] ?? 'Dashboard';
                    ?>
                </span>

            </nav>
        </div>

        <!-- Right Side: Theme Toggle + User Menu -->
        <div class="flex items-center space-x-4">
            <!-- Dark Mode Toggle -->
            <button id="theme-toggle-header" class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-sun text-lg dark:hidden"></i>
                <i class="fas fa-moon text-lg hidden dark:block"></i>
            </button>

            <!-- Welcome Message + User Avatar -->
            <?php if ($isLoggedIn): ?>
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
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login.php" class="flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>