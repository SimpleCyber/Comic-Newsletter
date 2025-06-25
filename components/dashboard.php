<?php
$startId = isset($_GET['startId']) ? (int)$_GET['startId'] : 3104;
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'grid'; // 'grid' or 'list'
$batchSize = 5; // Load 5 comics at a time
?>

<div class="container mx-auto px-4 py-8">
    <?php if (!$isLoggedIn): ?>
        <!-- Not Logged In Content -->
        <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-2xl p-8 text-white mb-8 shadow-lg text-center">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-4xl font-bold mb-4">Welcome to COMIC BYTE  <br /> <span class="text-[1.5rem]">Short Comics </span></h1>
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
            <!-- <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Latest XKCD Comics</h2> -->
            
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
    <?php endif; ?>

    <!-- Comics Container - Same for both logged in/out -->
      <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Latest XKCD Comics</h2>
    <div id="comics-container" class="<?php echo $viewMode === 'list' ? 'max-w-2xl mx-auto grid grid-cols-1 md:grid-cols-1 gap-12' : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'; ?>">
        <!-- Comics will be loaded here by JavaScript -->
        
    </div>

    <!-- Loading Indicator -->
    <div id="loading-indicator" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-gray-600 dark:text-gray-300">Loading comics...</p>
    </div>

    <!-- Navigation Controls at Bottom (for logged-in users) -->
    <?php if ($isLoggedIn): ?>
        <div class="flex justify-between mt-6">
            <a href="?page=dashboard&startId=<?php echo $startId + $batchSize; ?>&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg <?php echo $startId >= 3104 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'; ?>"
               <?php echo $startId >= 3104 ? 'disabled' : ''; ?>>
                Newer Comics
            </a>
            <a href="?page=dashboard&startId=<?php echo $startId - $batchSize; ?>&view=<?php echo $viewMode; ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg <?php echo ($startId - $batchSize) < 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'; ?>"
               <?php echo ($startId - $batchSize) < 1 ? 'disabled' : ''; ?>>
                Older Comics
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('comics-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    let currentStartId = <?php echo $startId; ?>;
    const batchSize = <?php echo $batchSize; ?>;
    const viewMode = '<?php echo $viewMode; ?>';
    let isLoading = false;
    let hasMore = true;

    // Initial load
    loadComics();

    // Function to load comics
    async function loadComics() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        loadingIndicator.style.display = 'block';

        try {
            const response = await fetch(`/api/fetch-comics.php?startId=${currentStartId}&count=${batchSize}`);
            const comics = await response.json();

            if (comics.length === 0) {
                hasMore = false;
                loadingIndicator.style.display = 'none';
                return;
            }

            comics.forEach(comic => {
                const comicElement = createComicElement(comic);
                container.appendChild(comicElement);
            });

            currentStartId = Math.min(...comics.map(c => c.num)) - 1;
            
        } catch (error) {
            console.error('Error loading comics:', error);
        } finally {
            isLoading = false;
            loadingIndicator.style.display = 'none';
        }
    }

    // Function to create comic element
    function createComicElement(comic) {
        const div = document.createElement('div');
        div.className = viewMode === 'list' ? 
            'bg-card-light dark:bg-card-dark rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-600' : 
            'bg-card-light dark:bg-card-dark rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-600';

        div.innerHTML = `
            <div class="p-4">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-2">${escapeHtml(comic.safe_title)}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                    Published: ${comic.day}/${comic.month}/${comic.year}
                </p>
                <img src="${escapeHtml(comic.img)}" 
                     alt="${escapeHtml(comic.alt)}"
                     class="w-full h-auto rounded-md mb-3">
                <p class="text-sm text-gray-600 dark:text-gray-300 italic">
                    ${escapeHtml(comic.alt)}
                </p>
                <div class="mt-4 flex justify-between items-center">
                    <span class="text-xs text-gray-500">#${comic.num}</span>
                    <a href="https://xkcd.com/${comic.num}/" 
                       target="_blank" 
                       class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                        View on XKCD
                    </a>
                </div>
            </div>
        `;
        return div;
    }

    // Simple HTML escape function
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Infinite scroll (optional)
    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
            loadComics();
        }
    });
});
</script>