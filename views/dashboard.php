<?php

$page_title = 'Dashboard';
ob_start();
?>

<h1>Dashboard</h1>
<p>Welcome, you are logged in.</p>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>

