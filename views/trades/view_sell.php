<?php

$page_title = 'SELL Trade Details';
ob_start();
?>

<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-gray-900">SELL Trade Details</h1>
        <a href="?action=trades" class="text-blue-600 hover:text-blue-900 flex items-center">
            <span class="mr-1">←</span> Back to Trades
        </a>
    </div>
</div>

<div class="mb-8">
    <h2 class="text-2xl font-semibold text-gray-900 mb-4">Trade Summary</h2>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900 w-48">Date</th>
                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->trade_date) ?></td>
                </tr>
                <tr class="bg-gray-50">
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Instrument</th>
                    <td class="px-6 py-4 text-sm text-gray-700">
                        <?php if ($instrument): ?>
                            <?= htmlspecialchars($instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name) ?>
                        <?php else: ?>
                            ID: <?= htmlspecialchars((string)$trade->instrument_id) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Quantity</th>
                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->quantity) ?></td>
                </tr>
                <tr class="bg-gray-50">
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Price per Unit</th>
                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->price_per_unit) ?> <?= htmlspecialchars($trade->trade_currency) ?></td>
                </tr>
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Price EUR</th>
                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->price_eur) ?> EUR</td>
                </tr>
                <tr class="bg-gray-50">
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Total Value EUR</th>
                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->total_value_eur) ?> EUR</td>
                </tr>
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Fee EUR</th>
                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->fee_eur) ?> EUR</td>
                </tr>
                <?php if ($trade->notes): ?>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-900">Notes</th>
                        <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($trade->notes) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div>
    <h2 class="text-2xl font-semibold text-gray-900 mb-4">FIFO Allocations</h2>
    <?php if (empty($allocations)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">No allocations found.</p>
        </div>
    <?php else: ?>
        <?php
        function compare_decimals_view(string $a, string $b): int {
            return bccomp($a, $b, 2);
        }
        $total_proceeds = '0.00';
        $total_cost = '0.00';
        $total_pnl = '0.00';
        foreach ($allocations as $alloc_data):
            $alloc = $alloc_data['allocation'];
            $total_proceeds = bcadd($total_proceeds, $alloc->proceeds_eur, 2);
            $total_cost = bcadd($total_cost, $alloc->cost_basis_eur, 2);
            $total_pnl = bcadd($total_pnl, $alloc->realized_pnl_eur, 2);
        endforeach;
        ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot Opened Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Consumed</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Proceeds EUR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Basis EUR</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Realized P/L EUR</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($allocations as $index => $alloc_data): ?>
                            <?php
                            $alloc = $alloc_data['allocation'];
                            $lot_opened_date = $alloc_data['lot_opened_date'];
                            ?>
                            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($lot_opened_date) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($alloc->quantity_consumed) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($alloc->proceeds_eur) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($alloc->cost_basis_eur) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium <?= compare_decimals_view($alloc->realized_pnl_eur, '0') >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= htmlspecialchars($alloc->realized_pnl_eur) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-6 py-4 text-sm text-gray-900" colspan="2">Total</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($total_proceeds) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($total_cost) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold <?= compare_decimals_view($total_pnl, '0') >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= htmlspecialchars($total_pnl) ?>
                            </td>
                        </tr>
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

