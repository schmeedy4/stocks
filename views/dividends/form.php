<?php

$dividend = $dividend_data ?? null;
$is_edit = isset($dividend) && isset($dividend->id);
$page_title = $is_edit ? 'Edit Dividend' : 'New Dividend';
ob_start();
?>

<h1><?= $is_edit ? 'Edit Dividend' : 'New Dividend' ?></h1>

<?php if (!empty($errors)): ?>
    <div class="error">
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php foreach ($errors as $field => $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $is_edit ? '?action=dividend_update&id=' . htmlspecialchars((string)$dividend->id) : '?action=dividend_create' ?>">
    <div style="margin-bottom: 15px;">
        <label for="instrument_id">Instrument (Ticker):</label><br>
        <select id="instrument_id" name="instrument_id" style="width: 400px; padding: 5px;">
            <option value="">(None)</option>
            <?php foreach ($instruments as $inst): ?>
                <option value="<?= $inst->id ?>" <?= ($dividend->instrument_id ?? null) === $inst->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($inst->ticker ?? $inst->name) ?> - <?= htmlspecialchars($inst->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="dividend_payer_id">Payer <span style="color: red;">*</span>:</label><br>
        <select id="dividend_payer_id" name="dividend_payer_id" required style="width: 400px; padding: 5px;">
            <option value="">(Select payer)</option>
            <?php foreach ($payers as $payer): ?>
                <option value="<?= $payer->id ?>" <?= ($dividend->dividend_payer_id ?? null) === $payer->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($payer->payer_name) ?> (<?= htmlspecialchars($payer->payer_country_code) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['dividend_payer_id'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['dividend_payer_id']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="broker_account_id">Broker Account:</label><br>
        <select id="broker_account_id" name="broker_account_id" style="width: 400px; padding: 5px;">
            <option value="">(None)</option>
            <?php foreach ($broker_accounts as $account): ?>
                <option value="<?= $account->id ?>" <?= ($dividend->broker_account_id ?? null) === $account->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($account->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="received_date">Received Date <span style="color: red;">*</span>:</label><br>
        <input type="date" id="received_date" name="received_date" value="<?= htmlspecialchars($dividend->received_date ?? '') ?>" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['received_date'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['received_date']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="ex_date">Ex-Date:</label><br>
        <input type="date" id="ex_date" name="ex_date" value="<?= htmlspecialchars($dividend->ex_date ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="pay_date">Pay Date:</label><br>
        <input type="date" id="pay_date" name="pay_date" value="<?= htmlspecialchars($dividend->pay_date ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="dividend_type_code">Dividend Type Code <span style="color: red;">*</span>:</label><br>
        <input type="text" id="dividend_type_code" name="dividend_type_code" value="<?= htmlspecialchars($dividend->dividend_type_code ?? '') ?>" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['dividend_type_code'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['dividend_type_code']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="source_country_code">Source Country Code <span style="color: red;">*</span>:</label><br>
        <input type="text" id="source_country_code" name="source_country_code" value="<?= htmlspecialchars($dividend->source_country_code ?? '') ?>" maxlength="2" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['source_country_code'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['source_country_code']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="gross_amount_eur">Gross Amount EUR <span style="color: red;">*</span>:</label><br>
        <input type="number" step="0.01" id="gross_amount_eur" name="gross_amount_eur" value="<?= htmlspecialchars($dividend->gross_amount_eur ?? '') ?>" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['gross_amount_eur'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['gross_amount_eur']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="foreign_tax_eur">Foreign Tax EUR:</label><br>
        <input type="number" step="0.01" id="foreign_tax_eur" name="foreign_tax_eur" value="<?= htmlspecialchars($dividend->foreign_tax_eur ?? '') ?>" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['foreign_tax_eur'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['foreign_tax_eur']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="original_currency">Original Currency:</label><br>
        <input type="text" id="original_currency" name="original_currency" value="<?= htmlspecialchars($dividend->original_currency ?? '') ?>" maxlength="3" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="gross_amount_original">Gross Amount Original:</label><br>
        <input type="number" step="0.000001" id="gross_amount_original" name="gross_amount_original" value="<?= htmlspecialchars($dividend->gross_amount_original ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="foreign_tax_original">Foreign Tax Original:</label><br>
        <input type="number" step="0.000001" id="foreign_tax_original" name="foreign_tax_original" value="<?= htmlspecialchars($dividend->foreign_tax_original ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="fx_rate_to_eur">FX Rate to EUR:</label><br>
        <input type="number" step="0.00000001" id="fx_rate_to_eur" name="fx_rate_to_eur" value="<?= htmlspecialchars($dividend->fx_rate_to_eur ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="payer_ident_for_export">Payer Ident for Export:</label><br>
        <input type="text" id="payer_ident_for_export" name="payer_ident_for_export" value="<?= htmlspecialchars($dividend->payer_ident_for_export ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="treaty_exemption_text">Treaty Exemption Text:</label><br>
        <input type="text" id="treaty_exemption_text" name="treaty_exemption_text" value="<?= htmlspecialchars($dividend->treaty_exemption_text ?? '') ?>" maxlength="100" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="notes">Notes:</label><br>
        <textarea id="notes" name="notes" style="width: 400px; padding: 5px; height: 60px;"><?= htmlspecialchars($dividend->notes ?? '') ?></textarea>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" style="padding: 8px 16px;">Save</button>
        <a href="?action=dividends" style="margin-left: 10px;">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

