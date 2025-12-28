<?php

$page_title = 'Holdings';
ob_start();
?>

<h1>Holdings</h1>

<?php if (empty($holdings)): ?>
    <p>No open positions found.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
        <thead>
            <tr>
                <th>Symbol</th>
                <th>Price</th>
                <th>Change</th>
                <th>Change (%)</th>
                <th>Shares</th>
                <th>Weight (%)</th>
                <th>Avg Cost</th>
                <th>Cost (USD)</th>
                <th>Today's Gain (USD)</th>
                <th>Today's Gain (%)</th>
                <th>Total Change (USD)</th>
                <th>Total Change (%)</th>
                <th>Value (USD)</th>
                <th>Sell 100% Tax</th>
                <th>No tax date</th>
                <th>Price date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($holdings as $holding): ?>
                <?php 
                $instrument = $holding['instrument'];
                $symbol = $instrument->ticker ?: $instrument->name;
                ?>
                <tr>
                    <td><?= htmlspecialchars($symbol) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['close_price_usd'], 2, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['change_usd'], 2, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['change_percent'], 2, '.', '')) ?>%</td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['shares'], 0, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['weight_percent'], 2, '.', '')) ?>%</td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['avg_cost'], 2, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['cost_basis_usd'], 2, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['todays_gain_usd'], 2, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['todays_gain_percent'], 2, '.', '')) ?>%</td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['total_change_usd'], 2, '.', '')) ?></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['total_change_percent'], 2, '.', '')) ?>%</td>
                    <td style="text-align: right;"><strong><?= htmlspecialchars(number_format((float)$holding['value_usd'], 2, '.', '')) ?></strong></td>
                    <td style="text-align: right;"><?= htmlspecialchars(number_format((float)$holding['sell_100_tax_eur'], 2, '.', '')) ?></td>
                    <td><?= $holding['no_tax_date'] !== null ? htmlspecialchars($holding['no_tax_date']) : '-' ?></td>
                    <td><?= $holding['price_date'] !== null ? htmlspecialchars($holding['price_date']) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

