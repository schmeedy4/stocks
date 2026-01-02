<?php

$page_title = 'Instruments';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">Instruments</h1>
    <a href="?action=instrument_new" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
        Add Instrument
    </a>
</div>

<?php if (empty($instruments)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No instruments found.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISIN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticker</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">30d sentiment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">90d sentiment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($instruments as $index => $instrument): ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->isin ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->ticker ?? '') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($instrument->name) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->instrument_type) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->country_code ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->trading_currency ?? '') ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php 
                                $counts_30d = $sentiment_counts_30d[$instrument->id] ?? ['bullish' => 0, 'bearish' => 0, 'neutral' => 0, 'mixed' => 0];
                                ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php if ($counts_30d['bullish'] > 0): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                            <?= $counts_30d['bullish'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($counts_30d['mixed'] > 0): ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">
                                            <?= $counts_30d['mixed'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($counts_30d['neutral'] > 0): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                                            <?= $counts_30d['neutral'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($counts_30d['bearish'] > 0): ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                            <?= $counts_30d['bearish'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (array_sum($counts_30d) === 0): ?>
                                        <span class="text-gray-400 text-xs">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php 
                                $counts_90d = $sentiment_counts_90d[$instrument->id] ?? ['bullish' => 0, 'bearish' => 0, 'neutral' => 0, 'mixed' => 0];
                                ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php if ($counts_90d['bullish'] > 0): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                            <?= $counts_90d['bullish'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($counts_90d['mixed'] > 0): ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">
                                            <?= $counts_90d['mixed'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($counts_90d['neutral'] > 0): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                                            <?= $counts_90d['neutral'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($counts_90d['bearish'] > 0): ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                            <?= $counts_90d['bearish'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (array_sum($counts_90d) === 0): ?>
                                        <span class="text-gray-400 text-xs">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=instrument_edit&id=<?= $instrument->id ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
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
