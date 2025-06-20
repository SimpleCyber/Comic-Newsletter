<?php if (!isset($isLoggedIn)) $isLoggedIn = isLoggedIn(); ?>
<?php if ($isLoggedIn && !isset($user)) $user = getUserData($pdo, $_SESSION['user_id']); ?>

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
                    <h1 class="text-lg font-bold">COMIC BYTE</h1>
                    <p class="text-sm text-gray-300">Short Comics</p>
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
        <a href="?page=dashboard" data-page="dashboard" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $page === 'dashboard' ? 'bg-sidebar-hover text-white' : 'text-gray-300 hover:bg-sidebar-hover hover:text-white' ?> transition-colors">
            <i class="fas fa-chart-line mr-3 text-base"></i>
            Dashboard
        </a>
        <a href="?page=random-comic" data-page="random-comic" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $page === 'random-comic' ? 'bg-sidebar-hover text-white' : 'text-gray-300 hover:bg-sidebar-hover hover:text-white' ?> transition-colors">
            <i class="fas fa-graduation-cap mr-3 text-base"></i>
            Get Random Comics
        </a>
        <a href="?page=newsletter" data-page="newsletter" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $page === 'newsletter' ? 'bg-sidebar-hover text-white' : 'text-gray-300 hover:bg-sidebar-hover hover:text-white' ?> transition-colors">
            <i class="fas fa-newspaper mr-3 text-base"></i>
            Daily Newsletter
        </a>
        <a href="<?php echo $isLoggedIn ? '?page=bookmarked-comics' : 'login.php'; ?>" 
            data-page="bookmarked-comics" 
            class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-lg <?php echo $page === 'bookmarked-comics' ? 'bg-sidebar-hover text-white' : 'text-gray-300 hover:bg-sidebar-hover hover:text-white' ?> transition-colors">
                <i class="fas fa-trophy mr-3 text-base"></i>
                Bookmarked Comics
        </a>
    </div>
</nav>

    <!-- User Menu at Bottom -->
    <div class="p-4 dark:border-gray-700 flex-shrink-0">
        <?php if ($isLoggedIn): ?>
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
                      
                        <a href="logout.php" class="w-full flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <a href="/login.php" class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                <i class="fas fa-sign-in-alt mr-3 text-base"></i>
                Login to Access
            </a>
        <?php endif; ?>
    </div>
</div>