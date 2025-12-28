<?php

$is_edit = isset($payer_data->id);
$page_title = $is_edit ? 'Edit Payer' : 'New Payer';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900"><?= $is_edit ? 'Edit Payer' : 'New Payer' ?></h1>
</div>

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

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= $is_edit ? '?action=payer_update&id=' . htmlspecialchars((string)$payer_data->id) : '?action=payer_create' ?>" class="space-y-4">
        <div>
            <label for="payer_name" class="block text-sm font-medium text-gray-700 mb-1">Payer Name <span class="text-red-500">*</span>:</label>
            <input type="text" id="payer_name" name="payer_name" value="<?= htmlspecialchars($payer_data->payer_name ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['payer_name'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['payer_name']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="payer_address" class="block text-sm font-medium text-gray-700 mb-1">Payer Address <span class="text-red-500">*</span>:</label>
            <textarea id="payer_address" name="payer_address" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" rows="3"><?= htmlspecialchars($payer_data->payer_address ?? '') ?></textarea>
            <?php if (isset($errors['payer_address'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['payer_address']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="payer_country_code" class="block text-sm font-medium text-gray-700 mb-1">Payer Country Code <span class="text-red-500">*</span>:</label>
            <input type="text" id="payer_country_code" name="payer_country_code" value="<?= htmlspecialchars($payer_data->payer_country_code ?? '') ?>" maxlength="2" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['payer_country_code'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['payer_country_code']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="payer_si_tax_id" class="block text-sm font-medium text-gray-700 mb-1">SI Tax ID (if SI):</label>
            <input type="text" id="payer_si_tax_id" name="payer_si_tax_id" value="<?= htmlspecialchars($payer_data->payer_si_tax_id ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="payer_foreign_tax_id" class="block text-sm font-medium text-gray-700 mb-1">Foreign Tax ID:</label>
            <input type="text" id="payer_foreign_tax_id" name="payer_foreign_tax_id" value="<?= htmlspecialchars($payer_data->payer_foreign_tax_id ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="default_source_country_code" class="block text-sm font-medium text-gray-700 mb-1">Default Source Country Code:</label>
            <input type="text" id="default_source_country_code" name="default_source_country_code" value="<?= htmlspecialchars($payer_data->default_source_country_code ?? '') ?>" maxlength="2" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['default_source_country_code'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['default_source_country_code']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="default_dividend_type_code" class="block text-sm font-medium text-gray-700 mb-1">Default Dividend Type Code:</label>
            <input type="text" id="default_dividend_type_code" name="default_dividend_type_code" value="<?= htmlspecialchars($payer_data->default_dividend_type_code ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <div class="mt-2 text-sm text-gray-600 max-w-md">
                <p class="font-medium mb-1">Common FURS codes:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>DIV = regular cash dividend from shares (most stocks &amp; ETFs, quarterly or annual)</li>
                    <li>DIV_POS = profit participation (not typical public-company dividends)</li>
                    <li>DRUGO = other capital income (use only for special cases)</li>
                </ul>
            </div>
        </div>

        <?php if ($is_edit): ?>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" <?= ($payer_data->is_active ?? true) ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>
        <?php endif; ?>

        <div class="pt-4 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Save
            </button>
            <a href="?action=payers" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

