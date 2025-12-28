<?php

$page_title = 'New SELL Trade';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">New SELL Trade</h1>
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
    <form method="POST" action="?action=trade_create_sell" class="space-y-4">
        <div>
            <label for="instrument_id" class="block text-sm font-medium text-gray-700 mb-1">Instrument <span class="text-red-500">*</span>:</label>
            <select id="instrument_id" name="instrument_id" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Instrument --</option>
                <?php foreach ($instruments as $instrument): ?>
                    <?php $display = $instrument->ticker ? $instrument->ticker . ' - ' . $instrument->name : $instrument->name; ?>
                    <option value="<?= $instrument->id ?>" <?= (isset($trade->instrument_id) && $trade->instrument_id == $instrument->id) ? 'selected' : '' ?>>
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
                    <option value="<?= $broker['id'] ?>" <?= (isset($trade->broker_account_id) && $trade->broker_account_id == $broker['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($broker['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="trade_date" class="block text-sm font-medium text-gray-700 mb-1">Trade Date <span class="text-red-500">*</span>:</label>
            <input type="date" id="trade_date" name="trade_date" value="<?= htmlspecialchars($trade->trade_date ?? '') ?>" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['trade_date'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['trade_date']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="flex items-center">
                <input type="checkbox" id="show_closed_positions" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Show closed positions</span>
            </label>
        </div>

        <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span>:</label>
            <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($trade->quantity ?? '') ?>" step="0.000001" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <div id="available_qty_display" class="mt-1 text-sm text-gray-600"></div>
            <?php if (isset($errors['quantity'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['quantity']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="price_per_unit" class="block text-sm font-medium text-gray-700 mb-1">Price per Unit <span class="text-red-500">*</span>:</label>
            <input type="number" id="price_per_unit" name="price_per_unit" value="<?= htmlspecialchars($trade->price_per_unit ?? '') ?>" step="any" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['price_per_unit'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['price_per_unit']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="trade_currency" class="block text-sm font-medium text-gray-700 mb-1">Trade Currency <span class="text-red-500">*</span>:</label>
            <select id="trade_currency" name="trade_currency" required class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="USD" <?= (isset($trade->trade_currency) && $trade->trade_currency === 'USD') || (!isset($trade->trade_currency)) ? 'selected' : '' ?>>USD</option>
                <option value="EUR" <?= (isset($trade->trade_currency) && $trade->trade_currency === 'EUR') ? 'selected' : '' ?>>EUR</option>
            </select>
            <?php if (isset($errors['trade_currency'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['trade_currency']) ?></p>
            <?php endif; ?>
        </div>

        <?php
        $trade_currency = strtoupper(trim($trade->trade_currency ?? 'USD'));
        $is_usd = ($trade_currency === 'USD');
        // Calculate broker_fx_rate from stored fx_rate_to_eur for display (if available)
        // Round to 4 decimals for clean display
        $broker_fx_rate = '';
        if (isset($trade->fx_rate_to_eur) && $trade->fx_rate_to_eur !== '' && $trade->fx_rate_to_eur !== '0') {
            $broker_fx_rate = number_format(1 / (float)$trade->fx_rate_to_eur, 4, '.', '');
        }
        if (isset($trade->broker_fx_rate)) {
            // If already provided (e.g., from old_input), use as-is but ensure it's reasonable
            $broker_fx_rate = $trade->broker_fx_rate;
        }
        ?>
        <div id="broker_fx_rate_container" style="display: <?= $is_usd ? 'block' : 'none' ?>;">
            <label for="broker_fx_rate" class="block text-sm font-medium text-gray-700 mb-1">FX Rate (EUR → USD) <span class="text-red-500">*</span>:</label>
            <input type="number" id="broker_fx_rate" name="broker_fx_rate" value="<?= htmlspecialchars($broker_fx_rate) ?>" step="any" <?= $is_usd ? 'required' : '' ?> class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['broker_fx_rate'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['broker_fx_rate']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="fee_eur" class="block text-sm font-medium text-gray-700 mb-1">Fee EUR:</label>
            <input type="number" id="fee_eur" name="fee_eur" value="<?= htmlspecialchars($trade->fee_eur ?? '') ?>" step="0.01" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <?php if (isset($errors['fee_eur'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['fee_eur']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes:</label>
            <textarea id="notes" name="notes" class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" rows="4"><?= htmlspecialchars($trade->notes ?? '') ?></textarea>
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
</div>

<script>
(function() {
    'use strict';

    // Elements
    const brokerSelect = document.getElementById('broker_account_id');
    const tradeDateInput = document.getElementById('trade_date');
    const showClosedCheckbox = document.getElementById('show_closed_positions');
    const instrumentSelect = document.getElementById('instrument_id');
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('price_per_unit');
    const availableQtyDisplay = document.getElementById('available_qty_display');
    const tradeCurrencySelect = document.getElementById('trade_currency');
    const brokerFxRateContainer = document.getElementById('broker_fx_rate_container');
    const brokerFxRateInput = document.getElementById('broker_fx_rate');

    let currentAvailableQty = '0';

    // Format quantity to 6 decimals
    function format_qty(qty_str) {
        const num = parseFloat(qty_str);
        if (isNaN(num)) return '0.000000';
        return num.toFixed(6);
    }

    // Fetch instruments list and rebuild dropdown
    async function update_instruments_list() {
        const brokerId = brokerSelect.value || null;
        const tradeDate = tradeDateInput.value || '';
        const includeZero = showClosedCheckbox.checked;

        let url = '?action=trades_sell_instruments';
        const params = new URLSearchParams();
        if (brokerId) params.append('broker_account_id', brokerId);
        if (tradeDate) params.append('trade_date', tradeDate);
        if (includeZero) params.append('include_zero', '1');
        if (params.toString()) url += '&' + params.toString();

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to fetch instruments');
            const instruments = await response.json();

            // Save current selection
            const currentInstrumentId = instrumentSelect.value;

            // Clear and rebuild dropdown
            instrumentSelect.innerHTML = '<option value="">-- Select Instrument --</option>';
            instruments.forEach(function(instr) {
                const option = document.createElement('option');
                option.value = instr.instrument_id;
                option.textContent = instr.label;
                instrumentSelect.appendChild(option);
            });

            // Try to restore selection, or select first available
            if (currentInstrumentId) {
                const option = instrumentSelect.querySelector('option[value="' + currentInstrumentId + '"]');
                if (option) {
                    instrumentSelect.value = currentInstrumentId;
                } else if (instruments.length > 0) {
                    // Current selection not available, select first
                    instrumentSelect.value = instruments[0].instrument_id.toString();
                } else {
                    instrumentSelect.value = '';
                }
            } else if (instruments.length > 0) {
                instrumentSelect.value = instruments[0].instrument_id.toString();
            }

            // Update availability for selected instrument
            update_available_quantity();
        } catch (error) {
            console.error('Error fetching instruments:', error);
            // Fallback: keep existing dropdown, server validation will handle errors
        }
    }

    // Fetch available quantity for selected instrument
    async function update_available_quantity() {
        const instrumentId = instrumentSelect.value;
        if (!instrumentId) {
            currentAvailableQty = '0';
            update_quantity_input_state('0');
            return;
        }

        const brokerId = brokerSelect.value || null;
        const tradeDate = tradeDateInput.value || '';

        let url = '?action=trades_sell_available';
        const params = new URLSearchParams();
        params.append('instrument_id', instrumentId);
        if (brokerId) params.append('broker_account_id', brokerId);
        if (tradeDate) params.append('trade_date', tradeDate);
        url += '&' + params.toString();

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to fetch available quantity');
            const data = await response.json();
            currentAvailableQty = format_qty(data.available_qty);
            update_quantity_input_state(currentAvailableQty);
        } catch (error) {
            console.error('Error fetching available quantity:', error);
            // Fallback: keep current state, server validation will handle errors
        }
    }

    // Update quantity input max, clamp if needed, update display
    function update_quantity_input_state(available_qty_str) {
        const availableQty = parseFloat(available_qty_str);
        const isAvailable = !isNaN(availableQty) && availableQty > 0;

        if (isAvailable) {
            // Set max attribute (use step precision: 0.000001)
            quantityInput.max = format_qty(available_qty_str);
            quantityInput.disabled = false;
            priceInput.disabled = false;

            // Update display
            availableQtyDisplay.textContent = 'Available: ' + format_qty(available_qty_str);
            availableQtyDisplay.className = 'mt-1 text-sm text-gray-600';

            // Clamp current value if it exceeds available
            const currentValue = parseFloat(quantityInput.value);
            if (!isNaN(currentValue) && currentValue > availableQty) {
                quantityInput.value = format_qty(available_qty_str);
            }
        } else {
            // No shares available
            quantityInput.max = '';
            quantityInput.value = '';
            quantityInput.disabled = true;
            priceInput.disabled = true;
            availableQtyDisplay.textContent = 'No shares available';
            availableQtyDisplay.className = 'mt-1 text-sm text-red-600';
        }
    }

    // Event listeners
    brokerSelect.addEventListener('change', function() {
        update_instruments_list();
    });

    tradeDateInput.addEventListener('change', function() {
        update_instruments_list();
    });

    showClosedCheckbox.addEventListener('change', function() {
        update_instruments_list();
    });

    instrumentSelect.addEventListener('change', function() {
        update_available_quantity();
    });

    quantityInput.addEventListener('input', function() {
        const value = parseFloat(quantityInput.value);
        const max = parseFloat(quantityInput.max);
        if (!isNaN(value) && !isNaN(max) && value > max) {
            quantityInput.value = format_qty(max.toString());
        }
    });

    // Toggle FX rate field based on currency selection
    function toggleFxRateField() {
        if (brokerFxRateContainer && brokerFxRateInput) {
            if (tradeCurrencySelect.value === 'USD') {
                brokerFxRateContainer.style.display = 'block';
                brokerFxRateInput.setAttribute('required', 'required');
            } else {
                brokerFxRateContainer.style.display = 'none';
                brokerFxRateInput.removeAttribute('required');
            }
        }
    }
    
    if (tradeCurrencySelect) {
        tradeCurrencySelect.addEventListener('change', toggleFxRateField);
        // Initialize on page load
        toggleFxRateField();
    }

    // Initialize: set default trade_date to today if empty
    if (!tradeDateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        tradeDateInput.value = today;
    }

    // Initial load
    update_instruments_list();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

