<?php

namespace CredLedger\Database;

use PDO;

class Migration
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, string $migrationsPath)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath;
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }

    public function run(): void
    {
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $migration = basename($file);
            
            if ($this->hasRun($migration)) {
                echo "Skipping: {$migration}\n";
                continue;
            }

            echo "Running: {$migration}\n";
            $sql = file_get_contents($file);
            
            try {
                $this->db->beginTransaction();
                $this->db->exec($sql);
                $this->markAsRun($migration);
                $this->db->commit();
                echo "Completed: {$migration}\n";
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw new \RuntimeException("Migration {$migration} failed: " . $e->getMessage());
            }
        }

        echo "All migrations completed.\n";
    }

    private function hasRun(string $migration): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
        $stmt->execute([$migration]);
        return $stmt->fetchColumn() > 0;
    }

    private function markAsRun(string $migration): void
    {
        $stmt = $this->db->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $stmt->execute([$migration]);
    }
}
