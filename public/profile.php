<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$userModel = $services['userModel'];
$totpService = $services['totpService'];

$user = $userModel->findById($currentUser['id']);
$error = null;
$success = null;
$showQr = false;
$totpUri = null;

// Setup 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_2fa'])) {
    if (!$user['totp_secret']) {
        $secret = $totpService->generateSecret();
        $userModel->updateTotpSecret($user['id'], $secret, false);
        
        $totpUri = $totpService->getUri(
            $secret,
            'CredLedger-Lite',
            $user['email']
        );
        
        $services['auditLogService']->log(
            $user['id'],
            'user.2fa_setup_started',
            'user',
            $user['id'],
            '2FA setup initiated',
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        
        $showQr = true;
        $user = $userModel->findById($user['id']);
    }
}

// Enable 2FA after verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_2fa'])) {
    $code = $_POST['code'] ?? '';
    
    if ($user['totp_secret'] && !$user['totp_enabled']) {
        if ($totpService->verifyCode($user['totp_secret'], $code)) {
            $userModel->enableTotp($user['id']);
            
            $services['auditLogService']->log(
                $user['id'],
                'user.2fa_enabled',
                'user',
                $user['id'],
                '2FA enabled',
                $authService->getClientIp(),
                $authService->getUserAgent()
            );
            
            $success = '2FA enabled successfully';
            $user = $userModel->findById($user['id']);
        } else {
            $error = 'Invalid verification code';
        }
    }
}

// Disable 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_2fa'])) {
    if ($user['totp_enabled']) {
        $userModel->disableTotp($user['id']);
        
        $services['auditLogService']->log(
            $user['id'],
            'user.2fa_disabled',
            'user',
            $user['id'],
            '2FA disabled',
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        
        $success = '2FA disabled';
        $user = $userModel->findById($user['id']);
    }
}

// Show QR if secret exists but not enabled
if ($user['totp_secret'] && !$user['totp_enabled'] && !$totpUri) {
    $totpUri = $totpService->getUri(
        $user['totp_secret'],
        'CredLedger-Lite',
        $user['email']
    );
    $showQr = true;
}

$title = 'Profile';
ob_start();
?>

<div class="card">
    <h2>User Profile</h2>
    <table style="margin-bottom: 1rem;">
        <tr>
            <th>Name</th>
            <td><?= htmlspecialchars($user['name']) ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
        <tr>
            <th>Role</th>
            <td><span class="badge badge-info"><?= htmlspecialchars($user['role']) ?></span></td>
        </tr>
    </table>
</div>

<div class="card">
    <h2>Two-Factor Authentication (2FA)</h2>
    
    <?php if ($user['totp_enabled']): ?>
        <div class="alert alert-success">
            <strong>2FA is enabled</strong> - Your account is protected with two-factor authentication
        </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA?')">
            <button type="submit" name="disable_2fa" class="btn btn-danger">Disable 2FA</button>
        </form>
        
    <?php elseif ($showQr): ?>
        <div class="alert alert-info">
            <strong>Scan this QR code with your authenticator app</strong>
        </div>
        <div style="background: white; padding: 1rem; text-align: center; border-radius: 4px; margin-bottom: 1rem;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($totpUri) ?>" 
                 alt="QR Code" style="max-width: 200px;">
            <p class="text-muted" style="margin-top: 0.5rem;">Or enter this code manually:</p>
            <code style="background: #f8f9fa; padding: 0.5rem; display: inline-block; border-radius: 4px;">
                <?= $user['totp_secret'] ?>
            </code>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="code">Enter verification code from your app</label>
                <input type="text" id="code" name="code" pattern="[0-9]{6}" maxlength="6" required 
                       style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
            </div>
            <button type="submit" name="enable_2fa" class="btn">Verify & Enable 2FA</button>
        </form>
        
    <?php else: ?>
        <p>Add an extra layer of security to your account with two-factor authentication.</p>
        <form method="POST">
            <button type="submit" name="setup_2fa" class="btn">Setup 2FA</button>
        </form>
    <?php endif; ?>
</div>

<div style="margin-top: 1.5rem;">
    <a href="/dashboard.php" class="btn btn-danger">Back to Dashboard</a>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
