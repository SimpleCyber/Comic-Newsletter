<?php
$startId = isset($_GET['startId']) ? (int)$_GET['startId'] : 3104;
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'grid'; // 'grid' or 'list'
$comics = fetchMultipleXkcdComics($startId, 10);
?>

<div class="container mx-auto px-4 py-8">
    <?php if (!$isLoggedIn): ?>
        <!-- Not Logged In Content -->
        <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-2xl p-8 text-white mb-8 shadow-lg text-center">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-4xl font-bold mb-4">Welcome to COMIC BYTE Short Comics</h1>
                <p class="text-blue-100 text-lg mb-6">Join thousands of users getting the best comics here</p>

                 <a href="/index.php?page=random-comic" class="inline-flex items-center space-x-2 px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-random mr-2"></i>
                    <span>Random Comic</span>
                </a>

                <a href="/login.php" class="inline-flex items-center space-x-2 px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>

               
            </div>
        </div>
    <?php else: ?>
        <!-- Logged In Dashboard Content -->
        <div class="flex justify-between items-center mb-8 -mt-4">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Latest XKCD Comics</h2>
            
            <!-- View Toggle (Desktop Only) -->
            <div class="hidden lg:flex items-center space-x-2">
                <a href="?page=dashboard&startId=<?php echo $startId; ?>&view=grid" 
                   class="px-3 py-1 rounded-md text-sm font-medium transition-colors <?php echo $viewMode === 'grid' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                    <i class="fas fa-th"></i> 
                </a>
                <a href="?page=dashboard&startId=<?php echo $startId; ?>&view=list" 
                   class="px-3 py-1 rounded-md text-sm font-medium transition-colors <?php echo $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                    <i class="fas fa-list"></i> 
                </a>
            </div>
        </div>

        <!-- Comics Display -->
        <?php if ($viewMode === 'list'): ?>
            <!-- List View (Desktop) - Centered like medium screen -->
            <div class="max-w-2xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-1 gap-12">
                    <?php foreach ($comics as $comic): ?>
                        <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-600">
                            <div class="p-4">
                                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2"><?php echo htmlspecialchars($comic['safe_title']); ?></h2>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                    Published: <?php echo "{$comic['day']}/{$comic['month']}/{$comic['year']}"; ?>
                                </p>
                                <img src="<?php echo htmlspecialchars($comic['img']); ?>" 
                                     alt="<?php echo htmlspecialchars($comic['alt']); ?>"
                                     class="w-full h-auto rounded-md mb-3">
                                <p class="text-sm text-gray-600 dark:text-gray-300 italic">
                                    <?php echo htmlspecialchars($comic['alt']); ?>
                                </p>
                                <div class="mt-4 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">#<?php echo $comic['num']; ?></span>
                                    <a href="https://xkcd.com/<?php echo $comic['num']; ?>/" 
                                       target="_blank" 
                                       class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                        View on XKCD
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid View (Default) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($comics as $comic): ?>
                    <div class="bg-card-light dark:bg-card-dark rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-600">
                        <div class="p-4">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2"><?php echo htmlspecialchars($comic['safe_title']); ?></h2>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                Published: <?php echo "{$comic['day']}/{$comic['month']}/{$comic['year']}"; ?>
                            </p>
                            <img src="<?php echo htmlspecialchars($comic['img']); ?>" 
                                 alt="<?php echo htmlspecialchars($comic['alt']); ?>"
                                 class="w-full h-auto rounded-md mb-3">
                            <p class="text-sm text-gray-600 dark:text-gray-300 italic">
                                <?php echo htmlspecialchars($comic['alt']); ?>
                            </p>
                            <div class="mt-4 flex justify-between items-center">
                                <span class="text-xs text-gray-500">#<?php echo $comic['num']; ?></span>
                                <a href="https://xkcd.com/<?php echo $comic['num']; ?>/" 
                                   target="_blank" 
                                   class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                    View on XKCD
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Navigation Controls at Bottom -->
        <div class="flex justify-between mt-6">
            <a href="?page=dashboard&startId=<?php echo $startId + 9; ?>&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg <?php echo $startId >= 3104 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'; ?>"
               <?php echo $startId >= 3104 ? 'disabled' : ''; ?>>
                Newer Comics
            </a>
            <a href="?page=dashboard&startId=<?php echo $startId - 9; ?>&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg <?php echo ($startId - 9) < 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'; ?>"
               <?php echo ($startId - 9) < 1 ? 'disabled' : ''; ?>>
                Older Comics
            </a>
        </div>
    <?php endif; ?>
</div>