<?php

namespace CredLedger\Models;

use PDO;
use CredLedger\Services\EncryptionService;

class Secret
{
    private PDO $db;
    private EncryptionService $encryption;

    public function __construct(PDO $db, EncryptionService $encryption)
    {
        $this->db = $db;
        $this->encryption = $encryption;
    }

    public function create(
        string $name,
        string $value,
        int $ownerId,
        ?string $description = null,
        ?string $category = null
    ): int {
        $encrypted = $this->encryption->encrypt($value);
        
        $stmt = $this->db->prepare('
            INSERT INTO secrets (name, description, encrypted_value, nonce, category, owner_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $name,
            $description,
            $encrypted['encrypted_value'],
            $encrypted['nonce'],
            $category,
            $ownerId
        ]);
        
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, u.name as owner_name, u.email as owner_email
            FROM secrets s
            JOIN users u ON s.owner_id = u.id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function getDecryptedValue(int $id): ?string
    {
        $secret = $this->findById($id);
        if (!$secret) {
            return null;
        }
        
        try {
            return $this->encryption->decrypt($secret['encrypted_value'], $secret['nonce']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAll(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE s.is_active = 1' : '';
        $stmt = $this->db->query("
            SELECT s.*, u.name as owner_name
            FROM secrets s
            JOIN users u ON s.owner_id = u.id
            {$where}
            ORDER BY s.name
        ");
        return $stmt->fetchAll();
    }

    public function getByOwner(int $ownerId, bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'AND s.is_active = 1' : '';
        $stmt = $this->db->prepare("
            SELECT s.*, u.name as owner_name
            FROM secrets s
            JOIN users u ON s.owner_id = u.id
            WHERE s.owner_id = ? {$where}
            ORDER BY s.name
        ");
        $stmt->execute([$ownerId]);
        return $stmt->fetchAll();
    }

    public function update(
        int $id,
        string $name,
        ?string $description = null,
        ?string $category = null
    ): void {
        $stmt = $this->db->prepare('
            UPDATE secrets 
            SET name = ?, description = ?, category = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$name, $description, $category, $id]);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare('
            UPDATE secrets 
            SET is_active = 0, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$id]);
    }
}
