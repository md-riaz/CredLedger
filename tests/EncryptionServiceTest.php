<?php

namespace CredLedger\Tests;

use PHPUnit\Framework\TestCase;
use CredLedger\Services\EncryptionService;

class EncryptionServiceTest extends TestCase
{
    private EncryptionService $service;

    protected function setUp(): void
    {
        $this->service = new EncryptionService(EncryptionService::generateKey());
    }

    public function testEncryptionAndDecryption(): void
    {
        $plaintext = 'secret_password_123';
        
        $encrypted = $this->service->encrypt($plaintext);
        
        $this->assertIsArray($encrypted);
        $this->assertArrayHasKey('encrypted_value', $encrypted);
        $this->assertArrayHasKey('nonce', $encrypted);
        
        $decrypted = $this->service->decrypt(
            $encrypted['encrypted_value'],
            $encrypted['nonce']
        );
        
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testGenerateKeyReturnsHexString(): void
    {
        $key = EncryptionService::generateKey();
        
        $this->assertEquals(64, strlen($key));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $key);
    }

    public function testInvalidKeyThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        new EncryptionService('invalid_key');
    }

    public function testDecryptWithWrongNonceFails(): void
    {
        $plaintext = 'secret';
        $encrypted = $this->service->encrypt($plaintext);
        
        $this->expectException(\RuntimeException::class);
        $this->service->decrypt($encrypted['encrypted_value'], base64_encode(random_bytes(24)));
    }
}
