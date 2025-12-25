<?php
/** @var string $title */
/** @var string $content */
/** @var array<int, array{type:string,message:string}> $flashes */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
<?php foreach ($flashes as $flash): ?>
    <p><?php echo htmlspecialchars($flash['type'] . ': ' . $flash['message'], ENT_QUOTES, 'UTF-8'); ?></p>
<?php endforeach; ?>
<?php echo $content; ?>
</body>
</html>
