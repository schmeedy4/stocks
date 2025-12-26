<?php

$page_title = 'Trades';
ob_start();
?>

<h1>Trades</h1>

<p>
    <a href="?action=trade_new_buy">Add BUY</a> |
    <a href="?action=trade_new_sell">Add SELL</a>
</p>

<?php if (empty($trades)): ?>
    <p>No trades found.</p>
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
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trades as $trade): ?>
                <?php $instrument = $instruments[$trade->instrument_id] ?? null; ?>
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

