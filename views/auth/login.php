<?php

$page_title = 'Login';
ob_start();
?>

<div class="bg-white rounded-lg shadow-lg p-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Login</h1>

    <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?action=login_post" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
            <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password:</label>
            <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            Login
        </button>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

