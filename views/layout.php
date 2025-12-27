<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Portfolio Tracker') ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        .error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php
    require_once __DIR__ . '/../infrastructure/auth.php';
    $is_logged_in = current_user_id() !== null;
    ?>
    <?php if ($is_logged_in): ?>
        <nav>
            <a href="?action=dashboard">Dashboard</a> |
            <a href="?action=instruments">Instruments</a> |
            <a href="?action=trades">Trades</a> |
            <a href="?action=corporate_actions">Corporate Actions</a> |
            <a href="?action=payers">Payers</a> |
            <a href="?action=dividends">Dividends</a> |
            <a href="?action=logout">Logout</a>
        </nav>
        <hr>
    <?php endif; ?>
    <?= $content ?? '' ?>
</body>
</html>

