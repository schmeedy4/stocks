<?php

$page_title = 'Dashboard';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard</h1>
    <p class="text-gray-600">Welcome, you are logged in.</p>
</div>

<form method="GET" action="?action=dashboard" class="mb-6 flex items-center gap-3">
    <label for="year" class="text-sm font-medium text-gray-700">Year:</label>
    <select id="year" name="year" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
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
    <input type="hidden" name="action" value="dashboard">
</form>

<?php if (empty($sell_trades) && (float)$dividend_gross_amount_eur == 0): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No SELL trades or dividends found for <?= htmlspecialchars((string)$selected_year) ?>.</p>
    </div>
<?php else: ?>
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Year Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sells Table -->
            <?php if (!empty($sell_trades)): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <h3 class="px-6 py-3 bg-gray-50 text-lg font-semibold text-gray-900 border-b border-gray-200">Sells</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900 w-64">Total Proceeds (EUR):</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-right"><?= htmlspecialchars($sum_proceeds_eur) ?></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">Tax before losses (EUR):</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-right"><?= htmlspecialchars($tax_before_losses_eur) ?></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">Total Losses Offset (EUR):</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-right"><?= htmlspecialchars($total_losses_offset_eur) ?></td>
                        </tr>
                        <tr class="border-t-2 border-gray-300">
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">Final Tax (EUR):</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 text-right font-bold"><?= htmlspecialchars($final_tax_eur) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Dividends Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <h3 class="px-6 py-3 bg-gray-50 text-lg font-semibold text-gray-900 border-b border-gray-200">Dividends</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900 w-64">Gross Amount EUR:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-right"><?= htmlspecialchars(number_format((float)$dividend_gross_amount_eur, 2, '.', '')) ?></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">Foreign Tax EUR:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-right"><?= htmlspecialchars(number_format((float)$dividend_foreign_tax_eur, 2, '.', '')) ?></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">Slovenian Tax (10%):</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-700 text-right"><?= htmlspecialchars(number_format((float)$dividend_slovenian_tax_eur, 2, '.', '')) ?></td>
                        </tr>
                        <tr class="border-t-2 border-gray-300">
                            <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900">Profit:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-900 text-right font-bold"><?= htmlspecialchars(number_format((float)$dividend_profit_eur, 2, '.', '')) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">SELL Trades for <?= htmlspecialchars((string)$selected_year) ?></h2>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrument</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Proceeds EUR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Basis EUR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Realized P/L EUR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Norm. Stroški EUR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Base EUR</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tax Rate</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax EUR</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sell_trades as $index => $trade): ?>
                            <?php 
                            $instrument = $instruments[$trade->instrument_id] ?? null;
                            $tax_totals = $sell_tax_totals[$trade->id] ?? null;
                            ?>
                            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d.m.Y', strtotime($trade->trade_date)) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($instrument): ?>
                                        <?= htmlspecialchars($instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name) ?>
                                    <?php else: ?>
                                        ID: <?= htmlspecialchars((string)$trade->instrument_id) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= number_format((float)$trade->quantity, 0) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($tax_totals['total_sell_proceeds_eur'] ?? '0.00') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($tax_totals['total_buy_cost_eur'] ?? '0.00') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($tax_totals['total_gain_eur'] ?? '0.00') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($tax_totals['total_normirani_stroski_eur'] ?? '0.00') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($tax_totals['total_tax_base_eur'] ?? '0.00') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php
                                    if ($tax_totals !== null && isset($tax_totals['tax_rate_percent'])) {
                                        if ($tax_totals['tax_rate_percent'] === 'mixed') {
                                            echo htmlspecialchars('mixed (' . $tax_totals['tax_rate_min'] . '–' . $tax_totals['tax_rate_max'] . '%)');
                                        } else {
                                            echo htmlspecialchars($tax_totals['tax_rate_percent'] ?? '');
                                            if ($tax_totals['tax_rate_percent'] !== null) {
                                                echo '%';
                                            }
                                        }
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($tax_totals['total_tax_eur'] ?? '0.00') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>

