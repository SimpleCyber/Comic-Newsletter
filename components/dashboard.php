<!-- Dashboard Content -->
<main class="flex-1 p-6 sm:px-6 lg:px-40 bg-back-light dark:bg-back-dark overflow-y-auto">
    <?php if (!$isLoggedIn): ?>
        <!-- Not Logged In Content -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-gray-800 dark:text-gray-200 mb-8 shadow-md text-center border border-gray-200 dark:border-gray-700">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-3xl font-bold mb-4">Welcome to COMIC BYTE</h1>
                <p class="text-gray-600 dark:text-gray-300 text-lg mb-6">Join our community of readers enjoying the best short comics</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="/index.php?page=random-comic" class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-book-open"></i>
                        <span>Read Comics</span>
                    </a>
                    <a href="/login.php" class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Logged In Content -->
        <div class="text-gray-800 dark:text-gray-200">
            <h2 class="text-2xl font-semibold mb-4">Your Comics Dashboard</h2>
            <!-- Add your logged-in content here -->
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Your content goes here -->
    </div>
</main>