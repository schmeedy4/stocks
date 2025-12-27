<?php

$instrument = $instrument_data ?? null;
$is_edit = isset($instrument) && isset($instrument->id);
$page_title = $is_edit ? 'Edit Instrument' : 'New Instrument';
ob_start();
?>

<h1><?= $is_edit ? 'Edit Instrument' : 'New Instrument' ?></h1>

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

<form method="POST" action="<?= $is_edit ? '?action=instrument_update&id=' . htmlspecialchars((string)$instrument->id) : '?action=instrument_create' ?>">
    <div style="margin-bottom: 15px;">
        <label for="name">Name <span style="color: red;">*</span>:</label><br>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($instrument->name ?? '') ?>" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['name'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['name']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="isin">ISIN:</label><br>
        <input type="text" id="isin" name="isin" value="<?= htmlspecialchars($instrument->isin ?? '') ?>" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['isin'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['isin']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="ticker">Ticker:</label><br>
        <input type="text" id="ticker" name="ticker" value="<?= htmlspecialchars($instrument->ticker ?? '') ?>" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['ticker'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['ticker']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="instrument_type">Type <span style="color: red;">*</span>:</label><br>
        <select id="instrument_type" name="instrument_type" required style="width: 400px; padding: 5px;">
            <option value="STOCK" <?= ($instrument->instrument_type ?? 'STOCK') === 'STOCK' ? 'selected' : '' ?>>STOCK</option>
            <option value="ETF" <?= ($instrument->instrument_type ?? '') === 'ETF' ? 'selected' : '' ?>>ETF</option>
            <option value="ADR" <?= ($instrument->instrument_type ?? '') === 'ADR' ? 'selected' : '' ?>>ADR</option>
            <option value="BOND" <?= ($instrument->instrument_type ?? '') === 'BOND' ? 'selected' : '' ?>>BOND</option>
            <option value="OTHER" <?= ($instrument->instrument_type ?? '') === 'OTHER' ? 'selected' : '' ?>>OTHER</option>
        </select>
        <?php if (isset($errors['instrument_type'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['instrument_type']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="country_code">Country Code:</label><br>
        <input type="text" id="country_code" name="country_code" value="<?= htmlspecialchars($instrument->country_code ?? '') ?>" maxlength="2" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['country_code'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['country_code']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="trading_currency">Trading Currency:</label><br>
        <input type="text" id="trading_currency" name="trading_currency" value="<?= htmlspecialchars($instrument->trading_currency ?? '') ?>" maxlength="3" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['trading_currency'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['trading_currency']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="dividend_payer_id">Default Dividend Payer:</label><br>
        <select id="dividend_payer_id" name="dividend_payer_id" style="width: 400px; padding: 5px;">
            <option value="">(None)</option>
            <?php foreach ($payers ?? [] as $payer): ?>
                <option value="<?= $payer->id ?>" <?= ($instrument->dividend_payer_id ?? null) === $payer->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($payer->payer_name) ?> (<?= htmlspecialchars($payer->payer_country_code) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['dividend_payer_id'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['dividend_payer_id']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" style="padding: 8px 16px;">Save</button>
        <a href="?action=instruments" style="margin-left: 10px;">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

