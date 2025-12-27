<?php

$page_title = 'Dividend Payers';
ob_start();
?>

<h1>Dividend Payers</h1>

<p><a href="?action=payer_new">Add Payer</a></p>

<?php if (empty($payers)): ?>
    <p>No payers found.</p>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Country</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payers as $payer): ?>
                <tr>
                    <td><?= htmlspecialchars($payer->payer_name) ?></td>
                    <td><?= htmlspecialchars($payer->payer_address) ?></td>
                    <td><?= htmlspecialchars($payer->payer_country_code) ?></td>
                    <td><?= $payer->is_active ? 'Yes' : 'No' ?></td>
                    <td><a href="?action=payer_edit&id=<?= $payer->id ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

