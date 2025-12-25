<?php

declare(strict_types=1);

/**
 * CLI script to seed admin user
 * Usage: php _database/seed_user.php
 */

require __DIR__ . '/../infrastructure/autoloader.php';

$email = 'eriksmi@gmail.com';
$password = 'eriksm';

$user_repo = new UserRepository();
$existing_user = $user_repo->find_by_email($email);

if ($existing_user !== null) {
    echo "User with email '{$email}' already exists.\n";
    exit(0);
}

$user_id = $user_repo->create_user($email, $password);
echo "User created successfully with ID: {$user_id}\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";

