<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn() || !$authService->isAdmin()) {
    header('Location: /dashboard.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$grantModel = $services['grantModel'];

$success = null;

// Handle grant revocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke'])) {
    $grantId = (int)($_POST['grant_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'Revoked by admin');
    
    $grant = $grantModel->findById($grantId);
    if ($grant && !$grant['is_revoked']) {
        $grantModel->revoke($grantId, $currentUser['id'], $reason);
        
        $services['auditLogService']->log(
            $currentUser['id'],
            'grant.revoked',
            'grant',
            $grantId,
            "Revoked grant for {$grant['user_name']}: {$grant['secret_name']}. Reason: {$reason}",
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        
        $success = 'Grant revoked successfully';
    }
}

$grants = $grantModel->getAll();

$title = 'Manage Grants';
ob_start();
?>

<div class="card">
    <h2>Grant Management</h2>
    <a href="/admin.php" class="btn btn-small">← Back to Admin</a>
</div>

<div class="card">
    <h2>All Grants</h2>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Secret</th>
                <th>Granted By</th>
                <th>Created</th>
                <th>Expires</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grants as $grant): ?>
                <?php 
                    $isActive = !$grant['is_revoked'] && strtotime($grant['expires_at']) > time();
                ?>
                <tr>
                    <td><?= htmlspecialchars($grant['user_name']) ?></td>
                    <td><?= htmlspecialchars($grant['secret_name']) ?></td>
                    <td><?= htmlspecialchars($grant['granted_by_name']) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($grant['created_at'])) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($grant['expires_at'])) ?></td>
                    <td>
                        <?php if ($grant['is_revoked']): ?>
                            <span class="badge badge-danger">Revoked</span>
                        <?php elseif (!$isActive): ?>
                            <span class="badge badge-warning">Expired</span>
                        <?php elseif ($grant['checked_in_at']): ?>
                            <span class="badge badge-success">Returned</span>
                        <?php elseif ($grant['checked_out_at']): ?>
                            <span class="badge badge-warning">Checked Out</span>
                        <?php else: ?>
                            <span class="badge badge-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isActive): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="grant_id" value="<?= $grant['id'] ?>">
                                <input type="text" name="reason" placeholder="Reason" 
                                       style="width: 150px; display: inline-block; padding: 0.25rem;">
                                <button type="submit" name="revoke" class="btn btn-danger btn-small">
                                    Revoke
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
