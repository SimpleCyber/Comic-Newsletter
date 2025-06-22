<?php
require_once '../config.php';
date_default_timezone_set('Asia/Kolkata');

$section = $_GET['section'] ?? 'postmails';
$filter = $_GET['status'] ?? 'latest';

$allowed_status = ['latest', 'active', 'waiting', 'completed', 'failed', 'delayed', 'paused'];
if (!in_array($filter, $allowed_status)) $filter = 'latest';

$section = in_array($section, ['postmails', 'users', 'loggedin']) ? $section : 'postmails';

// Retry / Pause / Delete / Execute / Resume
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['retry'])) {
        $id = $_POST['retry'];
        $stmt = $pdo->prepare("SELECT attempts, max_attempts FROM email_queue WHERE job_id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch();
        if ($job && $job['attempts'] < $job['max_attempts']) {
            $pdo->prepare("UPDATE email_queue SET status='waiting', attempts=0, error_message=NULL, next_retry_at=NULL WHERE job_id=?")
                ->execute([$id]);
        }
    }

    if (isset($_POST['pause'])) {
        $pdo->prepare("UPDATE email_queue SET status='paused' WHERE job_id=?")
            ->execute([$_POST['pause']]);
    }

    if (isset($_POST['resume'])) {
        $pdo->prepare("UPDATE email_queue SET status='waiting' WHERE job_id=?")
            ->execute([$_POST['resume']]);
    }

    if (isset($_POST['execute'])) {
        $pdo->prepare("UPDATE email_queue SET status='active', started_at=NOW() WHERE job_id=?")
            ->execute([$_POST['execute']]);
    }

    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM email_queue WHERE job_id=?")
            ->execute([$_POST['delete']]);
    }

    header("Location: bull-dashboard.php?section=postmails&status=$filter");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🐂 Comic Bull Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .status-waiting {  color: #1e40af; }
        .status-active {  color: #92400e; }
        .status-completed { color: #065f46; }
        .status-failed { color: #991b1b; }
        .status-delayed {  color: #374151; }
        .status-paused {  color: #3730a3; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-700 text-white border-r border-gray-200">
            <div class="p-6">
                <div class="flex items-center mb-3 ">
                    <i class="fas fa-bull text-2xl mr-3"></i>
                    <h2 class="text-xl font-semibold "> 🎯 Bull Dashboard</h2>
                </div>
                <div class="border mt-1 mb-3"></div>
                
                <nav class="space-y-2 ">
                    <a href="?section=postmails" class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $section === 'postmails' ? 'bg-gray-500 text-gray-100 border-l-4 border-gray-500' : 'text-gray-200 hover:bg-gray-500' ?>">
                        <i class="fas fa-envelope mr-3"></i>
                        <span>Post Mails</span>
                    </a>
                    
                    <a href="?section=users" class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $section === 'users' ? 'bg-gray-500 text-gray-100 border-l-4 border-gray-500' : 'text-gray-200 hover:bg-gray-500' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span>Subscribers</span>
                    </a>
                    
                    <a href="?section=loggedin" class="flex items-center px-4 py-3 rounded-lg transition-colors <?= $section === 'loggedin' ? 'bg-gray-500 text-gray-100 border-l-4 border-gray-500' : 'text-gray-200 hover:bg-gray-500' ?>">
                        <i class="fas fa-user-check mr-3"></i>
                        <span>Logged In Users</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-semibold text-gray-900 mb-2">
                        <?php 
                        $titles = ['postmails' => 'Email Queue', 'users' => 'Subscribers', 'loggedin' => 'Logged In Users'];
                        echo $titles[$section] ?? 'Dashboard';
                        ?>
                    </h1>
                    <p class="text-gray-600">Manage and monitor your email system</p>
                </div>

                <?php if ($section === 'postmails'): ?>
                    <!-- Status Filter Tabs -->
                    <div class="mb-6">
                        <div class="border-b border-gray-200">
                            <nav class="flex space-x-8">
                                <?php foreach ($allowed_status as $s): ?>
                                    <a href="?section=postmails&status=<?= $s ?>" 
                                       class="py-4 px-1 border-b-2 font-medium text-sm transition-colors <?= $filter === $s ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                        <?= ucfirst($s) ?>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>
                    </div>

                    <?php
                    $query = "SELECT * FROM email_queue ";
                    if ($filter !== 'latest') {
                        $query .= "WHERE status = :status ORDER BY created_at DESC";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(['status' => $filter]);
                    } else {
                        $query .= "ORDER BY created_at DESC LIMIT 25";
                        $stmt = $pdo->query($query);
                    }
                    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    function getStatusClass($status) {
                        return "status-$status";
                    }
                    ?>

                    <!-- Jobs Grid -->
                    <div class="grid gap-6">
                        <?php foreach ($jobs as $job):
                            $comic = json_decode($job['comic_data'] ?? '', true); ?>
                            <div class="card p-6">
                                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                                    <!-- Data Section (40%) -->
                                    <div class="lg:col-span-2">
                                        <h3 class="text-sm font-medium text-gray-500 mb-3 flex items-center">
                                            <i class="fas fa-database mr-2"></i>
                                            Data
                                        </h3>
                                        <div class="space-y-3">
                                            <div class="bg-gray-50 p-3 rounded-lg">
                                                <div class="text-xs text-gray-500 mb-1">Job Info</div>
                                                <div class="font-mono text-sm text-red-700"><?= $job['job_id'] ?></div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    <i class="fas fa-envelope mr-1"></i>
                                                    <?= htmlspecialchars($job['recipient_email']) ?>
                                                </div>
                                            </div>
                                        
                                            <div class="flex items-center ">
                                                <span class="text-sm text-gray-600">Status :</span>
                                                <span class="px-2 rounded-full text-sm font-medium <?= getStatusClass($job['status']) ?>">
                                                    <?= $job['status'] ?>
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-600">Comic :</span>
                                                <div class="px-2 text-right">
                                                    <div class="text-sm font-medium">#<?= $comic['num'] ?? '-' ?></div>
                                                    <?php if ($comic['img'] ?? false): ?>

                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Options Section (40%) -->
                                    <div class="lg:col-span-2">
                                        <h3 class="text-sm font-medium text-gray-500 mb-3 flex items-center">
                                            <i class="fas fa-cog mr-2"></i>
                                            Work Flow
                                        </h3>
                                        <div class="space-y-3">
                                            <div class="bg-gray-50 p-3 rounded-lg">
                                                <div class="text-xs text-gray-500 mb-1">Attempts</div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium"><?= $job['attempts'] ?>/<?= $job['max_attempts'] ?></span>
                                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?= ($job['attempts'] / $job['max_attempts']) * 100 ?>%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="bg-gray-50 p-3 rounded-lg">
                                                <div class="text-xs text-gray-500 mb-2">Timing</div>
                                                <div class="space-y-1 text-xs">
                                                    <?php if ($job['started_at']): ?>
                                                        <div class="flex items-center text-green-600">
                                                            <i class="fas fa-play w-4 mr-1"></i>
                                                            Started: <?= $job['started_at'] ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($job['completed_at']): ?>
                                                        <div class="flex items-center text-blue-600">
                                                            <i class="fas fa-check w-4 mr-1"></i>
                                                            Completed: <?= $job['completed_at'] ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($job['next_retry_at']): ?>
                                                        <div class="flex items-center text-orange-600">
                                                            <i class="fas fa-clock w-4 mr-1"></i>
                                                            Next Retry: <?= $job['next_retry_at'] ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($job['process_time_ms']): ?>
                                                        <div class="flex items-center text-gray-500">
                                                            <i class="fas fa-stopwatch w-4 mr-1"></i>
                                                            Duration: <?= $job['process_time_ms'] ?>ms
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions Section (20%) -->
                                    <div class="lg:col-span-1">
                                        <h3 class="text-sm font-medium text-gray-500 mb-3 flex items-center">
                                            <i class="fas fa-bolt mr-2"></i>
                                            Actions
                                        </h3>
                                        <div class="space-y-2">
                                            <?php if (in_array($job['status'], ['failed', 'delayed']) && $job['attempts'] < $job['max_attempts']): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="retry" value="<?= $job['job_id'] ?>">
                                                    <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                                        <i class="fas fa-redo mr-2"></i>Retry
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($job['status'] === 'paused'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="resume" value="<?= $job['job_id'] ?>">
                                                    <button class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                                        <i class="fas fa-play mr-2"></i>Resume
                                                    </button>
                                                </form>
                                            <?php elseif ($job['status'] !== 'completed'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="pause" value="<?= $job['job_id'] ?>">
                                                    <button class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors mb-2">
                                                        <i class="fas fa-pause mr-2"></i>Pause
                                                    </button>
                                                </form>
                                                <form method="POST">
                                                    <input type="hidden" name="execute" value="<?= $job['job_id'] ?>">
                                                    <button class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                                        <i class="fas fa-bolt mr-2"></i>Execute
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" onsubmit="return confirm('Delete this job?')">
                                                <input type="hidden" name="delete" value="<?= $job['job_id'] ?>">
                                                <button class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                                    <i class="fas fa-trash mr-2"></i>Delete
                                                </button>
                                            </form>
                                        </div>

                                        <?php if ($job['error_message']): ?>
                                            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                                <div class="text-xs text-red-600 font-medium mb-1">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Error
                                                </div>
                                                <div class="text-xs text-red-700"><?= htmlspecialchars($job['error_message']) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($section === 'users'): ?>
                    <?php
                    $stmt = $pdo->query("SELECT id, email, is_verified, is_subscribed, preferred_time, created_at, updated_at FROM comic_subscribers ORDER BY created_at DESC");
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="card overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preferences</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?= $user['id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col space-y-1">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?= $user['is_verified'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                    <i class="<?= $user['is_verified'] ? 'fas fa-check' : 'fas fa-times' ?> mr-1"></i>
                                                    <?= $user['is_verified'] ? 'Verified' : 'Unverified' ?>
                                                </span>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?= $user['is_subscribed'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                                    <i class="<?= $user['is_subscribed'] ? 'fas fa-bell' : 'fas fa-bell-slash' ?> mr-1"></i>
                                                    <?= $user['is_subscribed'] ? 'Subscribed' : 'Unsubscribed' ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <i class="fas fa-clock text-gray-400 mr-2"></i>
                                                <?= $user['preferred_time'] ?? 'Not set' ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div><?= $user['created_at'] ?></div>
                                            <div class="text-xs"><?= $user['updated_at'] ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($section === 'loggedin'): ?>
                    <?php
                    $stmt = $pdo->query("SELECT id, full_name, email, created_at, updated_at FROM users ORDER BY created_at DESC");
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="card overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Info</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                                    <i class="fas fa-user-check text-green-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['full_name']) ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?= $user['id'] ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                                <span class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div>Created: <?= $user['created_at'] ?></div>
                                            <div class="text-xs">Updated: <?= $user['updated_at'] ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>