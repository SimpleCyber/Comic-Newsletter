<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
$user = null;
$isLoggedIn = isLoggedIn();
if ($isLoggedIn) {
    $user = getUserData($pdo, $_SESSION['user_id']);
}

// Determine which page to show (default to dashboard)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$validPages = ['dashboard', 'random-comic', 'newsletter', 'bookmarked-comics'];

// Validate the page parameter
if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - COMIC BYTE Short Comics</title>
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
    <link rel="stylesheet" href="./asset/index.css">

    
</head>
<body class="bg-back-light dark:bg-back-dark font-sans">
    <div class="flex min-h-screen">
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-[250px]">
            <?php include 'components/header.php'; ?>
            
            <!-- Dynamic Content Area -->
            <main class="flex-1 p-6 sm:px-6 lg:px-40 bg-back-light dark:bg-back-dark overflow-y-auto">
                <?php 
                // Include the appropriate component based on the page parameter
                $componentFile = "components/{$page}.php";
                if (file_exists($componentFile)) {
                    include $componentFile;
                } else {
                    include 'components/dashboard.php';
                }
                ?>
            </main>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>

    <script src="./asset/index.js"></script>
</body>
</html>