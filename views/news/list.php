<?php

$page_title = 'News';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">News</h1>
    <a href="?action=news_import" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
        Import News
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="?action=news" class="grid grid-cols-1 md:grid-cols-9 gap-4">
        <input type="hidden" name="action" value="news">
        
        <!-- Title Search -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input 
                type="text" 
                id="title" 
                name="title" 
                value="<?= htmlspecialchars($_GET['title'] ?? '') ?>"
                placeholder="Search title..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <!-- Author Search -->
        <div>
            <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Author</label>
            <input 
                type="text" 
                id="author" 
                name="author" 
                value="<?= htmlspecialchars($_GET['author'] ?? '') ?>"
                placeholder="Search author..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>
        
        <!-- Ticker -->
        <div>
            <label for="ticker" class="block text-sm font-medium text-gray-700 mb-1">Ticker</label>
            <input 
                type="text" 
                id="ticker" 
                name="ticker" 
                value="<?= htmlspecialchars($_GET['ticker'] ?? '') ?>"
                placeholder="e.g. MU"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>
        
        <!-- Show Only -->
        <div>
            <label for="show_only" class="block text-sm font-medium text-gray-700 mb-1">Show Only</label>
            <select 
                id="show_only" 
                name="show_only"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="all" <?= ($_GET['show_only'] ?? 'all') === 'all' ? 'selected' : '' ?>>All</option>
                <option value="holdings" <?= ($_GET['show_only'] ?? '') === 'holdings' ? 'selected' : '' ?>>Holdings</option>
                <option value="watchlist" <?= ($_GET['show_only'] ?? '') === 'watchlist' ? 'selected' : '' ?>>Watchlist</option>
            </select>
        </div>

        <!-- Read Status -->
        <div>
            <label for="read_status" class="block text-sm font-medium text-gray-700 mb-1">Read</label>
            <select 
                id="read_status" 
                name="read_status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="all" <?= ($_GET['read_status'] ?? 'all') === 'all' ? 'selected' : '' ?>>All</option>
                <option value="read" <?= ($_GET['read_status'] ?? '') === 'read' ? 'selected' : '' ?>>Read</option>
                <option value="unread" <?= ($_GET['read_status'] ?? '') === 'unread' ? 'selected' : '' ?>>Unread</option>
            </select>
        </div>

        <!-- Sentiment -->
        <div>
            <label for="sentiment" class="block text-sm font-medium text-gray-700 mb-1">Sentiment</label>
            <select 
                id="sentiment" 
                name="sentiment"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">All</option>
                <option value="bullish" <?= ($_GET['sentiment'] ?? '') === 'bullish' ? 'selected' : '' ?>>Bullish</option>
                <option value="bearish" <?= ($_GET['sentiment'] ?? '') === 'bearish' ? 'selected' : '' ?>>Bearish</option>
                <option value="neutral" <?= ($_GET['sentiment'] ?? '') === 'neutral' ? 'selected' : '' ?>>Neutral</option>
                <option value="mixed" <?= ($_GET['sentiment'] ?? '') === 'mixed' ? 'selected' : '' ?>>Mixed</option>
            </select>
        </div>

        <!-- Min Confidence -->
        <div>
            <label for="min_confidence" class="block text-sm font-medium text-gray-700 mb-1">Min Confidence</label>
            <input 
                type="number" 
                id="min_confidence" 
                name="min_confidence" 
                min="0" 
                max="100"
                value="<?= htmlspecialchars($_GET['min_confidence'] ?? '') ?>"
                placeholder="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <!-- Min Read Grade -->
        <div>
            <label for="min_read_grade" class="block text-sm font-medium text-gray-700 mb-1">Min Read Grade</label>
            <select 
                id="min_read_grade" 
                name="min_read_grade"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">All</option>
                <option value="1" <?= ($_GET['min_read_grade'] ?? '') === '1' ? 'selected' : '' ?>>1+</option>
                <option value="2" <?= ($_GET['min_read_grade'] ?? '') === '2' ? 'selected' : '' ?>>2+</option>
                <option value="3" <?= ($_GET['min_read_grade'] ?? '') === '3' ? 'selected' : '' ?>>3+</option>
                <option value="4" <?= ($_GET['min_read_grade'] ?? '') === '4' ? 'selected' : '' ?>>4+</option>
                <option value="5" <?= ($_GET['min_read_grade'] ?? '') === '5' ? 'selected' : '' ?>>5</option>
            </select>
        </div>

        <!-- Sort -->
        <div>
            <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort</label>
            <select 
                id="sort" 
                name="sort"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="captured_desc" <?= ($_GET['sort'] ?? 'captured_desc') === 'captured_desc' ? 'selected' : '' ?>>Captured (Newest)</option>
                <option value="published_desc" <?= ($_GET['sort'] ?? '') === 'published_desc' ? 'selected' : '' ?>>Published (Newest)</option>
                <option value="confidence_desc" <?= ($_GET['sort'] ?? '') === 'confidence_desc' ? 'selected' : '' ?>>Confidence (High)</option>
                <option value="read_grade_desc" <?= ($_GET['sort'] ?? '') === 'read_grade_desc' ? 'selected' : '' ?>>Read Grade (High)</option>
            </select>
        </div>

        <div class="md:col-span-8 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Apply Filters
            </button>
            <a href="?action=news" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Clear
            </a>
        </div>
    </form>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (empty($articles)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No news articles found.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Read</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickers</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sentiment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confidence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Read Grade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Captured</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($articles as $index => $article): ?>
                        <tr 
                            id="article-<?= $article->id ?>"
                            class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 <?= (isset($_GET['highlight']) && (int)$_GET['highlight'] === $article->id) ? 'bg-yellow-100' : '' ?> <?= (isset($article->is_read) && $article->is_read) ? 'opacity-75' : '' ?>"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button 
                                    onclick="toggleRead(<?= $article->id ?>)"
                                    class="p-2 rounded-md hover:bg-gray-200 transition-colors"
                                    title="<?= (isset($article->is_read) && $article->is_read) ? 'Mark as unread' : 'Mark as read' ?>"
                                >
                                    <?php if (isset($article->is_read) && $article->is_read): ?>
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    <?php endif; ?>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <a 
                                    href="<?= htmlspecialchars($article->url) ?>" 
                                    target="_blank" 
                                    class="text-blue-600 hover:text-blue-900 truncate max-w-[320px] block"
                                    title="<?= htmlspecialchars($article->title) ?>"
                                >
                                    <?= htmlspecialchars($article->title) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (!empty($article->tickers)): ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($article->tickers as $ticker): ?>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if ($article->author_name): ?>
                                    <?php if ($article->author_url): ?>
                                        <a href="<?= htmlspecialchars($article->author_url) ?>" target="_blank" class="text-blue-600 hover:text-blue-900">
                                            <?= htmlspecialchars($article->author_name) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($article->author_name) ?>
                                    <?php endif; ?>
                                    <?php if ($article->author_followers !== null): ?>
                                        <span class="text-gray-500">(<?= number_format($article->author_followers) ?>)</span>
                                    <?php endif; ?>
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
                                $color = $sentiment_colors[$article->sentiment] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $color ?>">
                                    <?= htmlspecialchars(ucfirst($article->sentiment)) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <?= $article->confidence ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= $article->read_grade ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= date('d.m.Y H:i', strtotime($article->captured_at)) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $article->published_at ? date('d.m.Y H:i', strtotime($article->published_at)) : '—' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button 
                                    onclick="showArticleModal(<?= $article->id ?>)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-700">
                Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $total) ?> of <?= $total ?> articles
            </div>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a 
                        href="?action=news&<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                        class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-sm"
                    >
                        Previous
                    </a>
                <?php endif; ?>
                
                <?php
                // Smart pagination logic
                $current_page = $page;
                $total_pages_num = $total_pages;
                $pages_to_show = [];
                
                // Always show first page
                if ($current_page > 3) {
                    $pages_to_show[] = 1;
                    if ($current_page > 4) {
                        $pages_to_show[] = 'ellipsis_start';
                    }
                }
                
                // Show pages around current page
                $start = max(1, $current_page - 2);
                $end = min($total_pages_num, $current_page + 2);
                
                for ($i = $start; $i <= $end; $i++) {
                    $pages_to_show[] = $i;
                }
                
                // Always show last page
                if ($current_page < $total_pages_num - 2) {
                    if ($current_page < $total_pages_num - 3) {
                        $pages_to_show[] = 'ellipsis_end';
                    }
                    $pages_to_show[] = $total_pages_num;
                }
                
                // Render page numbers
                foreach ($pages_to_show as $page_num):
                    if ($page_num === 'ellipsis_start' || $page_num === 'ellipsis_end'):
                ?>
                    <span class="px-3 py-2 text-gray-500">...</span>
                <?php else: ?>
                    <?php if ($page_num == $current_page): ?>
                        <span class="px-3 py-2 bg-blue-600 text-white rounded-md text-sm font-medium">
                            <?= $page_num ?>
                        </span>
                    <?php else: ?>
                        <a 
                            href="?action=news&<?= http_build_query(array_merge($_GET, ['page' => $page_num])) ?>"
                            class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-sm"
                        >
                            <?= $page_num ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a 
                        href="?action=news&<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                        class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-sm"
                    >
                        Next
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal -->
<div id="articleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900" id="modalTitle">Article Details</h3>
            <button onclick="closeArticleModal()" class="text-gray-400 hover:text-gray-600">
                <span class="text-2xl">&times;</span>
            </button>
        </div>
        <div id="modalContent" class="max-h-[70vh] overflow-y-auto">
            <p class="text-gray-600">Loading...</p>
        </div>
    </div>
