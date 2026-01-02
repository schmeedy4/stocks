<?php

$watchlist = $watchlist_data ?? null;
$is_edit = isset($watchlist) && isset($watchlist->id);
$page_title = $is_edit ? 'Edit Watchlist' : 'New Watchlist';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900"><?= $is_edit ? 'Edit Watchlist' : 'New Watchlist' ?></h1>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
        <strong class="block mb-2">Please fix the following errors:</strong>
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $field => $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= $is_edit ? '?action=watchlist_update&id=' . htmlspecialchars((string)$watchlist->id) : '?action=watchlist_create' ?>" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span>:</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                value="<?= htmlspecialchars($watchlist->name ?? '') ?>" 
                required 
                maxlength="80"
                class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
            <?php if (isset($errors['name'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($is_edit && isset($watchlist->is_default) && $watchlist->is_default): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4">
                <p class="text-sm">This is your default watchlist. It cannot be deleted.</p>
            </div>
        <?php endif; ?>

        <div class="flex gap-4">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <?= $is_edit ? 'Update' : 'Create' ?>
            </button>
            <a href="?action=watchlist" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

