<?php

$page_title = 'Dividend Payers';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">Dividend Payers</h1>
    <a href="?action=payer_new" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
        Add Payer
    </a>
</div>

<?php if (empty($payers)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No payers found.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payers as $index => $payer): ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($payer->payer_name) ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($payer->payer_address) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($payer->payer_country_code) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $payer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $payer->is_active ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=payer_edit&id=<?= $payer->id ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

