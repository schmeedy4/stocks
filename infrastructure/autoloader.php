<?php

declare(strict_types=1);

spl_autoload_register(function (string $class_name): void {
    $base_dir = __DIR__ . '/../';
    
    $directories = [
        '',
        'controllers/',
        'services/',
        'repositories/',
        'models/',
        'infrastructure/',
    ];

    foreach ($directories as $dir) {
        $file = $base_dir . $dir . $class_name . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

