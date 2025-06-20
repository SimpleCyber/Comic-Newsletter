<?php
// Get a random comic
$comic = fetchRandomXkcdComic();
?>

<div class="container mx-auto px-4 py-4 text-white">    
     <h2 class="max-w-2xl mx-auto text-3xl font-bold text-gray-800 dark:text-white mb-6">Random Comics</h2>
    
    <?php if ($comic): ?>
        <div class="max-w-2xl mx-auto bg-card-light dark:bg-card-dark rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-600">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2"><?php echo htmlspecialchars($comic['safe_title']); ?></h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                    Published: <?php echo "{$comic['day']}/{$comic['month']}/{$comic['year']}"; ?>
                    <span class="ml-2">#<?php echo $comic['num']; ?></span>
                </p>
                <img src="<?php echo htmlspecialchars($comic['img']); ?>" 
                     alt="<?php echo htmlspecialchars($comic['alt']); ?>"
                     class="w-full h-auto rounded-md mb-4 mx-auto">
                <p class="text-sm text-gray-600 dark:text-gray-300 italic mb-4">
                    <?php echo htmlspecialchars($comic['alt']); ?>
                </p>
                <div class="flex justify-center mb-8">
                    <button onclick="window.location.reload()" 
                            class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg shadow-md transition-colors">
                        <i class="fas fa-random mr-2"></i> Get Random Comic
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-8 text-red-500">
            <i class="fas fa-exclamation-triangle fa-2x mb-4"></i>
            <p>Failed to load a random comic. Please try again.</p>
        </div>
    <?php endif; ?>
</div>