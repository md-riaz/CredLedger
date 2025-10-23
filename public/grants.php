<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$grantModel = $services['grantModel'];

$grants = $grantModel->getByUser($currentUser['id']);

$title = 'My Grants';
ob_start();
?>

<div class="card">
    <h2>My Credential Grants</h2>
    <p>All grants assigned to you</p>
</div>

<?php if (empty($grants)): ?>
    <div class="card">
        <p class="text-muted">You don't have any grants yet.</p>
        <a href="/secrets.php" class="btn">Browse Secrets & Request Access</a>
    </div>
<?php else: ?>
    <div class="card">
        <table>
            <thead>
                <tr>
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
                        $isExpired = strtotime($grant['expires_at']) <= time();
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($grant['secret_name']) ?></td>
                        <td><?= htmlspecialchars($grant['granted_by_name'] ?? 'System') ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($grant['created_at'])) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($grant['expires_at'])) ?></td>
                        <td>
                            <?php if ($grant['is_revoked']): ?>
                                <span class="badge badge-danger">Revoked</span>
                            <?php elseif ($isExpired): ?>
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
                                <a href="/grant-view.php?id=<?= $grant['id'] ?>" class="btn btn-small">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
