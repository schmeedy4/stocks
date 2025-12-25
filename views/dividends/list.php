<?php

$page_title = 'Dividends';
ob_start();
?>

<h1>Dividends</h1>
<p>Dividends list (placeholder)</p>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

