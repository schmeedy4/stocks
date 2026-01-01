<?php

$dividend = $dividend_data ?? null;
$is_edit = isset($dividend) && isset($dividend->id);
$page_title = $is_edit ? 'Edit Dividend' : 'New Dividend';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900"><?= $is_edit ? 'Edit Dividend' : 'New Dividend' ?></h1>
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
    <form method="POST" action="<?= $is_edit ? '?action=dividend_update&id=' . htmlspecialchars((string)$dividend->id) : '?action=dividend_create' ?>" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label for="instrument_id" class="block text-sm font-medium text-gray-700 mb-1">Instrument (Ticker):</label>
            <select id="instrument_id" name="instrument_id" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">(None)</option>
                <?php foreach ($instruments as $inst): ?>
                    <option value="<?= $inst->id ?>" <?= ($dividend->instrument_id ?? null) === $inst->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($inst->ticker ?? $inst->name) ?> - <?= htmlspecialchars($inst->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="dividend_payer_id" class="block text-sm font-medium text-gray-700 mb-1">Payer <span class="text-red-500">*</span>:</label>
            <select id="dividend_payer_id" name="dividend_payer_id" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">(Select payer)</option>
                <?php foreach ($payers as $payer): ?>
                    <option value="<?= $payer->id ?>" <?= ($dividend->dividend_payer_id ?? null) === $payer->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($payer->payer_name) ?> (<?= htmlspecialchars($payer->payer_country_code) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['dividend_payer_id'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['dividend_payer_id']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="broker_account_id" class="block text-sm font-medium text-gray-700 mb-1">Broker Account:</label>
            <select id="broker_account_id" name="broker_account_id" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">(None)</option>
                <?php foreach ($broker_accounts as $account): ?>
                    <option value="<?= $account->id ?>" <?= ($dividend->broker_account_id ?? null) === $account->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($account->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="received_date" class="block text-sm font-medium text-gray-700 mb-1">Received Date <span class="text-red-500">*</span>:</label>
            <input type="date" id="received_date" name="received_date" value="<?= htmlspecialchars($dividend->received_date ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['received_date'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['received_date']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="ex_date" class="block text-sm font-medium text-gray-700 mb-1">Ex-Date:</label>
            <input type="date" id="ex_date" name="ex_date" value="<?= htmlspecialchars($dividend->ex_date ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="pay_date" class="block text-sm font-medium text-gray-700 mb-1">Pay Date:</label>
            <input type="date" id="pay_date" name="pay_date" value="<?= htmlspecialchars($dividend->pay_date ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="dividend_type_code" class="block text-sm font-medium text-gray-700 mb-1">Dividend Type Code <span class="text-red-500">*</span>:</label>
            <input type="text" id="dividend_type_code" name="dividend_type_code" value="<?= htmlspecialchars($dividend->dividend_type_code ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['dividend_type_code'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['dividend_type_code']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="source_country_code" class="block text-sm font-medium text-gray-700 mb-1">Source Country Code <span class="text-red-500">*</span>:</label>
            <input type="text" id="source_country_code" name="source_country_code" value="<?= htmlspecialchars($dividend->source_country_code ?? '') ?>" maxlength="2" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['source_country_code'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['source_country_code']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="gross_amount_eur" class="block text-sm font-medium text-gray-700 mb-1">Gross Amount EUR <span class="text-red-500">*</span>:</label>
            <input type="number" step="0.01" id="gross_amount_eur" name="gross_amount_eur" value="<?= htmlspecialchars($dividend->gross_amount_eur ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['gross_amount_eur'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['gross_amount_eur']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="foreign_tax_eur" class="block text-sm font-medium text-gray-700 mb-1">Foreign Tax EUR:</label>
            <input type="number" step="0.01" id="foreign_tax_eur" name="foreign_tax_eur" value="<?= htmlspecialchars($dividend->foreign_tax_eur ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['foreign_tax_eur'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['foreign_tax_eur']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="original_currency" class="block text-sm font-medium text-gray-700 mb-1">Original Currency:</label>
            <input type="text" id="original_currency" name="original_currency" value="<?= htmlspecialchars($dividend->original_currency ?? '') ?>" maxlength="3" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="gross_amount_original" class="block text-sm font-medium text-gray-700 mb-1">Gross Amount Original:</label>
            <input type="number" step="0.000001" id="gross_amount_original" name="gross_amount_original" value="<?= htmlspecialchars($dividend->gross_amount_original ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="foreign_tax_original" class="block text-sm font-medium text-gray-700 mb-1">Foreign Tax Original:</label>
            <input type="number" step="0.000001" id="foreign_tax_original" name="foreign_tax_original" value="<?= htmlspecialchars($dividend->foreign_tax_original ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="fx_rate_to_eur" class="block text-sm font-medium text-gray-700 mb-1">FX Rate to EUR:</label>
            <input type="number" step="0.00000001" id="fx_rate_to_eur" name="fx_rate_to_eur" value="<?= htmlspecialchars($dividend->fx_rate_to_eur ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="payer_ident_for_export" class="block text-sm font-medium text-gray-700 mb-1">Payer Ident for Export:</label>
            <input type="text" id="payer_ident_for_export" name="payer_ident_for_export" value="<?= htmlspecialchars($dividend->payer_ident_for_export ?? '') ?>" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="treaty_exemption_text" class="block text-sm font-medium text-gray-700 mb-1">Treaty Exemption Text:</label>
            <input type="text" id="treaty_exemption_text" name="treaty_exemption_text" value="<?= htmlspecialchars($dividend->treaty_exemption_text ?? '') ?>" maxlength="100" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes:</label>
            <textarea id="notes" name="notes" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" rows="4"><?= htmlspecialchars($dividend->notes ?? '') ?></textarea>
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

            <!-- Currently Linked Documents (edit mode only) -->
            <?php if ($is_edit && !empty($linked_document_ids ?? [])): ?>
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
                                                href="?action=document_download&id=<?= $doc->id ?>" 
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
            <a href="?action=dividends" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
            </a>
        </div>
    </form>
    
    <!-- Hidden delete forms (outside main form to avoid nesting issues) -->
    <?php if ($is_edit && !empty($linked_document_ids ?? [])): ?>
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
                action="?action=document_delete&id=<?= $doc->id ?>&dividend_id=<?= $dividend->id ?>"
                style="display: none;"
            >
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

