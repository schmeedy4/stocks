<?php

$page_title = 'Login';
ob_start();
?>

<h1>Login</h1>

<?php if (isset($error)): ?>
    <div class="error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="?action=login_post">
    <div style="margin-bottom: 15px;">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required style="width: 300px; padding: 5px;">
    </div>
    <div style="margin-bottom: 15px;">
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required style="width: 300px; padding: 5px;">
    </div>
    <button type="submit" style="padding: 8px 16px;">Login</button>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

