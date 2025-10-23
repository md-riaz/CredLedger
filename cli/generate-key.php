#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CredLedger\Services\EncryptionService;

echo "Generating new encryption key...\n\n";
$key = EncryptionService::generateKey();
echo "ENCRYPTION_KEY={$key}\n\n";
echo "Add this to your .env file.\n";
