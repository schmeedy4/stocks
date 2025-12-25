<?php
/** @var int $year */
/** @var array $dividends */
/** @var array $payers */
/** @var array $flashes */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dividends</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { margin-bottom: 24px; padding: 12px; border: 1px solid #ccc; }
        label { display: block; margin-top: 8px; }
        input, select { padding: 6px; width: 260px; max-width: 100%; }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .flash { padding: 10px; margin-bottom: 12px; }
        .flash-success { background: #e1f3e0; border: 1px solid #7db973; }
        .flash-error { background: #fbe4e6; border: 1px solid #d76b6b; }
    </style>
</head>
<body>
<h1>Dividend Management</h1>

<?php foreach ($flashes as $flash): ?>
    <div class="flash flash-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endforeach; ?>

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
    <table>
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
</body>
</html>
