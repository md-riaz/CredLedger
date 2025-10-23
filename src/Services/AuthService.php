<?php

namespace CredLedger\Services;

use CredLedger\Models\User;

class AuthService
{
    private User $userModel;
    private TotpService $totpService;

    public function __construct(User $userModel, TotpService $totpService)
    {
        $this->userModel = $userModel;
        $this->totpService = $totpService;
    }

    /**
     * Start session if not already started
     */
    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Authenticate user with email and password
     * Returns user array on success, null on failure
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !$user['is_active']) {
            return null;
        }
        
        if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
            return null;
        }
        
        return $user;
    }

    /**
     * Verify TOTP code for 2FA
     */
    public function verifyTotp(array $user, string $code): bool
    {
        if (!$user['totp_enabled'] || !$user['totp_secret']) {
            return false;
        }
        
        return $this->totpService->verifyCode($user['totp_secret'], $code);
    }

    /**
     * Login user and create session
     */
    public function login(array $user): void
    {
        $this->startSession();
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Mark that 2FA is pending for a user
     */
    public function setPending2FA(int $userId): void
    {
        $this->startSession();
        $_SESSION['pending_2fa_user_id'] = $userId;
        $_SESSION['pending_2fa_time'] = time();
    }

    /**
     * Check if 2FA is pending
     */
    public function isPending2FA(): bool
    {
        $this->startSession();
        return isset($_SESSION['pending_2fa_user_id']);
    }

    /**
     * Complete 2FA verification
     */
    public function complete2FA(): void
    {
        $this->startSession();
        
        if (isset($_SESSION['pending_2fa_user_id'])) {
            $userId = $_SESSION['pending_2fa_user_id'];
            $user = $this->userModel->findById($userId);
            
            if ($user) {
                unset($_SESSION['pending_2fa_user_id']);
                unset($_SESSION['pending_2fa_time']);
                $this->login($user);
            }
        }
    }

    /**
     * Logout user and destroy session
     */
    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user data from session
     */
    public function getCurrentUser(): ?array
    {
        $this->startSession();
        
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role']
        ];
    }

    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Get client IP address
     */
    public function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}
