<?php

namespace CredLedger\Tests;

use PHPUnit\Framework\TestCase;
use CredLedger\Models\User;
use CredLedger\Database\Database;
use PDO;

class UserModelTest extends TestCase
{
    private PDO $db;
    private User $userModel;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/test_' . uniqid() . '.db';
        $database = new Database($this->dbPath);
        $this->db = $database->getConnection();
        
        // Create users table
        $this->db->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT "user",
                totp_secret VARCHAR(255),
                totp_enabled BOOLEAN DEFAULT 0,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        $this->userModel = new User($this->db);
    }

    protected function tearDown(): void
    {
        Database::resetInstance();
        if (isset($this->dbPath) && file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testCreateUser(): void
    {
        $userId = $this->userModel->create(
            'test@example.com',
            'password123',
            'Test User',
            'user'
        );
        
        $this->assertGreaterThan(0, $userId);
        
        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('Test User', $user['name']);
    }

    public function testFindByEmail(): void
    {
        $this->userModel->create('test@example.com', 'password123', 'Test User');
        
        $user = $this->userModel->findByEmail('test@example.com');
        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user['email']);
        
        $notFound = $this->userModel->findByEmail('notfound@example.com');
        $this->assertNull($notFound);
    }

    public function testVerifyPassword(): void
    {
        $userId = $this->userModel->create('test@example.com', 'password123', 'Test User');
        $user = $this->userModel->findById($userId);
        
        $this->assertTrue($this->userModel->verifyPassword('password123', $user['password_hash']));
        $this->assertFalse($this->userModel->verifyPassword('wrongpassword', $user['password_hash']));
    }

    public function testTotpManagement(): void
    {
        $userId = $this->userModel->create('test@example.com', 'password123', 'Test User');
        
        $this->userModel->updateTotpSecret($userId, 'test_secret', true);
        $user = $this->userModel->findById($userId);
        $this->assertEquals('test_secret', $user['totp_secret']);
        $this->assertEquals(1, $user['totp_enabled']);
        
        $this->userModel->disableTotp($userId);
        $user = $this->userModel->findById($userId);
        $this->assertEquals(0, $user['totp_enabled']);
        $this->assertNull($user['totp_secret']);
    }

    public function testUserActivation(): void
    {
        $userId = $this->userModel->create('test@example.com', 'password123', 'Test User');
        
        $this->userModel->deactivate($userId);
        $user = $this->userModel->findById($userId);
        $this->assertEquals(0, $user['is_active']);
        
        $this->userModel->activate($userId);
        $user = $this->userModel->findById($userId);
        $this->assertEquals(1, $user['is_active']);
    }
}
