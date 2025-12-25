<?php
/** @var int $year */
/** @var array $dividends */
/** @var array $payers */
/** @var array<int, array{type:string,message:string}> $flashes */
?>
<?php ob_start(); ?>
<h1>Dividend Management</h1>
<p><a href="?action=logout">Logout</a></p>
<form method="get">
    <input type="hidden" name="action" value="dividends">
    <label>
        Tax year
        <input type="number" name="year" value="<?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>" min="1900" max="2100">
    </label>
    <button type="submit">Refresh</button>
</form>

<form method="post" action="?action=add_dividend">
    <h2>Add Dividend</h2>
    <input type="hidden" name="year" value="<?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>">
    <label>
        Payer
        <select name="dividend_payer_id" required>
            <option value="">Choose payer</option>
            <?php foreach ($payers as $payer): ?>
                <option value="<?php echo (int) $payer->id; ?>">
                    <?php echo htmlspecialchars($payer->payerName, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($payer->payerCountryCode, ENT_QUOTES, 'UTF-8'); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Received date
        <input type="date" name="received_date" required>
    </label>
    <label>
        Dividend type code
        <input type="text" name="dividend_type_code" required>
    </label>
    <label>
        Source country code
        <input type="text" name="source_country_code" required>
    </label>
    <label>
        Gross amount (EUR)
        <input type="number" step="0.01" name="gross_amount_eur" required>
    </label>
    <label>
        Foreign tax (EUR)
        <input type="number" step="0.01" name="foreign_tax_eur">
    </label>
    <label>
        Payer identifier for export (optional)
        <input type="text" name="payer_ident_for_export">
    </label>
    <label>
        Notes
        <input type="text" name="notes">
    </label>
    <button type="submit">Save dividend</button>
</form>

<h2>Dividends for <?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?></h2>
<?php if (empty($dividends)): ?>
    <p>No dividends recorded for this year.</p>
<?php else: ?>
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
        <tr>
            <th>Date</th>
            <th>Payer</th>
            <th>Type</th>
            <th>Country</th>
            <th>Gross EUR</th>
            <th>Foreign tax EUR</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($dividends as $dividend): ?>
            <tr>
                <td><?php echo htmlspecialchars($dividend->receivedDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($payers[$dividend->dividendPayerId]->payerName ?? ('Payer #' . $dividend->dividendPayerId), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($dividend->dividendTypeCode, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($dividend->sourceCountryCode, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo number_format($dividend->grossAmountEur, 2); ?></td>
                <td><?php echo $dividend->foreignTaxEur === null ? '' : number_format($dividend->foreignTaxEur, 2); ?></td>
                <td><?php echo htmlspecialchars((string) $dividend->notes, ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<form method="get" action="">
    <h2>Export DOH-DIV</h2>
    <input type="hidden" name="action" value="export_doh_div">
    <label>
        Tax year
        <input type="number" name="year" value="<?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>" min="1900" max="2100" required>
    </label>
    <button type="submit">Download CSV</button>
</form>
<?php
$content = (string) ob_get_clean();
$title = 'Dividends';
require __DIR__ . '/layout.php';
