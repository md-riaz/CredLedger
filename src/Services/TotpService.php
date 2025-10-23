<?php

namespace CredLedger\Services;

class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;

    /**
     * Generate a new TOTP secret
     */
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(20));
    }

    /**
     * Generate TOTP code for a given secret at a specific time
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = floor($timestamp / self::PERIOD);
        
        $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $counter), hex2bin($secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::DIGITS);
        
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a TOTP code against a secret (with time window tolerance)
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();
        
        // Check current time and surrounding windows
        for ($i = -$window; $i <= $window; $i++) {
            $testTime = $timestamp + ($i * self::PERIOD);
            if ($this->generateCode($secret, $testTime) === $code) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate a QR code-compatible URI for TOTP setup
     */
    public function getUri(string $secret, string $issuer, string $accountName): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            urlencode($issuer),
            urlencode($accountName),
            $this->base32Encode(hex2bin($secret)),
            urlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    /**
     * Base32 encode (RFC 4648) for TOTP URI
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;
            
            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }
        
        if ($vbits > 0) {
            $output .= $alphabet[($v << (5 - $vbits)) & 31];
        }
        
        return $output;
    }
}
