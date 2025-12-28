<?php

$page_title = 'Price Updates';
ob_start();
?>

<h1>Price Updates</h1>

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
    <div class="error" style="background-color: #efe; border-color: #cec; color: #060;">
        <h3>Update completed for <?= htmlspecialchars($price_date) ?></h3>
        <p><strong>Updated:</strong> <?= (int)$updated ?> | 
           <strong>Skipped:</strong> <?= (int)$skipped ?> | 
           <strong>Failed:</strong> <?= (int)$failed ?> | 
           <strong>Duration:</strong> <?= number_format($duration, 2) ?> seconds</p>
        <?php if (!empty($errors)): ?>
            <h4>Errors:</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}
?>

<form method="POST" action="?action=price_update" style="margin-bottom: 20px;">
    <p>
        <label>
            <input type="checkbox" name="force_update" value="1">
            Force update (ignore existing prices for today)
        </label>
    </p>
    <p>
        <button type="submit">Update prices (today)</button>
    </p>
</form>

<?php if (empty($portfolio_instruments)): ?>
    <p>No instruments with open positions found.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Instrument</th>
                <th>Last Price Date</th>
                <th>Last Close Price</th>
                <th>Currency</th>
                <th>Last Fetched At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($portfolio_instruments as $item): 
                $instrument = $item['instrument'];
                $latest_price = $item['latest_price'];
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($instrument->ticker ?? '') ?> - 
                        <?= htmlspecialchars($instrument->name) ?>
                    </td>
                    <td>
                        <?php if ($latest_price !== null): ?>
                            <?= htmlspecialchars($latest_price->price_date) ?>
                        <?php else: ?>
                            <em>No price data</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($latest_price !== null): ?>
                            <?= htmlspecialchars($latest_price->close_price) ?>
                        <?php else: ?>
                            <em>-</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($latest_price !== null): ?>
                            <?= htmlspecialchars($latest_price->currency) ?>
                        <?php else: ?>
                            <em>-</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($latest_price !== null): ?>
                            <?= htmlspecialchars($latest_price->fetched_at) ?>
                        <?php else: ?>
                            <em>-</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

