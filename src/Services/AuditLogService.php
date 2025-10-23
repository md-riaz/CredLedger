<?php

namespace CredLedger\Services;

use PDO;

class AuditLogService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an action to the append-only audit log
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $details = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $stmt = $this->db->prepare('
            INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $details,
            $ipAddress,
            $userAgent
        ]);
    }

    /**
     * Get audit logs with optional filtering
     */
    public function getLogs(
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $where = [];
        $params = [];

        if ($userId !== null) {
            $where[] = 'user_id = ?';
            $params[] = $userId;
        }

        if ($entityType !== null) {
            $where[] = 'entity_type = ?';
            $params[] = $entityType;
        }

        if ($entityId !== null) {
            $where[] = 'entity_id = ?';
            $params[] = $entityId;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT * FROM audit_log
            {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
