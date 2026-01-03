<?php

$instrument = $instrument_data ?? null;
$is_edit = isset($instrument) && isset($instrument->id);
$page_title = $is_edit ? 'Edit Instrument' : 'New Instrument';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900"><?= $is_edit ? 'Edit Instrument' : 'New Instrument' ?></h1>
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
    <form method="POST" action="<?= $is_edit ? '?action=instrument_update&id=' . htmlspecialchars((string)$instrument->id) : '?action=instrument_create' ?>" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span>:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($instrument->name ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['name'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="isin" class="block text-sm font-medium text-gray-700 mb-1">ISIN:</label>
            <input type="text" id="isin" name="isin" value="<?= htmlspecialchars($instrument->isin ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['isin'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['isin']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="ticker" class="block text-sm font-medium text-gray-700 mb-1">Ticker:</label>
            <input type="text" id="ticker" name="ticker" value="<?= htmlspecialchars($instrument->ticker ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['ticker'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['ticker']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="instrument_type" class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span>:</label>
            <select id="instrument_type" name="instrument_type" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="STOCK" <?= ($instrument->instrument_type ?? 'STOCK') === 'STOCK' ? 'selected' : '' ?>>STOCK</option>
                <option value="ETF" <?= ($instrument->instrument_type ?? '') === 'ETF' ? 'selected' : '' ?>>ETF</option>
                <option value="ADR" <?= ($instrument->instrument_type ?? '') === 'ADR' ? 'selected' : '' ?>>ADR</option>
                <option value="BOND" <?= ($instrument->instrument_type ?? '') === 'BOND' ? 'selected' : '' ?>>BOND</option>
                <option value="OTHER" <?= ($instrument->instrument_type ?? '') === 'OTHER' ? 'selected' : '' ?>>OTHER</option>
            </select>
            <?php if (isset($errors['instrument_type'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['instrument_type']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="country_code" class="block text-sm font-medium text-gray-700 mb-1">Country Code:</label>
            <input type="text" id="country_code" name="country_code" value="<?= htmlspecialchars($instrument->country_code ?? '') ?>" maxlength="2" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['country_code'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['country_code']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="trading_currency" class="block text-sm font-medium text-gray-700 mb-1">Trading Currency:</label>
            <input type="text" id="trading_currency" name="trading_currency" value="<?= htmlspecialchars($instrument->trading_currency ?? '') ?>" maxlength="3" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['trading_currency'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['trading_currency']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="dividend_payer_id" class="block text-sm font-medium text-gray-700 mb-1">Default Dividend Payer:</label>
            <select id="dividend_payer_id" name="dividend_payer_id" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">(None)</option>
                <?php foreach ($payers ?? [] as $payer): ?>
                    <option value="<?= $payer->id ?>" <?= ($instrument->dividend_payer_id ?? null) === $payer->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($payer->payer_name) ?> (<?= htmlspecialchars($payer->payer_country_code) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['dividend_payer_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['dividend_payer_id']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" id="is_private" name="is_private" value="1" <?= ($instrument->is_private ?? false) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Private</span>
            </label>
            <?php if (isset($errors['is_private'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['is_private']) ?></p>
            <?php endif; ?>
        </div>

        <div class="pt-4 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Save
            </button>
            <a href="?action=instruments" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

