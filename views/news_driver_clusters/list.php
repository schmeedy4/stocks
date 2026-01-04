<?php

$page_title = 'News Driver Clusters';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">News Driver Clusters</h1>
    <div class="flex gap-3">
        <button type="button" id="copy-cluster-keys-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
            Copy Cluster Keys (JSON)
        </button>
        <form method="POST" action="?action=news_driver_clusters" class="inline">
            <input type="hidden" name="sync" value="1">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Sync Clusters
            </button>
        </form>
    </div>
</div>

<?php if ($sync_result !== null && $sync_result['success']): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
        <p class="text-sm">
            <strong>Sync completed:</strong> <?= htmlspecialchars((string)$sync_result['inserted_count']) ?> new cluster<?= $sync_result['inserted_count'] === 1 ? '' : 's' ?> added.
        </p>
    </div>
<?php endif; ?>

<?php if (empty($clusters)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No clusters found. Click "Sync Clusters" to extract cluster_keys from news articles.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cluster Key</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Usage Count</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($clusters as $index => $cluster): ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900"><?= htmlspecialchars($cluster['cluster_key']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($cluster['title']) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($cluster['description'] ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= number_format($cluster['usage_count']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                <?php if ($cluster['is_active']): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($cluster['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-200">
            <div class="text-sm text-gray-700">
                Showing <?= count($clusters) ?> <?= count($clusters) === 1 ? 'cluster' : 'clusters' ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copy-cluster-keys-btn');
    if (!copyBtn) return;

    // Extract cluster_keys from PHP data
    const clusterKeys = <?= json_encode(isset($clusters) && !empty($clusters) ? array_column($clusters, 'cluster_key') : []) ?>;

    copyBtn.addEventListener('click', async function() {
        if (clusterKeys.length === 0) {
            alert('No cluster keys to copy.');
            return;
        }

        try {
            // Format as JSON array
            const jsonArray = JSON.stringify(clusterKeys, null, 2);
            
            // Copy to clipboard
            await navigator.clipboard.writeText(jsonArray);
            
            // Show success feedback
            const originalText = copyBtn.textContent;
            copyBtn.textContent = 'Copied!';
            copyBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            copyBtn.classList.add('bg-green-500');
            
            setTimeout(function() {
                copyBtn.textContent = originalText;
                copyBtn.classList.remove('bg-green-500');
                copyBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard. Please try again.');
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

