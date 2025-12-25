<?php

declare(strict_types=1);

namespace App\Infrastructure;

final class Autoloader
{
    public static function register(string $basePath): void
    {
        spl_autoload_register(static function (string $class) use ($basePath): void {
            $prefix = 'App\\';
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
                $file = $basePath . DIRECTORY_SEPARATOR . strtolower($relativePath);
                if (!file_exists($file)) {
                    // Fallback to original casing
                    $file = $basePath . DIRECTORY_SEPARATOR . $relativePath;
                }
                if (file_exists($file)) {
                    require $file;
                }
            }
        });
    }
}
