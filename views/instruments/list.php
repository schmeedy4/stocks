<?php

$page_title = 'Instruments';
ob_start();
?>

<h1>Instruments</h1>
<p>Instruments list (placeholder)</p>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