</div>

<script>
function showArticleModal(id) {
    const modal = document.getElementById('articleModal');
    const modalContent = document.getElementById('modalContent');
    const modalTitle = document.getElementById('modalTitle');
    
    modal.classList.remove('hidden');
    modalContent.innerHTML = '<p class="text-gray-600">Loading...</p>';
    
    fetch('?action=news_get_json&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                modalContent.innerHTML = '<p class="text-red-600">Error: ' + data.error + '</p>';
                return;
            }
            
            modalTitle.textContent = data.title;
            
            let html = '<div class="space-y-6">';
            
            // Recap
            html += '<div><h4 class="font-semibold text-gray-900 mb-2">Recap</h4><p class="text-gray-700 whitespace-pre-wrap">' + escapeHtml(data.recap) + '</p></div>';
            
            // Tags
            if (data.tags && data.tags.length > 0) {
                html += '<div><h4 class="font-semibold text-gray-900 mb-2">Tags</h4><div class="flex flex-wrap gap-2">';
                data.tags.forEach(tag => {
                    html += '<span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm">' + escapeHtml(tag) + '</span>';
                });
                html += '</div></div>';
            }
            
            // Drivers
            if (data.drivers && data.drivers.length > 0) {
                html += '<div><h4 class="font-semibold text-gray-900 mb-2">Drivers</h4><ul class="space-y-3">';
                data.drivers.forEach(driver => {
                    html += '<li class="border-l-4 border-blue-500 pl-4">';
                    html += '<div class="flex items-center gap-2 mb-1">';
                    html += '<span class="font-semibold text-gray-900">' + escapeHtml(driver.title || 'Untitled') + '</span>';
                    if (driver.direction) {
                        const dirColor = driver.direction === 'bullish' ? 'text-green-600' : driver.direction === 'bearish' ? 'text-red-600' : 'text-gray-600';
                        html += '<span class="text-xs ' + dirColor + '">(' + escapeHtml(driver.direction) + ')</span>';
                    }
                    if (driver.impact_score !== undefined) {
                        html += '<span class="text-xs text-gray-500">Impact: ' + driver.impact_score + '</span>';
                    }
                    html += '</div>';
                    if (driver.detail) {
                        html += '<p class="text-sm text-gray-700">' + escapeHtml(driver.detail) + '</p>';
                    }
                    if (driver.evidence && driver.evidence.length > 0) {
                        html += '<ul class="mt-1 text-xs text-gray-600 list-disc list-inside">';
                        driver.evidence.forEach(ev => {
                            html += '<li>' + escapeHtml(ev) + '</li>';
                        });
                        html += '</ul>';
                    }
                    html += '</li>';
                });
                html += '</ul></div>';
            }
            
            // Key Dates
            if (data.key_dates && data.key_dates.length > 0) {
                html += '<div><h4 class="font-semibold text-gray-900 mb-2">Key Dates</h4><ul class="space-y-3">';
                data.key_dates.forEach(kd => {
                    html += '<li class="border-l-4 border-purple-500 pl-4">';
                    html += '<div class="font-semibold text-gray-900">' + escapeHtml(kd.date || '') + '</div>';
                    if (kd.type) html += '<div class="text-sm text-gray-600">Type: ' + escapeHtml(kd.type) + '</div>';
                    if (kd.horizon) html += '<div class="text-sm text-gray-600">Horizon: ' + escapeHtml(kd.horizon) + '</div>';
                    if (kd.description) html += '<div class="text-sm text-gray-700 mt-1">' + escapeHtml(kd.description) + '</div>';
                    if (kd.tickers && kd.tickers.length > 0) {
                        html += '<div class="text-xs text-gray-500 mt-1">Tickers: ' + escapeHtml(kd.tickers.join(', ')) + '</div>';
                    }
                    html += '</li>';
                });
                html += '</ul></div>';
            }
            
            // Raw JSON (collapsed)
            html += '<div><h4 class="font-semibold text-gray-900 mb-2 cursor-pointer" onclick="toggleRawJson()">Raw JSON <span id="rawJsonToggle">▼</span></h4>';
            html += '<pre id="rawJsonContent" class="hidden bg-gray-100 p-4 rounded text-xs overflow-x-auto"><code>' + escapeHtml(JSON.stringify(data.raw_json, null, 2)) + '</code></pre></div>';
            
            html += '</div>';
            modalContent.innerHTML = html;
        })
        .catch(error => {
            modalContent.innerHTML = '<p class="text-red-600">Error loading article: ' + error.message + '</p>';
        });
}

function closeArticleModal() {
    document.getElementById('articleModal').classList.add('hidden');
}

function toggleRawJson() {
    const content = document.getElementById('rawJsonContent');
    const toggle = document.getElementById('rawJsonToggle');
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        toggle.textContent = '▲';
    } else {
        content.classList.add('hidden');
        toggle.textContent = '▼';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleRead(articleId) {
    const formData = new FormData();
    formData.append('article_id', articleId);
    
    fetch('?action=news_toggle_read', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        // Update the UI
        const row = document.getElementById('article-' + articleId);
        if (!row) return;
        
        const button = row.querySelector('td:first-child button');
        if (!button) return;
        
        if (data.is_read) {
            // Mark as read
            button.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
            button.title = 'Mark as unread';
            row.classList.add('opacity-75');
        } else {
            // Mark as unread
            button.innerHTML = '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            button.title = 'Mark as read';
            row.classList.remove('opacity-75');
        }
    })
    .catch(error => {
        alert('Error toggling read status: ' + error.message);
    });
}

// Close modal on outside click
document.getElementById('articleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeArticleModal();
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

