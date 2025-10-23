<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

// Redirect if already logged in
if ($authService->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

// Handle 2FA pending state
if ($authService->isPending2FA()) {
    header('Location: /2fa.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        $user = $authService->authenticate($email, $password);
        
        if ($user) {
            $services['auditLogService']->log(
                $user['id'],
                'auth.login_attempt',
                'user',
                $user['id'],
                'Login attempt successful',
                $authService->getClientIp(),
                $authService->getUserAgent()
            );
            
            // Check if 2FA is enabled
            if ($user['totp_enabled']) {
                $authService->setPending2FA($user['id']);
                header('Location: /2fa.php');
                exit;
            }
            
            // Login without 2FA
            $authService->login($user);
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
            $services['auditLogService']->log(
                null,
                'auth.login_failed',
                'user',
                null,
                "Failed login attempt for email: {$email}",
                $authService->getClientIp(),
                $authService->getUserAgent()
            );
        }
    } else {
        $error = 'Please provide email and password';
    }
}

$title = 'Login - CredLedger-Lite';
ob_start();
?>

<div class="card" style="max-width: 400px; margin: 4rem auto;">
    <h2>Login</h2>
    <form method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn" style="width: 100%;">Login</button>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
