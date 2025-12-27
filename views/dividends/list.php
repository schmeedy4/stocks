<?php

$page_title = 'Dividends';
ob_start();
?>

<h1>Dividends</h1>

<p><a href="?action=dividend_new">Add Dividend</a></p>

<form method="GET" action="?action=dividends" style="margin-bottom: 20px;">
    <label for="year">Year:</label>
    <select id="year" name="year">
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit">Filter</button>
</form>

<?php if (empty($dividends)): ?>
    <p>No dividends found for year <?= $year ?>.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Instrument</th>
                <th>Payer</th>
                <th>Gross EUR</th>
                <th>Foreign Tax EUR</th>
                <th>Source Country</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dividends as $dividend): ?>
                <tr>
                    <td><?= htmlspecialchars($dividend->received_date) ?></td>
                    <td>
                        <?php if ($dividend->instrument_id !== null && isset($instruments[$dividend->instrument_id])): ?>
                            <?= htmlspecialchars($instruments[$dividend->instrument_id]->ticker ?? $instruments[$dividend->instrument_id]->name) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (isset($payers[$dividend->dividend_payer_id])): ?>
                            <?= htmlspecialchars($payers[$dividend->dividend_payer_id]->payer_name) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($dividend->gross_amount_eur) ?></td>
                    <td><?= $dividend->foreign_tax_eur !== null ? htmlspecialchars($dividend->foreign_tax_eur) : '-' ?></td>
                    <td><?= htmlspecialchars($dividend->source_country_code) ?></td>
                    <td><?= htmlspecialchars($dividend->dividend_type_code) ?></td>
                    <td><?= $dividend->is_voided ? 'Voided' : 'Active' ?></td>
                    <td>
                        <a href="?action=dividend_edit&id=<?= $dividend->id ?>">Edit</a> |
                        <form method="POST" action="?action=dividend_void&id=<?= $dividend->id ?>" style="display: inline;">
                            <button type="submit" style="background: none; border: none; color: blue; text-decoration: underline; cursor: pointer;">
                                <?= $dividend->is_voided ? 'Unvoid' : 'Void' ?>
                            </button>
                        </form>
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
