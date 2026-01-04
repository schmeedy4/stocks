<?php

$page_title = 'Dividends';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">Dividends</h1>
    <a href="?action=dividend_new" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
        Add Dividend
    </a>
</div>

<form method="GET" action="?action=dividends" class="mb-6 flex items-center gap-3">
    <input type="hidden" name="action" value="dividends">
    <label for="year" class="text-sm font-medium text-gray-700">Year:</label>
    <select id="year" name="year" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
            <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Filter</button>
</form>

<?php if (empty($dividends)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No dividends found for year <?= $year ?>.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrument</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payer</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross EUR</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Foreign Tax EUR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Documents</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($dividends as $index => $dividend): ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('d.m.Y', strtotime($dividend->received_date)) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if ($dividend->instrument_id !== null && isset($instruments[$dividend->instrument_id])): ?>
                                    <?= htmlspecialchars($instruments[$dividend->instrument_id]->ticker ?? $instruments[$dividend->instrument_id]->name) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if (isset($payers[$dividend->dividend_payer_id])): ?>
                                    <?= htmlspecialchars($payers[$dividend->dividend_payer_id]->payer_name) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= htmlspecialchars($dividend->gross_amount_eur) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right"><?= $dividend->foreign_tax_eur !== null ? htmlspecialchars($dividend->foreign_tax_eur) : '-' ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($dividend->source_country_code) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($dividend->dividend_type_code) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                <?php 
                                $doc_count = $document_counts[$dividend->id] ?? 0;
                                if ($doc_count > 0): 
                                ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?= $doc_count ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $dividend->is_voided ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= $dividend->is_voided ? 'Voided' : 'Active' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=dividend_edit&id=<?= $dividend->id ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <span class="text-gray-300 mx-1">|</span>
                                <form method="POST" action="?action=dividend_void&id=<?= $dividend->id ?>" class="inline">
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 underline bg-transparent border-none cursor-pointer">
                                        <?= $dividend->is_voided ? 'Unvoid' : 'Void' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
