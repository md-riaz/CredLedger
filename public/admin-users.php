<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn() || !$authService->isAdmin()) {
    header('Location: /dashboard.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$userModel = $services['userModel'];
$grantModel = $services['grantModel'];

$success = null;
$error = null;

// Handle user offboarding (revoke all grants)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offboard'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $user = $userModel->findById($userId);
    
    if ($user && $userId != $currentUser['id']) {
        $revokedCount = $grantModel->revokeAllForUser($userId, $currentUser['id'], 'User offboarded');
        $userModel->deactivate($userId);
        
        $services['auditLogService']->log(
            $currentUser['id'],
            'user.offboarded',
            'user',
            $userId,
            "User offboarded, {$revokedCount} grants revoked",
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        
        $success = "User offboarded successfully. {$revokedCount} grants revoked.";
    }
}

$users = $userModel->getAll(false);

$title = 'Manage Users';
ob_start();
?>

<div class="card">
    <h2>User Management</h2>
    <a href="/admin.php" class="btn btn-small">← Back to Admin</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>2FA</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($user['role']) ?></span></td>
                    <td>
                        <?php if ($user['totp_enabled']): ?>
                            <span class="badge badge-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                    <td>
                        <?php if ($user['is_active'] && $user['id'] != $currentUser['id']): ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('This will revoke all grants for this user. Continue?')">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="offboard" class="btn btn-danger btn-small">
                                    Offboard
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
