<?php

$page_title = htmlspecialchars($selected_watchlist->name);
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($selected_watchlist->name) ?></h1>
        <p class="text-sm text-gray-600 mt-1"><?= count($instruments) ?> <?= count($instruments) === 1 ? 'instrument' : 'instruments' ?></p>
    </div>
    <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
        Add to Watchlist
    </button>
</div>

<!-- Add Instrument Modal -->
<div id="addInstrumentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add Instruments to Watchlist</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            
            <div class="mb-4">
                <input 
                    type="text" 
                    id="instrumentSearch" 
                    placeholder="Search instruments by name, ticker, or ISIN..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    onkeyup="searchInstruments()"
                >
            </div>
            
            <div id="instrumentResults" class="max-h-96 overflow-y-auto border border-gray-200 rounded-md">
                <div class="p-4 text-center text-gray-500">Type to search for instruments...</div>
            </div>
        </div>
    </div>
</div>

<script>
const watchlistId = <?= $selected_watchlist->id ?>;
let searchTimeout = null;

function openAddModal() {
    document.getElementById('addInstrumentModal').classList.remove('hidden');
    document.getElementById('instrumentSearch').focus();
    // Load initial results
    searchInstruments();
}

function closeAddModal() {
    document.getElementById('addInstrumentModal').classList.add('hidden');
    document.getElementById('instrumentSearch').value = '';
    document.getElementById('instrumentResults').innerHTML = '<div class="p-4 text-center text-gray-500">Type to search for instruments...</div>';
}

function searchInstruments() {
    const query = document.getElementById('instrumentSearch').value.trim();
    
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // Debounce search
    searchTimeout = setTimeout(() => {
        const url = '?action=watchlist_search_instruments&watchlist_id=' + watchlistId + '&q=' + encodeURIComponent(query);
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('instrumentResults').innerHTML = '<div class="p-4 text-center text-red-500">Error: ' + data.error + '</div>';
                    return;
                }
                
                displayInstruments(data.instruments);
            })
            .catch(error => {
                document.getElementById('instrumentResults').innerHTML = '<div class="p-4 text-center text-red-500">Error loading instruments</div>';
            });
    }, 300);
}

function displayInstruments(instruments) {
    const resultsDiv = document.getElementById('instrumentResults');
    
    if (instruments.length === 0) {
        resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-500">No instruments found</div>';
        return;
    }
    
    let html = '<table class="min-w-full divide-y divide-gray-200">';
    html += '<thead class="bg-gray-50"><tr>';
    html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticker</th>';
    html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>';
    html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>';
    html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
    
    instruments.forEach(instrument => {
        const statusClass = instrument.is_in_watchlist ? 'text-green-600' : 'text-gray-600';
        const statusText = instrument.is_in_watchlist ? '✓ In watchlist' : 'Add';
        const buttonDisabled = instrument.is_in_watchlist ? 'disabled' : '';
        const buttonClass = instrument.is_in_watchlist 
            ? 'px-3 py-1 text-sm bg-gray-200 text-gray-500 rounded cursor-not-allowed'
            : 'px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700';
        
        html += '<tr class="hover:bg-gray-50">';
        html += '<td class="px-4 py-3 text-sm text-gray-900">' + (instrument.ticker || '') + '</td>';
        html += '<td class="px-4 py-3 text-sm text-gray-900">' + escapeHtml(instrument.name) + '</td>';
        html += '<td class="px-4 py-3 text-sm text-gray-900">' + escapeHtml(instrument.instrument_type) + '</td>';
        html += '<td class="px-4 py-3 text-sm">';
        html += '<button onclick="addInstrument(' + instrument.id + ')" ' + buttonDisabled + ' class="' + buttonClass + '">' + statusText + '</button>';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    resultsDiv.innerHTML = html;
}

function addInstrument(instrumentId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '?action=watchlist_add_instrument';
    
    const watchlistIdInput = document.createElement('input');
    watchlistIdInput.type = 'hidden';
    watchlistIdInput.name = 'watchlist_id';
    watchlistIdInput.value = watchlistId;
    form.appendChild(watchlistIdInput);
    
    const instrumentIdInput = document.createElement('input');
    instrumentIdInput.type = 'hidden';
    instrumentIdInput.name = 'instrument_id';
    instrumentIdInput.value = instrumentId;
    form.appendChild(instrumentIdInput);
    
    document.body.appendChild(form);
    form.submit();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on outside click
document.getElementById('addInstrumentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddModal();
    }
});
</script>

<?php if (empty($instruments)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-600">No instruments in this watchlist. Add instruments from the Instruments page by clicking the star icon.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISIN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticker</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($instruments as $index => $instrument): ?>
                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <form method="POST" action="?action=watchlist_remove_instrument" class="inline">
                                    <input type="hidden" name="watchlist_id" value="<?= $selected_watchlist->id ?>">
                                    <input type="hidden" name="instrument_id" value="<?= $instrument->id ?>">
                                    <button type="submit" class="text-yellow-500 hover:text-yellow-700" title="Remove from watchlist">★</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->isin ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->ticker ?? '') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($instrument->name) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->instrument_type) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->country_code ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($instrument->trading_currency ?? '') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?action=instrument_edit&id=<?= $instrument->id ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
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
