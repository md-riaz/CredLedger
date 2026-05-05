<?php

namespace CredLedger\Tests;

use PHPUnit\Framework\TestCase;
use CredLedger\Services\TotpService;

class TotpServiceTest extends TestCase
{
    private TotpService $service;

    protected function setUp(): void
    {
        $this->service = new TotpService();
    }

    public function testGenerateSecret(): void
    {
        $secret = $this->service->generateSecret();
        
        $this->assertEquals(40, strlen($secret)); // 20 bytes = 40 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}$/', $secret);
    }

    public function testGenerateCode(): void
    {
        $secret = $this->service->generateSecret();
        $code = $this->service->generateCode($secret);
        
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
    }

    public function testVerifyCode(): void
    {
        $secret = $this->service->generateSecret();
        $code = $this->service->generateCode($secret);
        
        $this->assertTrue($this->service->verifyCode($secret, $code));
        $this->assertFalse($this->service->verifyCode($secret, '000000'));
    }

    public function testGetUri(): void
    {
        $secret = $this->service->generateSecret();
        $uri = $this->service->getUri($secret, 'TestApp', 'user@example.com');
        
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('TestApp', $uri);
        // Email is URL encoded in the URI
        $this->assertStringContainsString(urlencode('user@example.com'), $uri);
    }
}
