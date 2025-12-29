<?php

$page_title = 'Price Updates';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Price Updates</h1>
</div>

<?php
// Show result from update if available
$update_result = $_SESSION['price_update_result'] ?? null;
if ($update_result !== null) {
    unset($_SESSION['price_update_result']);
    
    $updated = $update_result['updated'] ?? 0;
    $skipped = $update_result['skipped'] ?? 0;
    $failed = $update_result['failed'] ?? 0;
    $duration = $update_result['duration'] ?? 0;
    $errors = $update_result['errors'] ?? [];
    $price_date = $update_result['price_date'] ?? date('Y-m-d');
    ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
        <h3 class="font-semibold mb-2">Update completed for <?= htmlspecialchars($price_date) ?></h3>
        <p class="text-sm"><strong>Updated:</strong> <?= (int)$updated ?> | 
           <strong>Skipped:</strong> <?= (int)$skipped ?> | 
           <strong>Failed:</strong> <?= (int)$failed ?> | 
           <strong>Duration:</strong> <?= number_format($duration, 2) ?> seconds</p>
        <?php if (!empty($errors)): ?>
            <h4 class="font-semibold mt-3 mb-1">Errors:</h4>
            <ul class="list-disc list-inside text-sm">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}
?>

<?php
// Show result from 5 days update if available
$update_5days_result = $_SESSION['price_update_5days_result'] ?? null;
if ($update_5days_result !== null) {
    unset($_SESSION['price_update_5days_result']);
    
    $symbols_updated = $update_5days_result['symbols_updated'] ?? 0;
    $symbols_failed = $update_5days_result['symbols_failed'] ?? 0;
    $rows_upserted = $update_5days_result['rows_upserted'] ?? 0;
    $duration = $update_5days_result['duration'] ?? 0;
    $errors = $update_5days_result['errors'] ?? [];
    $min_date = $update_5days_result['min_date'] ?? null;
    $max_date = $update_5days_result['max_date'] ?? null;
    ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
        <h3 class="font-semibold mb-2">Last 5 daily bars update completed</h3>
        <p class="text-sm"><strong>Symbols updated:</strong> <?= (int)$symbols_updated ?> | 
           <strong>Symbols failed:</strong> <?= (int)$symbols_failed ?> | 
           <strong>Rows upserted:</strong> <?= (int)$rows_upserted ?> | 
           <strong>Duration:</strong> <?= number_format($duration, 2) ?> seconds</p>
        <?php if ($min_date !== null && $max_date !== null): ?>
            <p class="text-sm mt-1"><strong>Fetched date range:</strong> <?= htmlspecialchars($min_date) ?> → <?= htmlspecialchars($max_date) ?></p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <h4 class="font-semibold mt-3 mb-1">Errors:</h4>
            <ul class="list-disc list-inside text-sm">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}
?>

<div class="mb-6 flex gap-4">
    <form method="POST" action="?action=price_update" class="bg-white rounded-lg shadow p-4">
        <div class="mb-3">
            <label class="flex items-center">
                <input type="checkbox" name="force_update" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Force update (ignore existing prices for today)</span>
            </label>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            Update prices (today)
        </button>
    </form>

    <form method="POST" action="?action=price_update_5days" class="bg-white rounded-lg shadow p-4">
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
            Update last 5 days
        </button>
    </form>
</div>

<?php if (empty($portfolio_instruments)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No instruments with open positions found.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrument</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Price Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Last Close Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Fetched At</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($portfolio_instruments as $index => $item): 
                        $instrument = $item['instrument'];
                        $latest_price = $item['latest_price'];
                        ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= htmlspecialchars($instrument->ticker ?? '') ?> - 
                                <?= htmlspecialchars($instrument->name) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if ($latest_price !== null): ?>
                                    <?= date('d.m.Y', strtotime($latest_price->price_date)) ?>
                                <?php else: ?>
                                    <em class="text-gray-400">No price data</em>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                <?php if ($latest_price !== null): ?>
                                    <?= number_format((float)$latest_price->close_price, 2) ?>
                                <?php else: ?>
                                    <em class="text-gray-400">-</em>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if ($latest_price !== null): ?>
                                    <?= htmlspecialchars($latest_price->currency) ?>
                                <?php else: ?>
                                    <em class="text-gray-400">-</em>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php if ($latest_price !== null): ?>
                                    <?= date('H:i:s d.m.Y', strtotime($latest_price->fetched_at)) ?>
                                <?php else: ?>
                                    <em class="text-gray-400">-</em>
                                <?php endif; ?>
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

