<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];
$userModel = $services['userModel'];

// Check if 2FA is pending
if (!$authService->isPending2FA()) {
    header('Location: /index.php');
    exit;
}

$authService->startSession();
$userId = $_SESSION['pending_2fa_user_id'];
$user = $userModel->findById($userId);

if (!$user) {
    header('Location: /index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    
    if ($code) {
        if ($authService->verifyTotp($user, $code)) {
            $services['auditLogService']->log(
                $user['id'],
                'auth.2fa_verified',
                'user',
                $user['id'],
                '2FA verification successful',
                $authService->getClientIp(),
                $authService->getUserAgent()
            );
            
            $authService->complete2FA();
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Invalid verification code';
            $services['auditLogService']->log(
                $user['id'],
                'auth.2fa_failed',
                'user',
                $user['id'],
                '2FA verification failed',
                $authService->getClientIp(),
                $authService->getUserAgent()
            );
        }
    } else {
        $error = 'Please enter verification code';
    }
}

$title = '2FA Verification - CredLedger-Lite';
ob_start();
?>

<div class="card" style="max-width: 400px; margin: 4rem auto;">
    <h2>Two-Factor Authentication</h2>
    <p style="margin-bottom: 1rem; color: #666;">Enter the 6-digit code from your authenticator app.</p>
    <form method="POST">
        <div class="form-group">
            <label for="code">Verification Code</label>
            <input type="text" id="code" name="code" pattern="[0-9]{6}" maxlength="6" required autofocus 
                   style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
        </div>
        <button type="submit" class="btn" style="width: 100%;">Verify</button>
    </form>
    <a href="/logout.php" style="display: block; text-align: center; margin-top: 1rem; color: #666;">Cancel</a>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
