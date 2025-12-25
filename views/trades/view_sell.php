<?php

$page_title = 'SELL Trade Details';
ob_start();
?>

<h1>SELL Trade Details</h1>

<p><a href="?action=trades">← Back to Trades</a></p>

<h2>Trade Summary</h2>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr>
        <th style="text-align: left; width: 200px;">Date</th>
        <td><?= htmlspecialchars($trade->trade_date) ?></td>
    </tr>
    <tr>
        <th style="text-align: left;">Instrument</th>
        <td>
            <?php if ($instrument): ?>
                <?= htmlspecialchars($instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name) ?>
            <?php else: ?>
                ID: <?= htmlspecialchars((string)$trade->instrument_id) ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th style="text-align: left;">Quantity</th>
        <td><?= htmlspecialchars($trade->quantity) ?></td>
    </tr>
    <tr>
        <th style="text-align: left;">Price per Unit</th>
        <td><?= htmlspecialchars($trade->price_per_unit) ?> <?= htmlspecialchars($trade->trade_currency) ?></td>
    </tr>
    <tr>
        <th style="text-align: left;">Total Value EUR</th>
        <td><?= htmlspecialchars($trade->total_value_eur) ?> EUR</td>
    </tr>
    <tr>
        <th style="text-align: left;">Fee EUR</th>
        <td><?= htmlspecialchars($trade->fee_eur) ?> EUR</td>
    </tr>
    <?php if ($trade->notes): ?>
        <tr>
            <th style="text-align: left;">Notes</th>
            <td><?= htmlspecialchars($trade->notes) ?></td>
        </tr>
    <?php endif; ?>
</table>

<h2>FIFO Allocations</h2>
<?php if (empty($allocations)): ?>
    <p>No allocations found.</p>
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
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Lot Opened Date</th>
                <th>Qty Consumed</th>
                <th>Proceeds EUR</th>
                <th>Cost Basis EUR</th>
                <th>Realized P/L EUR</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allocations as $alloc_data): ?>
                <?php
                $alloc = $alloc_data['allocation'];
                $lot_opened_date = $alloc_data['lot_opened_date'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($lot_opened_date) ?></td>
                    <td><?= htmlspecialchars($alloc->quantity_consumed) ?></td>
                    <td><?= htmlspecialchars($alloc->proceeds_eur) ?></td>
                    <td><?= htmlspecialchars($alloc->cost_basis_eur) ?></td>
                    <td style="<?= compare_decimals_view($alloc->realized_pnl_eur, '0') >= 0 ? 'color: green;' : 'color: red;' ?>">
                        <?= htmlspecialchars($alloc->realized_pnl_eur) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td colspan="2">Total</td>
                <td><?= htmlspecialchars($total_proceeds) ?></td>
                <td><?= htmlspecialchars($total_cost) ?></td>
                <td style="<?= compare_decimals_view($total_pnl, '0') >= 0 ? 'color: green;' : 'color: red;' ?>">
                    <?= htmlspecialchars($total_pnl) ?>
                </td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

