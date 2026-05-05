<?php

namespace CredLedger\Models;

use PDO;

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(string $email, string $password, string $name, string $role = 'user'): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO users (email, password_hash, name, role)
            VALUES (?, ?, ?, ?)
        ');
        
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([$email, $passwordHash, $name, $role]);
        
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updateTotpSecret(int $userId, string $secret, bool $enabled = false): void
    {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET totp_secret = ?, totp_enabled = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$secret, $enabled ? 1 : 0, $userId]);
    }

    public function enableTotp(int $userId): void
    {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET totp_enabled = 1, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
    }

    public function disableTotp(int $userId): void
    {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET totp_enabled = 0, totp_secret = NULL, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
    }

    public function deactivate(int $userId): void
    {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET is_active = 0, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
    }

    public function activate(int $userId): void
    {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET is_active = 1, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$userId]);
    }

    public function getAll(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        $stmt = $this->db->query("SELECT * FROM users {$where} ORDER BY name");
        return $stmt->fetchAll();
    }
}
