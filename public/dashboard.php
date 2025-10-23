<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$grantModel = $services['grantModel'];
$accessRequestModel = $services['accessRequestModel'];

// Get user's active grants
$activeGrants = $grantModel->getActiveByUser($currentUser['id']);

// Get user's pending access requests
$myRequests = $accessRequestModel->getByRequester($currentUser['id']);
$pendingRequests = array_filter($myRequests, fn($r) => $r['status'] === 'pending');

$title = 'Dashboard';
ob_start();
?>

<div class="card">
    <h2>Welcome, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
    <p>Your credential access dashboard</p>
</div>

<div class="card">
    <h2>Active Grants (<?= count($activeGrants) ?>)</h2>
    <?php if (empty($activeGrants)): ?>
        <p class="text-muted">You don't have any active credential grants.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Secret</th>
                    <th>Category</th>
                    <th>Expires At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activeGrants as $grant): ?>
                    <tr>
                        <td><?= htmlspecialchars($grant['secret_name']) ?></td>
                        <td><?= htmlspecialchars($grant['category'] ?? 'N/A') ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($grant['expires_at'])) ?></td>
                        <td>
                            <?php if (!$grant['checked_out_at']): ?>
                                <span class="badge badge-info">Available</span>
                            <?php elseif (!$grant['checked_in_at']): ?>
                                <span class="badge badge-warning">Checked Out</span>
                            <?php else: ?>
                                <span class="badge badge-success">Returned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/grant-view.php?id=<?= $grant['id'] ?>" class="btn btn-small">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Pending Access Requests (<?= count($pendingRequests) ?>)</h2>
    <?php if (empty($pendingRequests)): ?>
        <p class="text-muted">No pending access requests.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Secret</th>
                    <th>Requested</th>
                    <th>Duration</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingRequests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['secret_name']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($request['created_at'])) ?></td>
                        <td><?= $request['requested_duration_hours'] ?>h</td>
                        <td><?= htmlspecialchars($request['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <a href="/secrets.php" class="btn">Browse Secrets & Request Access</a>
    <a href="/grants.php" class="btn" style="margin-left: 1rem;">View All My Grants</a>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
