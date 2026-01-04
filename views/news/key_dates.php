<?php

$page_title = 'Key Dates';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">Key Dates</h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="?action=key_dates" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="action" value="key_dates">
        
        <!-- Date Range -->
        <div>
            <label for="range" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
            <select 
                id="range" 
                name="range"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="upcoming" <?= ($_GET['range'] ?? 'upcoming') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                <option value="past_30" <?= ($_GET['range'] ?? '') === 'past_30' ? 'selected' : '' ?>>Last 30 days</option>
                <option value="next_30" <?= ($_GET['range'] ?? '') === 'next_30' ? 'selected' : '' ?>>Next 30 days</option>
                <option value="next_90" <?= ($_GET['range'] ?? '') === 'next_90' ? 'selected' : '' ?>>Next 90 days</option>
                <option value="all" <?= ($_GET['range'] ?? '') === 'all' ? 'selected' : '' ?>>All</option>
            </select>
        </div>

        <!-- Type Filter -->
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select 
                id="type" 
                name="type"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">All</option>
                <?php foreach ($unique_types as $type_option): ?>
                    <option value="<?= htmlspecialchars($type_option) ?>" <?= (isset($_GET['type']) && $_GET['type'] === $type_option) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type_option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Ticker Search -->
        <div>
            <label for="ticker" class="block text-sm font-medium text-gray-700 mb-1">Ticker</label>
            <input 
                type="text" 
                id="ticker" 
                name="ticker" 
                value="<?= htmlspecialchars($_GET['ticker'] ?? '') ?>"
                placeholder="e.g. MRK"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Apply Filters
            </button>
            <a href="?action=key_dates" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Clear
            </a>
        </div>
    </form>
</div>

<?php if (empty($key_dates)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No key dates found.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horizon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickers</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sentiment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($key_dates as $index => $kd): ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $kd['date'] ? date('d.m.Y', strtotime($kd['date'])) : '—' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($kd['type'] ?? '') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($kd['horizon'] ?? '') ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($kd['tickers_array'])): ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($kd['tickers_array'] as $ticker): ?>
                                            <?php 
                                            $ticker_upper = strtoupper(trim($ticker));
                                            $is_holding = isset($holdings_tickers_lookup[$ticker_upper]);
                                            $is_watchlist = isset($watchlist_tickers_lookup[$ticker_upper]);
                                            
                                            // Priority: holdings (green) > watchlist (blue) > default (gray)
                                            if ($is_holding) {
                                                $ticker_class = 'px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium';
                                            } elseif ($is_watchlist) {
                                                $ticker_class = 'px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium';
                                            } else {
                                                $ticker_class = 'px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium';
                                            }
                                            ?>
                                            <span class="<?= $ticker_class ?>">
                                                <?= htmlspecialchars($ticker) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $sentiment_colors = [
                                    'bullish' => 'bg-green-100 text-green-800',
                                    'bearish' => 'bg-red-100 text-red-800',
                                    'neutral' => 'bg-gray-100 text-gray-800',
                                    'mixed' => 'bg-amber-100 text-amber-800',
                                ];
                                $color = $sentiment_colors[$kd['sentiment'] ?? 'neutral'] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $color ?>">
                                    <?= htmlspecialchars(ucfirst($kd['sentiment'] ?? 'neutral')) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= htmlspecialchars($kd['description'] ?? '') ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($kd['article_url']): ?>
                                    <a 
                                        href="<?= htmlspecialchars($kd['article_url']) ?>" 
                                        target="_blank"
                                        class="text-blue-600 hover:text-blue-900 truncate max-w-[200px] block"
                                        title="<?= htmlspecialchars($kd['article_title']) ?>"
                                    >
                                        <?= htmlspecialchars($kd['article_title']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $kd['article_date'] ? date('d.m.Y', strtotime($kd['article_date'])) : '—' ?>
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

