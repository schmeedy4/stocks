<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Portfolio Tracker') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        accent: {
                            blue: '#3b82f6',
                            green: '#10b981',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php
    require_once __DIR__ . '/../infrastructure/auth.php';
    $is_logged_in = current_user_id() !== null;
    $current_action = $_GET['action'] ?? 'login';
    
    // Helper function to check if a link is active
    function is_active_link(string $action, string $current): bool {
        return $action === $current;
    }
    
    // Helper function to get link classes
    function get_link_classes(string $action, string $current): string {
        $base = 'block px-4 py-2 text-sm font-medium rounded-md transition-colors';
        if (is_active_link($action, $current)) {
            return $base . ' bg-blue-50 text-blue-700 border-l-4 border-blue-600';
        }
        return $base . ' text-gray-700 hover:bg-gray-100 hover:text-gray-900';
    }
    ?>
    
    <?php if ($is_logged_in): ?>
        <div class="flex min-h-screen">
            <!-- Left Sidebar -->
            <aside class="w-60 bg-gray-50 border-r border-gray-200 flex-shrink-0">
                <div class="p-4">
                    <h1 class="text-xl font-bold text-gray-900 mb-6">Portfolio Tracker</h1>
                    <nav class="space-y-1">
                        <a href="?action=dashboard" class="<?= get_link_classes('dashboard', $current_action) ?>">
                            Dashboard
                        </a>
                        <a href="?action=holdings" class="<?= get_link_classes('holdings', $current_action) ?>">
                            Holdings
                        </a>
                        <a href="?action=trades" class="<?= get_link_classes('trades', $current_action) ?>">
                            Trades
                        </a>
                        <a href="?action=prices" class="<?= get_link_classes('prices', $current_action) ?>">
                            Prices
                        </a>
                        <a href="?action=instruments" class="<?= get_link_classes('instruments', $current_action) ?>">
                            Instruments
                        </a>
                        <a href="?action=payers" class="<?= get_link_classes('payers', $current_action) ?>">
                            Payers
                        </a>
                        <a href="?action=dividends" class="<?= get_link_classes('dividends', $current_action) ?>">
                            Dividends
                        </a>
                        <a href="?action=corporate_actions" class="<?= get_link_classes('corporate_actions', $current_action) ?>">
                            Corporate Actions
                        </a>
                        <a href="?action=news" class="<?= get_link_classes('news', $current_action) ?>">
                            News
                        </a>
                        <div class="pt-4 mt-4 border-t border-gray-200">
                            <a href="?action=logout" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 rounded-md transition-colors">
                                Logout
                            </a>
                        </div>
                    </nav>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden">
                <div class="<?= $current_action === 'holdings' ? '' : (in_array($current_action, ['news', 'trades', 'dividends']) ? 'max-w-12xl mx-auto' : 'max-w-7xl mx-auto') ?> px-6 py-8">
                    <?php if (isset($content)): ?>
                        <?= $content ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    <?php else: ?>
        <!-- Login page - no sidebar -->
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="max-w-md w-full">
                <?php if (isset($content)): ?>
                    <?= $content ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>

