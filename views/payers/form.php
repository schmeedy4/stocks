<?php

$is_edit = isset($payer_data->id);
$page_title = $is_edit ? 'Edit Payer' : 'New Payer';
ob_start();
?>

<h1><?= $is_edit ? 'Edit Payer' : 'New Payer' ?></h1>

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

<form method="POST" action="<?= $is_edit ? '?action=payer_update&id=' . htmlspecialchars((string)$payer_data->id) : '?action=payer_create' ?>">
    <div style="margin-bottom: 15px;">
        <label for="payer_name">Payer Name <span style="color: red;">*</span>:</label><br>
        <input type="text" id="payer_name" name="payer_name" value="<?= htmlspecialchars($payer_data->payer_name ?? '') ?>" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['payer_name'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['payer_name']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="payer_address">Payer Address <span style="color: red;">*</span>:</label><br>
        <textarea id="payer_address" name="payer_address" required style="width: 400px; padding: 5px; height: 60px;"><?= htmlspecialchars($payer_data->payer_address ?? '') ?></textarea>
        <?php if (isset($errors['payer_address'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['payer_address']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="payer_country_code">Payer Country Code <span style="color: red;">*</span>:</label><br>
        <input type="text" id="payer_country_code" name="payer_country_code" value="<?= htmlspecialchars($payer_data->payer_country_code ?? '') ?>" maxlength="2" required style="width: 400px; padding: 5px;">
        <?php if (isset($errors['payer_country_code'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['payer_country_code']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="payer_si_tax_id">SI Tax ID (if SI):</label><br>
        <input type="text" id="payer_si_tax_id" name="payer_si_tax_id" value="<?= htmlspecialchars($payer_data->payer_si_tax_id ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="payer_foreign_tax_id">Foreign Tax ID:</label><br>
        <input type="text" id="payer_foreign_tax_id" name="payer_foreign_tax_id" value="<?= htmlspecialchars($payer_data->payer_foreign_tax_id ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="default_source_country_code">Default Source Country Code:</label><br>
        <input type="text" id="default_source_country_code" name="default_source_country_code" value="<?= htmlspecialchars($payer_data->default_source_country_code ?? '') ?>" maxlength="2" style="width: 400px; padding: 5px;">
        <?php if (isset($errors['default_source_country_code'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['default_source_country_code']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="default_dividend_type_code">Default Dividend Type Code:</label><br>
        <input type="text" id="default_dividend_type_code" name="default_dividend_type_code" value="<?= htmlspecialchars($payer_data->default_dividend_type_code ?? '') ?>" style="width: 400px; padding: 5px;">
    </div>

    <?php if ($is_edit): ?>
        <div style="margin-bottom: 15px;">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= ($payer_data->is_active ?? true) ? 'checked' : '' ?>>
                Active
            </label>
        </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <button type="submit" style="padding: 8px 16px;">Save</button>
        <a href="?action=payers" style="margin-left: 10px;">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

