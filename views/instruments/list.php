<?php

$page_title = 'Instruments';
ob_start();
?>

<h1>Instruments</h1>

<form method="GET" action="?action=instruments" style="margin-bottom: 20px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search by name, ticker, or ISIN" style="width: 300px; padding: 5px;">
    <button type="submit" style="padding: 5px 15px;">Search</button>
    <?php if (!empty($q)): ?>
        <a href="?action=instruments" style="margin-left: 10px;">Clear</a>
    <?php endif; ?>
</form>

<p><a href="?action=instrument_new">Add instrument</a></p>

<?php if (empty($instruments)): ?>
    <p>No instruments found.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>ISIN</th>
                <th>Ticker</th>
                <th>Name</th>
                <th>Type</th>
                <th>Country</th>
                <th>Currency</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($instruments as $instrument): ?>
                <tr>
                    <td><?= htmlspecialchars($instrument->isin ?? '') ?></td>
                    <td><?= htmlspecialchars($instrument->ticker ?? '') ?></td>
                    <td><?= htmlspecialchars($instrument->name) ?></td>
                    <td><?= htmlspecialchars($instrument->instrument_type) ?></td>
                    <td><?= htmlspecialchars($instrument->country_code ?? '') ?></td>
                    <td><?= htmlspecialchars($instrument->trading_currency ?? '') ?></td>
                    <td><a href="?action=instrument_edit&id=<?= $instrument->id ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>
