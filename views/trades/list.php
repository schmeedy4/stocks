<?php

$page_title = 'Trades';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">Trades</h1>
    <div class="flex gap-3">
        <a href="?action=trade_new_buy" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
            Add BUY
        </a>
        <a href="?action=trade_new_sell" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
            Add SELL
        </a>
    </div>
</div>

<form method="GET" action="?action=trades" class="mb-6 flex items-center gap-3">
    <label for="year" class="text-sm font-medium text-gray-700">Year:</label>
    <select id="year" name="year" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        <option value="">All Years</option>
        <?php
        $current_year = (int) date('Y');
        for ($year = $current_year; $year >= $min_year; $year--):
        ?>
            <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                <?= $year ?>
            </option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Filter</button>
    <input type="hidden" name="action" value="trades">
</form>

<?php if (empty($trades)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No trades found.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrument</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price EUR</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total EUR</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Fee EUR</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Documents</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($trades as $index => $trade): ?>
                        <?php 
                        $instrument = $instruments[$trade->instrument_id] ?? null;
                        ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d.m.Y', strtotime($trade->trade_date)) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $trade->trade_type === 'BUY' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars($trade->trade_type) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if ($instrument): ?>
                                    <?= htmlspecialchars($instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name) ?>
                                <?php else: ?>
                                    ID: <?= htmlspecialchars((string)$trade->instrument_id) ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= number_format((float)$trade->quantity, 0) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= number_format((float)$trade->price_per_unit, 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($trade->trade_currency) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= number_format((float)$trade->price_eur, 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($trade->total_value_eur) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($trade->fee_eur) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php 
                                $doc_count = $document_counts[$trade->id] ?? 0;
                                if ($doc_count > 0): 
                                ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?= $doc_count ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=trade_edit&id=<?= $trade->id ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <?php if ($trade->trade_type === 'SELL'): ?>
                                    <span class="text-gray-300 mx-1">|</span>
                                    <a href="?action=trade_view_sell&id=<?= $trade->id ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-200">
            <div class="text-sm text-gray-700">
                Showing <?= count($trades) ?> <?= count($trades) === 1 ? 'trade' : 'trades' ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

