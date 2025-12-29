<?php

$page_title = 'Holdings';
ob_start();
?>

<div class="max-w-12xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Holdings</h1>
        <div>
            <span class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars(number_format((float)$total_portfolio_value_usd, 2, '.', ',')) ?></span>
            <span class="ml-3 text-sm <?= (float)$total_todays_gain_usd >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                <?= (float)$total_todays_gain_usd >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format((float)$total_todays_gain_usd, 2, '.', ',')) ?>
                (<?= (float)$total_todays_gain_percent >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format((float)$total_todays_gain_percent, 2, '.', '')) ?>%)
            </span>
        </div>
    </div>

    <?php if (empty($holdings)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">No open positions found.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">Symbol</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Change (%)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Shares</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (%)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cost (USD)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Today's Gain (USD)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Today's Gain (%)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Change (USD)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Change (%)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Value (USD)</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sell 100% Tax (USD)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No tax date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($holdings as $index => $holding): ?>
                        <?php 
                        $instrument = $holding['instrument'];
                        $symbol = $instrument->ticker ?: $instrument->name;
                        ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 <?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> z-10"><?= htmlspecialchars($symbol) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars(number_format((float)$holding['close_price_usd'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?= (float)$holding['change_usd'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars(number_format((float)$holding['change_usd'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?= (float)$holding['change_percent'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars(number_format((float)$holding['change_percent'], 2, '.', '')) ?>%</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars(number_format((float)$holding['shares'], 0, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars(number_format((float)$holding['weight_percent'], 2, '.', '')) ?>%</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars(number_format((float)$holding['avg_cost'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars(number_format((float)$holding['cost_basis_usd'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?= (float)$holding['todays_gain_usd'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars(number_format((float)$holding['todays_gain_usd'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?= (float)$holding['todays_gain_percent'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars(number_format((float)$holding['todays_gain_percent'], 2, '.', '')) ?>%</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?= (float)$holding['total_change_usd'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars(number_format((float)$holding['total_change_usd'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right <?= (float)$holding['total_change_percent'] >= 0 ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars(number_format((float)$holding['total_change_percent'], 2, '.', '')) ?>%</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 text-right"><?= htmlspecialchars(number_format((float)$holding['value_usd'], 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars(number_format((float)($holding['sell_100_tax_usd'] ?? $holding['sell_100_tax_eur']), 2, '.', '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= $holding['no_tax_date'] !== null ? date('d.m.Y', strtotime($holding['no_tax_date'])) : '-' ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600"><?= $holding['price_date'] !== null ? date('d.m.Y', strtotime($holding['price_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

