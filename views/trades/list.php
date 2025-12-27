<?php

$page_title = 'Trades';
ob_start();
?>

<h1>Trades</h1>

<p>
    <a href="?action=trade_new_buy">Add BUY</a> |
    <a href="?action=trade_new_sell">Add SELL</a>
</p>

<form method="GET" action="?action=trades" style="margin-bottom: 20px;">
    <label for="year">Year:</label>
    <select id="year" name="year" style="padding: 5px;">
        <?php
        $current_year = (int) date('Y');
        $start_year = $min_year ?? $current_year;
        for ($year = $current_year; $year >= $start_year; $year--):
        ?>
            <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                <?= $year ?>
            </option>
        <?php endfor; ?>
    </select>
    <button type="submit" style="padding: 5px 15px; margin-left: 10px;">Filter</button>
    <input type="hidden" name="action" value="trades">
</form>

<?php if (empty($trades)): ?>
    <p>No trades found for <?= htmlspecialchars((string)$selected_year) ?>.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Instrument</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Currency</th>
                <th>Price EUR</th>
                <th>Total EUR</th>
                <th>Fee EUR</th>
                <?php 
                // Show tax columns if there are any SELL trades in the list
                $has_sell_trades = false;
                foreach ($trades as $t) {
                    if ($t->trade_type === 'SELL') {
                        $has_sell_trades = true;
                        break;
                    }
                }
                if ($has_sell_trades):
                ?>
                    <th>Norm. Stroški EUR</th>
                    <th>Tax Rate %</th>
                    <th>Tax EUR</th>
                <?php endif; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trades as $trade): ?>
                <?php 
                $instrument = $instruments[$trade->instrument_id] ?? null;
                $tax_totals = $sell_tax_totals[$trade->id] ?? null;
                ?>
                <tr>
                    <td><?= htmlspecialchars($trade->trade_date) ?></td>
                    <td><?= htmlspecialchars($trade->trade_type) ?></td>
                    <td>
                        <?php if ($instrument): ?>
                            <?= htmlspecialchars($instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name) ?>
                        <?php else: ?>
                            ID: <?= htmlspecialchars((string)$trade->instrument_id) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($trade->quantity) ?></td>
                    <td><?= htmlspecialchars($trade->price_per_unit) ?></td>
                    <td><?= htmlspecialchars($trade->trade_currency) ?></td>
                    <td><?= htmlspecialchars($trade->price_eur) ?></td>
                    <td><?= htmlspecialchars($trade->total_value_eur) ?></td>
                    <td><?= htmlspecialchars($trade->fee_eur) ?></td>
                    <?php if ($has_sell_trades): ?>
                        <?php if ($trade->trade_type === 'SELL' && $tax_totals !== null): ?>
                            <td><?= htmlspecialchars($tax_totals['total_normirani_stroski_eur'] ?? '') ?></td>
                            <td>
                                <?php
                                if ($tax_totals['tax_rate_percent'] === 'mixed') {
                                    echo htmlspecialchars($tax_totals['tax_rate_min'] . '-' . $tax_totals['tax_rate_max'] . '%');
                                } else {
                                    echo htmlspecialchars($tax_totals['tax_rate_percent'] ?? '');
                                    if ($tax_totals['tax_rate_percent'] !== null) {
                                        echo '%';
                                    }
                                }
                                ?>
                            </td>
                            <td><?= htmlspecialchars($tax_totals['total_tax_eur'] ?? '') ?></td>
                        <?php else: ?>
                            <td></td>
                            <td></td>
                            <td></td>
                        <?php endif; ?>
                    <?php endif; ?>
                    <td>
                        <a href="?action=trade_edit&id=<?= $trade->id ?>">Edit</a>
                        <?php if ($trade->trade_type === 'SELL'): ?>
                            | <a href="?action=trade_view_sell&id=<?= $trade->id ?>">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

