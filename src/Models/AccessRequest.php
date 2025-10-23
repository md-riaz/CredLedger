<?php

namespace CredLedger\Models;

use PDO;

class AccessRequest
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(
        int $secretId,
        int $requesterId,
        string $reason,
        int $durationHours
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO access_requests (secret_id, requester_id, reason, requested_duration_hours)
            VALUES (?, ?, ?, ?)
        ');
        
        $stmt->execute([$secretId, $requesterId, $reason, $durationHours]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT ar.*, 
                   s.name as secret_name,
                   u1.name as requester_name, u1.email as requester_email,
                   u2.name as reviewer_name
            FROM access_requests ar
            JOIN secrets s ON ar.secret_id = s.id
            JOIN users u1 ON ar.requester_id = u1.id
            LEFT JOIN users u2 ON ar.reviewed_by = u2.id
            WHERE ar.id = ?
        ');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function getPending(): array
    {
        $stmt = $this->db->query('
            SELECT ar.*, 
                   s.name as secret_name,
                   u.name as requester_name, u.email as requester_email
            FROM access_requests ar
            JOIN secrets s ON ar.secret_id = s.id
            JOIN users u ON ar.requester_id = u.id
            WHERE ar.status = "pending"
            ORDER BY ar.created_at DESC
        ');
        return $stmt->fetchAll();
    }

    public function getByRequester(int $requesterId): array
    {
        $stmt = $this->db->prepare('
            SELECT ar.*, 
                   s.name as secret_name,
                   u.name as reviewer_name
            FROM access_requests ar
            JOIN secrets s ON ar.secret_id = s.id
            LEFT JOIN users u ON ar.reviewed_by = u.id
            WHERE ar.requester_id = ?
            ORDER BY ar.created_at DESC
        ');
        $stmt->execute([$requesterId]);
        return $stmt->fetchAll();
    }

    public function approve(int $id, int $reviewerId, ?string $note = null): void
    {
        $stmt = $this->db->prepare('
            UPDATE access_requests 
            SET status = "approved", 
                reviewed_by = ?,
                reviewed_at = CURRENT_TIMESTAMP,
                review_note = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$reviewerId, $note, $id]);
    }

    public function reject(int $id, int $reviewerId, ?string $note = null): void
    {
        $stmt = $this->db->prepare('
            UPDATE access_requests 
            SET status = "rejected", 
                reviewed_by = ?,
                reviewed_at = CURRENT_TIMESTAMP,
                review_note = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        $stmt->execute([$reviewerId, $note, $id]);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('
            SELECT ar.*, 
                   s.name as secret_name,
                   u1.name as requester_name,
                   u2.name as reviewer_name
            FROM access_requests ar
            JOIN secrets s ON ar.secret_id = s.id
            JOIN users u1 ON ar.requester_id = u1.id
            LEFT JOIN users u2 ON ar.reviewed_by = u2.id
            ORDER BY ar.created_at DESC
        ');
        return $stmt->fetchAll();
    }
}
