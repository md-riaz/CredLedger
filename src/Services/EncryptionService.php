<?php

namespace CredLedger\Services;

use RuntimeException;

class EncryptionService
{
    private string $masterKey;

    public function __construct(string $masterKeyHex)
    {
        if (strlen($masterKeyHex) !== 64) {
            throw new RuntimeException('Master key must be 64 hex characters (32 bytes)');
        }
        
        $this->masterKey = hex2bin($masterKeyHex);
        
        if ($this->masterKey === false || strlen($this->masterKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('Invalid master key format');
        }
    }

    /**
     * Encrypt data using envelope encryption (libsodium secretbox)
     * Returns array with encrypted_value and nonce
     */
    public function encrypt(string $plaintext): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->masterKey);
        
        return [
            'encrypted_value' => base64_encode($ciphertext),
            'nonce' => base64_encode($nonce)
        ];
    }

    /**
     * Decrypt data using the nonce and master key
     */
    public function decrypt(string $encryptedValue, string $nonce): string
    {
        $ciphertext = base64_decode($encryptedValue);
        $nonceBytes = base64_decode($nonce);
        
        if (strlen($nonceBytes) !== SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Invalid nonce length');
        }
        
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonceBytes, $this->masterKey);
        
        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed');
        }
        
        return $plaintext;
    }

    /**
     * Generate a new encryption key (for initial setup)
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }
}
