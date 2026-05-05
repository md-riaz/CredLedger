<?php

namespace CredLedger\Database;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private string $dbPath;

    public function __construct(string $dbPath)
    {
        $this->dbPath = $dbPath;
    }

    public function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                // Ensure directory exists
                $dir = dirname($this->dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                self::$instance = new PDO(
                    'sqlite:' . $this->dbPath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );

                // Enable foreign keys for SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON');
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
