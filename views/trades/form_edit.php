<?php

$page_title = 'Edit Trade';
ob_start();
?>

<h1>Edit <?= htmlspecialchars($trade_type ?? 'Trade') ?> Trade</h1>

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

<?php if (isset($trade_type) && $trade_type === 'SELL'): ?>
    <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
        <strong>Note:</strong> Changing quantity or price for SELL trades will recalculate FIFO allocations.
    </div>
<?php endif; ?>

<form method="POST" action="?action=trade_update&id=<?= htmlspecialchars((string)$trade_data->id) ?>">
    <div style="margin-bottom: 15px;">
        <label for="instrument_id">Instrument <span style="color: red;">*</span>:</label><br>
        <select id="instrument_id" name="instrument_id" required style="width: 400px; padding: 5px;">
            <option value="">-- Select Instrument --</option>
            <?php foreach ($instruments as $instrument): ?>
                <?php $display = $instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name; ?>
                <option value="<?= $instrument->id ?>" <?= (isset($trade_data->instrument_id) && $trade_data->instrument_id == $instrument->id) ? 'selected' : '' ?>>
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
                <option value="<?= $broker['id'] ?>" <?= (isset($trade_data->broker_account_id) && $trade_data->broker_account_id == $broker['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($broker['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="trade_date">Trade Date <span style="color: red;">*</span>:</label><br>
        <input type="date" id="trade_date" name="trade_date" value="<?= htmlspecialchars($trade_data->trade_date ?? '') ?>" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['trade_date'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['trade_date']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="quantity">Quantity <span style="color: red;">*</span>:</label><br>
        <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($trade_data->quantity ?? '') ?>" step="any" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['quantity'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['quantity']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="price_per_unit">Price per Unit <span style="color: red;">*</span>:</label><br>
        <input type="number" id="price_per_unit" name="price_per_unit" value="<?= htmlspecialchars($trade_data->price_per_unit ?? '') ?>" step="any" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['price_per_unit'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['price_per_unit']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="trade_currency">Trade Currency <span style="color: red;">*</span>:</label><br>
        <input type="text" id="trade_currency" name="trade_currency" value="<?= htmlspecialchars($trade_data->trade_currency ?? 'EUR') ?>" maxlength="3" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['trade_currency'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['trade_currency']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="fx_rate_to_eur">FX Rate to EUR <span style="color: red;">*</span>:</label><br>
        <input type="number" id="fx_rate_to_eur" name="fx_rate_to_eur" value="<?= htmlspecialchars($trade_data->fx_rate_to_eur ?? '1.00000000') ?>" step="any" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['fx_rate_to_eur'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['fx_rate_to_eur']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="fee_eur">Fee EUR:</label><br>
        <input type="number" id="fee_eur" name="fee_eur" value="<?= htmlspecialchars($trade_data->fee_eur ?? '') ?>" step="0.01" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['fee_eur'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['fee_eur']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="notes">Notes:</label><br>
        <textarea id="notes" name="notes" style="width: 400px; padding: 5px; height: 80px;"><?= htmlspecialchars($trade_data->notes ?? '') ?></textarea>
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

