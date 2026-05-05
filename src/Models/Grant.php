<?php

namespace CredLedger\Models;

use PDO;
use DateTime;

class Grant
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(
        int $secretId,
        int $userId,
        int $grantedBy,
        int $durationHours,
        ?int $accessRequestId = null
    ): int {
        $expiresAt = (new DateTime())->modify("+{$durationHours} hours")->format('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare('
            INSERT INTO grants (secret_id, user_id, granted_by, expires_at, access_request_id)
            VALUES (?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([$secretId, $userId, $grantedBy, $expiresAt, $accessRequestId]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT g.*, 
                   s.name as secret_name,
                   u1.name as user_name, u1.email as user_email,
                   u2.name as granted_by_name,
                   u3.name as revoked_by_name
            FROM grants g
            JOIN secrets s ON g.secret_id = s.id
            JOIN users u1 ON g.user_id = u1.id
            JOIN users u2 ON g.granted_by = u2.id
            LEFT JOIN users u3 ON g.revoked_by = u3.id
            WHERE g.id = ?
        ');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function getActiveByUser(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT g.*, s.name as secret_name, s.category
            FROM grants g
            JOIN secrets s ON g.secret_id = s.id
            WHERE g.user_id = ? 
              AND g.is_revoked = 0
              AND g.expires_at > CURRENT_TIMESTAMP
            ORDER BY g.expires_at ASC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT g.*, s.name as secret_name
            FROM grants g
            JOIN secrets s ON g.secret_id = s.id
            WHERE g.user_id = ?
            ORDER BY g.created_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('
            SELECT g.*, 
                   s.name as secret_name,
                   u1.name as user_name,
                   u2.name as granted_by_name
            FROM grants g
            JOIN secrets s ON g.secret_id = s.id
            JOIN users u1 ON g.user_id = u1.id
            JOIN users u2 ON g.granted_by = u2.id
            ORDER BY g.created_at DESC
        ');
        return $stmt->fetchAll();
    }

    public function checkout(int $id): void
    {
        $stmt = $this->db->prepare('
            UPDATE grants 
            SET checked_out_at = CURRENT_TIMESTAMP
            WHERE id = ? AND checked_out_at IS NULL
        ');
        $stmt->execute([$id]);
    }

    public function checkin(int $id): void
    {
        $stmt = $this->db->prepare('
            UPDATE grants 
            SET checked_in_at = CURRENT_TIMESTAMP
            WHERE id = ? AND checked_in_at IS NULL
        ');
        $stmt->execute([$id]);
    }

    public function revoke(int $id, int $revokedBy, ?string $reason = null): void
    {
        $stmt = $this->db->prepare('
            UPDATE grants 
            SET is_revoked = 1,
                revoked_by = ?,
                revoked_at = CURRENT_TIMESTAMP,
                revocation_reason = ?
            WHERE id = ?
        ');
        $stmt->execute([$revokedBy, $reason, $id]);
    }

    public function revokeAllForUser(int $userId, int $revokedBy, string $reason = 'User offboarding'): int
    {
        $stmt = $this->db->prepare('
            UPDATE grants 
            SET is_revoked = 1,
                revoked_by = ?,
                revoked_at = CURRENT_TIMESTAMP,
                revocation_reason = ?
            WHERE user_id = ? AND is_revoked = 0
        ');
        $stmt->execute([$revokedBy, $reason, $userId]);
        return $stmt->rowCount();
    }

    public function getBySecret(int $secretId): array
    {
        $stmt = $this->db->prepare('
            SELECT g.*, 
                   u1.name as user_name,
                   u2.name as granted_by_name
            FROM grants g
            JOIN users u1 ON g.user_id = u1.id
            JOIN users u2 ON g.granted_by = u2.id
            WHERE g.secret_id = ?
            ORDER BY g.created_at DESC
        ');
        $stmt->execute([$secretId]);
        return $stmt->fetchAll();
    }
}
