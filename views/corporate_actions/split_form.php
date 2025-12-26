<?php

$page_title = 'Corporate Actions - Stock Split';
ob_start();
?>

<h1>Corporate Actions - Stock Split</h1>

<?php if (isset($success_message)): ?>
    <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

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

<p><strong>Note:</strong> Stock splits adjust quantities of open FIFO lots. Cost basis remains unchanged. This does not create trade records.</p>

<form method="POST" action="?action=corporate_action_apply_split">
    <div style="margin-bottom: 15px;">
        <label for="instrument_id">Instrument <span style="color: red;">*</span>:</label><br>
        <select id="instrument_id" name="instrument_id" required style="width: 400px; padding: 5px;">
            <option value="">-- Select Instrument --</option>
            <?php foreach ($instruments as $instrument): ?>
                <?php $display = $instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name; ?>
                <option value="<?= $instrument->id ?>" <?= (isset($split_data->instrument_id) && $split_data->instrument_id == $instrument->id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($display) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['instrument_id'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['instrument_id']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="split_date">Split Date <span style="color: red;">*</span>:</label><br>
        <input type="date" id="split_date" name="split_date" value="<?= htmlspecialchars($split_data->split_date ?? '') ?>" required style="width: 400px; padding: 5px;">
        <br><small>Only lots opened on or before this date will be adjusted.</small>
        <?php if (isset($errors['split_date'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['split_date']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="ratio_from">Ratio From <span style="color: red;">*</span>:</label><br>
        <input type="number" id="ratio_from" name="ratio_from" value="<?= htmlspecialchars($split_data->ratio_from ?? '1') ?>" min="1" required style="width: 400px; padding: 5px;">
        <br><small>Example: For a 2-for-1 split, enter 1</small>
        <?php if (isset($errors['ratio_from'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['ratio_from']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="ratio_to">Ratio To <span style="color: red;">*</span>:</label><br>
        <input type="number" id="ratio_to" name="ratio_to" value="<?= htmlspecialchars($split_data->ratio_to ?? '1') ?>" min="1" required style="width: 400px; padding: 5px;">
        <br><small>Example: For a 2-for-1 split, enter 2</small>
        <?php if (isset($errors['ratio_to'])): ?>
            <span style="color: red;"><?= htmlspecialchars($errors['ratio_to']) ?></span>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" style="padding: 8px 16px;">Apply Stock Split</button>
        <a href="?action=trades" style="margin-left: 10px;">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

