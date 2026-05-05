<?php

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException('.env file not found');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Try to load .env file
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Configuration
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Database connection
$db = new \CredLedger\Database\Database(env('DB_PATH', 'data/credledger.db'));
$pdo = $db->getConnection();

// Services
$encryptionService = new \CredLedger\Services\EncryptionService(
    env('ENCRYPTION_KEY', str_repeat('0', 64))
);
$totpService = new \CredLedger\Services\TotpService();
$auditLogService = new \CredLedger\Services\AuditLogService($pdo);

// Models
$userModel = new \CredLedger\Models\User($pdo);
$secretModel = new \CredLedger\Models\Secret($pdo, $encryptionService);
$accessRequestModel = new \CredLedger\Models\AccessRequest($pdo);
$grantModel = new \CredLedger\Models\Grant($pdo);

// Auth service
$authService = new \CredLedger\Services\AuthService($userModel, $totpService);

// Return services for use in scripts
return [
    'db' => $pdo,
    'encryptionService' => $encryptionService,
    'totpService' => $totpService,
    'auditLogService' => $auditLogService,
    'userModel' => $userModel,
    'secretModel' => $secretModel,
    'accessRequestModel' => $accessRequestModel,
    'grantModel' => $grantModel,
    'authService' => $authService
];
