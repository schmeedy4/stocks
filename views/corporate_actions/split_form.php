<?php

$page_title = 'Corporate Actions - Stock Split';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Corporate Actions - Stock Split</h1>
</div>

<?php if (isset($success_message)): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
        <strong class="block mb-2">Please fix the following errors:</strong>
        <ul class="list-disc list-inside">
            <?php foreach ($errors as $field => $message): ?>
                <li><?= htmlspecialchars($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4">
    <p><strong>Note:</strong> Stock splits adjust quantities of open FIFO lots. Cost basis remains unchanged. This does not create trade records.</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="?action=corporate_action_apply_split" class="space-y-4">
        <div>
            <label for="instrument_id" class="block text-sm font-medium text-gray-700 mb-1">Instrument <span class="text-red-500">*</span>:</label>
            <select id="instrument_id" name="instrument_id" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Instrument --</option>
                <?php foreach ($instruments as $instrument): ?>
                    <?php $display = $instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name; ?>
                    <option value="<?= $instrument->id ?>" <?= (isset($split_data->instrument_id) && $split_data->instrument_id == $instrument->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($display) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['instrument_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['instrument_id']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="split_date" class="block text-sm font-medium text-gray-700 mb-1">Split Date <span class="text-red-500">*</span>:</label>
            <input type="date" id="split_date" name="split_date" value="<?= htmlspecialchars($split_data->split_date ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Only lots opened on or before this date will be adjusted.</p>
            <?php if (isset($errors['split_date'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['split_date']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="ratio_from" class="block text-sm font-medium text-gray-700 mb-1">Ratio From <span class="text-red-500">*</span>:</label>
            <input type="number" id="ratio_from" name="ratio_from" value="<?= htmlspecialchars($split_data->ratio_from ?? '1') ?>" min="1" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Example: For a 2-for-1 split, enter 1</p>
            <?php if (isset($errors['ratio_from'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['ratio_from']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="ratio_to" class="block text-sm font-medium text-gray-700 mb-1">Ratio To <span class="text-red-500">*</span>:</label>
            <input type="number" id="ratio_to" name="ratio_to" value="<?= htmlspecialchars($split_data->ratio_to ?? '1') ?>" min="1" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Example: For a 2-for-1 split, enter 2</p>
            <?php if (isset($errors['ratio_to'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['ratio_to']) ?></p>
            <?php endif; ?>
        </div>

        <div class="pt-4 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Apply Stock Split
            </button>
            <a href="?action=trades" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

