#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/bootstrap.php';

use CredLedger\Database\Migration;

echo "Running database migrations...\n\n";

try {
    $migration = new Migration($pdo, __DIR__ . '/../migrations');
    $migration->run();
    echo "\n✓ Migrations completed successfully!\n";
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
