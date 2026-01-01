<?php

$page_title = 'Edit Trade';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Edit <?= htmlspecialchars($trade_type ?? 'Trade') ?> Trade</h1>
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

<?php if (isset($trade_type) && $trade_type === 'SELL'): ?>
    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg p-4">
        <strong>Note:</strong> Changing quantity or price for SELL trades will recalculate FIFO allocations.
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="?action=trade_update&id=<?= htmlspecialchars((string)$trade_data->id) ?>" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label for="instrument_id" class="block text-sm font-medium text-gray-700 mb-1">Instrument <span class="text-red-500">*</span>:</label>
            <select id="instrument_id" name="instrument_id" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Instrument --</option>
                <?php foreach ($instruments as $instrument): ?>
                    <?php $display = $instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name; ?>
                    <option value="<?= $instrument->id ?>" <?= (isset($trade_data->instrument_id) && $trade_data->instrument_id == $instrument->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($display) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['instrument_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['instrument_id']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="broker_account_id" class="block text-sm font-medium text-gray-700 mb-1">Broker Account:</label>
            <select id="broker_account_id" name="broker_account_id" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- None --</option>
                <?php foreach ($broker_accounts as $broker): ?>
                    <option value="<?= $broker['id'] ?>" <?= (isset($trade_data->broker_account_id) && $trade_data->broker_account_id == $broker['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($broker['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="trade_date" class="block text-sm font-medium text-gray-700 mb-1">Trade Date <span class="text-red-500">*</span>:</label>
            <input type="date" id="trade_date" name="trade_date" value="<?= htmlspecialchars($trade_data->trade_date ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['trade_date'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['trade_date']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span>:</label>
            <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($trade_data->quantity ?? '') ?>" step="any" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['quantity'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['quantity']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="price_per_unit" class="block text-sm font-medium text-gray-700 mb-1">Price per Unit <span class="text-red-500">*</span>:</label>
            <input type="number" id="price_per_unit" name="price_per_unit" value="<?= htmlspecialchars($trade_data->price_per_unit ?? '') ?>" step="any" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['price_per_unit'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['price_per_unit']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="trade_currency" class="block text-sm font-medium text-gray-700 mb-1">Trade Currency <span class="text-red-500">*</span>:</label>
            <select id="trade_currency" name="trade_currency" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="USD" <?= (isset($trade_data->trade_currency) && $trade_data->trade_currency === 'USD') || (!isset($trade_data->trade_currency)) ? 'selected' : '' ?>>USD</option>
                <option value="EUR" <?= (isset($trade_data->trade_currency) && $trade_data->trade_currency === 'EUR') ? 'selected' : '' ?>>EUR</option>
            </select>
            <?php if (isset($errors['trade_currency'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['trade_currency']) ?></p>
            <?php endif; ?>
        </div>

        <?php
        $trade_currency = strtoupper(trim($trade_data->trade_currency ?? 'EUR'));
        $is_eur = ($trade_currency === 'EUR');
        // Calculate broker_fx_rate from stored fx_rate_to_eur for display (if available)
        // Round to 4 decimals for clean display
        $broker_fx_rate = '';
        if (isset($trade_data->fx_rate_to_eur) && $trade_data->fx_rate_to_eur !== '' && $trade_data->fx_rate_to_eur !== '0') {
            $broker_fx_rate = number_format(1 / (float)$trade_data->fx_rate_to_eur, 4, '.', '');
        }
        if (isset($trade_data->broker_fx_rate)) {
            // If already provided (e.g., from old_input), use as-is but ensure it's reasonable
            $broker_fx_rate = $trade_data->broker_fx_rate;
        }
        ?>
        <div id="broker_fx_rate_container" style="display: <?= $is_eur ? 'none' : 'block' ?>;">
            <label for="broker_fx_rate" class="block text-sm font-medium text-gray-700 mb-1">FX Rate (EUR → <?= htmlspecialchars($trade_currency) ?>) <span class="text-red-500">*</span>:</label>
            <input type="number" id="broker_fx_rate" name="broker_fx_rate" value="<?= htmlspecialchars($broker_fx_rate) ?>" step="any" <?= !$is_eur ? 'required' : '' ?> class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['broker_fx_rate'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['broker_fx_rate']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="fee_eur" class="block text-sm font-medium text-gray-700 mb-1">Fee EUR:</label>
            <input type="number" id="fee_eur" name="fee_eur" value="<?= htmlspecialchars($trade_data->fee_eur ?? '') ?>" step="0.01" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['fee_eur'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['fee_eur']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes:</label>
            <textarea id="notes" name="notes" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" rows="4"><?= htmlspecialchars($trade_data->notes ?? '') ?></textarea>
        </div>

        <!-- Documents Section -->
        <div class="pt-4 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Supporting Documents</h3>

            <!-- File Upload -->
            <div class="mb-6">
                <label for="documents" class="block text-sm font-medium text-gray-700 mb-1">Upload New Documents (PDF/CSV):</label>
                <input 
                    type="file" 
                    id="documents" 
                    name="documents[]" 
                    multiple 
                    accept=".pdf,.csv,.txt"
                    class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                <p class="mt-1 text-xs text-gray-500">You can select multiple files. Maximum 10MB per file.</p>
                <?php if (isset($errors['documents'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['documents']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Currently Linked Documents -->
            <?php if (!empty($linked_document_ids ?? [])): ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Currently Linked Documents:</label>
                    <div class="max-w-md">
                        <?php 
                        $linked_docs = [];
                        foreach ($existing_documents ?? [] as $doc) {
                            if (in_array($doc->id, $linked_document_ids)) {
                                $linked_docs[] = $doc;
                            }
                        }
                        ?>
                        <?php if (!empty($linked_docs)): ?>
                            <ul class="list-none border border-gray-300 rounded-md p-3 space-y-2">
                                <?php foreach ($linked_docs as $doc): ?>
                                    <li class="flex items-center justify-between py-1">
                                        <span class="text-sm text-gray-700">
                                            <?= htmlspecialchars($doc->original_filename) ?>
                                            <span class="text-gray-500 text-xs">(<?= number_format($doc->file_size_bytes / 1024, 1) ?> KB)</span>
                                        </span>
                                        <div class="flex gap-2">
                                            <a 
                                                href="?action=trade_document_download&id=<?= $doc->id ?>" 
                                                class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                                                target="_blank"
                                            >
                                                Download
                                            </a>
                                            <button 
                                                type="button"
                                                data-filename="<?= htmlspecialchars($doc->original_filename, ENT_QUOTES, 'UTF-8') ?>"
                                                data-form-id="delete-form-<?= $doc->id ?>"
                                                onclick="const filename = this.getAttribute('data-filename'); const formId = this.getAttribute('data-form-id'); if(confirm('Do you really want to delete ' + filename + '?\\n\\nThis action cannot be undone and will permanently remove the file.')) { document.getElementById(formId).submit(); }"
                                                class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="pt-4 flex gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                Save
            </button>
            <a href="?action=trades" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
            </a>
        </div>
    </form>
    
    <!-- Hidden delete forms (outside main form to avoid nesting issues) -->
    <?php if (!empty($linked_document_ids ?? [])): ?>
        <?php 
        $linked_docs = [];
        foreach ($existing_documents ?? [] as $doc) {
            if (in_array($doc->id, $linked_document_ids)) {
                $linked_docs[] = $doc;
            }
        }
        ?>
        <?php foreach ($linked_docs as $doc): ?>
            <form 
                id="delete-form-<?= $doc->id ?>"
                method="POST" 
                action="?action=trade_document_delete&id=<?= $doc->id ?>&trade_id=<?= $trade_data->id ?>"
                style="display: none;"
            >
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';

    const tradeCurrencySelect = document.getElementById('trade_currency');
    const brokerFxRateContainer = document.getElementById('broker_fx_rate_container');
    const brokerFxRateInput = document.getElementById('broker_fx_rate');

    // Toggle FX rate field based on currency selection
    function toggleFxRateField() {
        if (brokerFxRateContainer && brokerFxRateInput) {
            if (tradeCurrencySelect.value === 'EUR') {
                brokerFxRateContainer.style.display = 'none';
                brokerFxRateInput.removeAttribute('required');
            } else {
                brokerFxRateContainer.style.display = 'block';
                brokerFxRateInput.setAttribute('required', 'required');
            }
        }
    }
    
    if (tradeCurrencySelect) {
        tradeCurrencySelect.addEventListener('change', toggleFxRateField);
        // Initialize on page load
        toggleFxRateField();
    }
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
