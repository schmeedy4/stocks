<?php

$page_title = 'Dashboard';
ob_start();
?>

<h1>Dashboard</h1>
<p>Welcome, you are logged in.</p>

<form method="GET" action="?action=dashboard" style="margin-bottom: 20px;">
    <label for="year">Year:</label>
    <select id="year" name="year" style="padding: 5px;">
        <?php
        $current_year = (int) date('Y');
        for ($year = $current_year; $year >= $min_year; $year--):
        ?>
            <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                <?= $year ?>
            </option>
        <?php endfor; ?>
    </select>
    <button type="submit" style="padding: 5px 15px; margin-left: 10px;">Filter</button>
    <input type="hidden" name="action" value="dashboard">
</form>

<?php if (empty($sell_trades)): ?>
    <p>No SELL trades found for <?= htmlspecialchars((string)$selected_year) ?>.</p>
<?php else: ?>
    <h2>Year Summary</h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <tbody>
            <tr>
                <td style="font-weight: bold; width: 250px;">Total Proceeds (EUR):</td>
                <td><?= htmlspecialchars($sum_proceeds_eur) ?></td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Tax before losses (EUR):</td>
                <td><?= htmlspecialchars($tax_before_losses_eur) ?></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Total Losses Offset (EUR):</td>
                <td><?= htmlspecialchars($total_losses_offset_eur) ?></td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td style="font-weight: bold;">Final Tax (EUR):</td>
                <td><?= htmlspecialchars($final_tax_eur) ?></td>
            </tr>
        </tbody>
    </table>

    <h2>SELL Trades for <?= htmlspecialchars((string)$selected_year) ?></h2>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Instrument</th>
                <th>Quantity</th>
                <th>Proceeds EUR</th>
                <th>Cost Basis EUR</th>
                <th>Realized P/L EUR</th>
                <th>Norm. Stroški EUR</th>
                <th>Tax Base EUR</th>
                <th>Tax Rate</th>
                <th>Tax EUR</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sell_trades as $trade): ?>
                <?php 
                $instrument = $instruments[$trade->instrument_id] ?? null;
                $tax_totals = $sell_tax_totals[$trade->id] ?? null;
                ?>
                <tr>
                    <td><?= htmlspecialchars($trade->trade_date) ?></td>
                    <td>
                        <?php if ($instrument): ?>
                            <?= htmlspecialchars($instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name) ?>
                        <?php else: ?>
                            ID: <?= htmlspecialchars((string)$trade->instrument_id) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trade->quantity) ?></td>
                    <td><?= htmlspecialchars($tax_totals['total_sell_proceeds_eur'] ?? '0.00') ?></td>
                    <td><?= htmlspecialchars($tax_totals['total_buy_cost_eur'] ?? '0.00') ?></td>
                    <td><?= htmlspecialchars($tax_totals['total_gain_eur'] ?? '0.00') ?></td>
                    <td><?= htmlspecialchars($tax_totals['total_normirani_stroski_eur'] ?? '0.00') ?></td>
                    <td><?= htmlspecialchars($tax_totals['total_tax_base_eur'] ?? '0.00') ?></td>
                    <td>
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
                    <td><?= htmlspecialchars($tax_totals['total_tax_eur'] ?? '0.00') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>

