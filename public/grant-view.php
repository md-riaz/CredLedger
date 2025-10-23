<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$grantModel = $services['grantModel'];
$secretModel = $services['secretModel'];

$grantId = (int)($_GET['id'] ?? 0);
$grant = $grantModel->findById($grantId);

if (!$grant || $grant['user_id'] != $currentUser['id']) {
    header('Location: /grants.php');
    exit;
}

$isActive = !$grant['is_revoked'] && strtotime($grant['expires_at']) > time();
$error = null;
$success = null;

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout']) && $isActive) {
    if (!$grant['checked_out_at']) {
        $grantModel->checkout($grantId);
        $services['auditLogService']->log(
            $currentUser['id'],
            'grant.checkout',
            'grant',
            $grantId,
            "Checked out credential: {$grant['secret_name']}",
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        $success = 'Credential checked out successfully';
        $grant = $grantModel->findById($grantId); // Refresh
    }
}

// Handle checkin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin']) && $isActive) {
    if ($grant['checked_out_at'] && !$grant['checked_in_at']) {
        $grantModel->checkin($grantId);
        $services['auditLogService']->log(
            $currentUser['id'],
            'grant.checkin',
            'grant',
            $grantId,
            "Checked in credential: {$grant['secret_name']}",
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        $success = 'Credential checked in successfully';
        $grant = $grantModel->findById($grantId); // Refresh
    }
}

$title = 'View Grant';
ob_start();
?>

<div class="card">
    <h2>Grant Details</h2>
    <table style="margin-bottom: 1.5rem;">
        <tr>
            <th>Secret Name</th>
            <td><?= htmlspecialchars($grant['secret_name']) ?></td>
        </tr>
        <tr>
            <th>Granted By</th>
            <td><?= htmlspecialchars($grant['granted_by_name']) ?></td>
        </tr>
        <tr>
            <th>Created At</th>
            <td><?= date('Y-m-d H:i:s', strtotime($grant['created_at'])) ?></td>
        </tr>
        <tr>
            <th>Expires At</th>
            <td><?= date('Y-m-d H:i:s', strtotime($grant['expires_at'])) ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td>
                <?php if ($grant['is_revoked']): ?>
                    <span class="badge badge-danger">Revoked</span>
                    <?php if ($grant['revocation_reason']): ?>
                        <p class="text-muted">Reason: <?= htmlspecialchars($grant['revocation_reason']) ?></p>
                    <?php endif; ?>
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
        </tr>
        <?php if ($grant['checked_out_at']): ?>
        <tr>
            <th>Checked Out At</th>
            <td><?= date('Y-m-d H:i:s', strtotime($grant['checked_out_at'])) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($grant['checked_in_at']): ?>
        <tr>
            <th>Checked In At</th>
            <td><?= date('Y-m-d H:i:s', strtotime($grant['checked_in_at'])) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if ($isActive): ?>
        <?php if (!$grant['checked_out_at']): ?>
            <form method="POST" style="display: inline;">
                <button type="submit" name="checkout" class="btn btn-success">Checkout Credential</button>
            </form>
            <p class="text-muted" style="margin-top: 0.5rem;">Click to view the credential value</p>
        <?php elseif (!$grant['checked_in_at']): ?>
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <strong>Credential Value:</strong>
                <div style="background: #fff; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem; font-family: monospace;">
                    <?= htmlspecialchars($secretModel->getDecryptedValue($grant['secret_id'])) ?>
                </div>
            </div>
            <form method="POST">
                <button type="submit" name="checkin" class="btn">Check In (Return)</button>
            </form>
            <p class="text-muted" style="margin-top: 0.5rem;">Check in when you're done using this credential</p>
        <?php else: ?>
            <p class="text-muted">This credential has been returned.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-muted">This grant is no longer active.</p>
    <?php endif; ?>

    <div style="margin-top: 1.5rem;">
        <a href="/grants.php" class="btn btn-danger">Back to My Grants</a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
