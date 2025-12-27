<?php

$page_title = 'New BUY Trade';
ob_start();
?>

<h1>New BUY Trade</h1>

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

<form method="POST" action="?action=trade_create_buy">
    <div style="margin-bottom: 15px;">
        <label for="instrument_id">Instrument <span style="color: red;">*</span>:</label><br>
        <select id="instrument_id" name="instrument_id" required style="width: 400px; padding: 5px;">
            <option value="">-- Select Instrument --</option>
            <?php foreach ($instruments as $instrument): ?>
                <?php $display = $instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name; ?>
                <option value="<?= $instrument->id ?>" <?= (isset($trade->instrument_id) && $trade->instrument_id == $instrument->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($display) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['instrument_id'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['instrument_id']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="broker_account_id">Broker Account:</label><br>
        <select id="broker_account_id" name="broker_account_id" style="width: 400px; padding: 5px;">
            <option value="">-- None --</option>
            <?php foreach ($broker_accounts as $broker): ?>
                <option value="<?= $broker['id'] ?>" <?= (isset($trade->broker_account_id) && $trade->broker_account_id == $broker['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($broker['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="trade_date">Trade Date <span style="color: red;">*</span>:</label><br>
        <input type="date" id="trade_date" name="trade_date" value="<?= htmlspecialchars($trade->trade_date ?? '') ?>" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['trade_date'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['trade_date']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="quantity">Quantity <span style="color: red;">*</span>:</label><br>
        <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($trade->quantity ?? '') ?>" step="any" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['quantity'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['quantity']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="price_per_unit">Price per Unit <span style="color: red;">*</span>:</label><br>
        <input type="number" id="price_per_unit" name="price_per_unit" value="<?= htmlspecialchars($trade->price_per_unit ?? '') ?>" step="any" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['price_per_unit'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['price_per_unit']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="trade_currency">Trade Currency <span style="color: red;">*</span>:</label><br>
        <input type="text" id="trade_currency" name="trade_currency" value="<?= htmlspecialchars($trade->trade_currency ?? 'EUR') ?>" maxlength="3" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['trade_currency'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['trade_currency']) ?></span>
        <?php endif; ?>
    </div>

    <?php
    $trade_currency = strtoupper(trim($trade->trade_currency ?? 'EUR'));
    $is_eur = ($trade_currency === 'EUR');
    if (!$is_eur):
        // Calculate broker_fx_rate from stored fx_rate_to_eur for display (if available)
        $broker_fx_rate = '';
        if (isset($trade->fx_rate_to_eur) && $trade->fx_rate_to_eur !== '' && $trade->fx_rate_to_eur !== '0') {
            $broker_fx_rate = number_format(1 / (float)$trade->fx_rate_to_eur, 8, '.', '');
        }
        if (isset($trade->broker_fx_rate)) {
            $broker_fx_rate = $trade->broker_fx_rate;
        }
    ?>
    <div style="margin-bottom: 15px;">
        <label for="broker_fx_rate">FX Rate (EUR → <?= htmlspecialchars($trade_currency) ?>) <span style="color: red;">*</span>:</label><br>
        <input type="number" id="broker_fx_rate" name="broker_fx_rate" value="<?= htmlspecialchars($broker_fx_rate) ?>" step="any" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['broker_fx_rate'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['broker_fx_rate']) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div style="margin-bottom: 15px;">
        <label for="fee_eur">Fee EUR:</label><br>
        <input type="number" id="fee_eur" name="fee_eur" value="<?= htmlspecialchars($trade->fee_eur ?? '') ?>" step="0.01" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['fee_eur'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['fee_eur']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="notes">Notes:</label><br>
        <textarea id="notes" name="notes" style="width: 400px; padding: 5px; height: 80px;"><?= htmlspecialchars($trade->notes ?? '') ?></textarea>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" style="padding: 8px 16px;">Save</button>
        <a href="?action=trades" style="margin-left: 10px;">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

