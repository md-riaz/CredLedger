<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn() || !$authService->isAdmin()) {
    header('Location: /dashboard.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$accessRequestModel = $services['accessRequestModel'];
$userModel = $services['userModel'];
$grantModel = $services['grantModel'];
$secretModel = $services['secretModel'];

// Handle approve/reject requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['note'] ?? '');
    
    if ($requestId && in_array($action, ['approve', 'reject'])) {
        $request = $accessRequestModel->findById($requestId);
        
        if ($request && $request['status'] === 'pending') {
            if ($action === 'approve') {
                $accessRequestModel->approve($requestId, $currentUser['id'], $note);
                
                // Create grant
                $grantId = $grantModel->create(
                    $request['secret_id'],
                    $request['requester_id'],
                    $currentUser['id'],
                    $request['requested_duration_hours'],
                    $requestId
                );
                
                $services['auditLogService']->log(
                    $currentUser['id'],
                    'access_request.approved',
                    'access_request',
                    $requestId,
                    "Approved access request and created grant #{$grantId}",
                    $authService->getClientIp(),
                    $authService->getUserAgent()
                );
                
                $success = 'Access request approved and grant created';
            } else {
                $accessRequestModel->reject($requestId, $currentUser['id'], $note);
                
                $services['auditLogService']->log(
                    $currentUser['id'],
                    'access_request.rejected',
                    'access_request',
                    $requestId,
                    "Rejected access request",
                    $authService->getClientIp(),
                    $authService->getUserAgent()
                );
                
                $success = 'Access request rejected';
            }
        }
    }
}

$pendingRequests = $accessRequestModel->getPending();
$allUsers = $userModel->getAll(false);
$allSecrets = $secretModel->getAll(false);
$allGrants = $grantModel->getAll();
$recentRequests = array_slice($accessRequestModel->getAll(), 0, 10);

$title = 'Admin Dashboard';
ob_start();
?>

<div class="card">
    <h2>Admin Dashboard</h2>
    <p>Manage users, secrets, and access requests</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="text-align: center;">
        <h3><?= count($allUsers) ?></h3>
        <p class="text-muted">Total Users</p>
        <a href="/admin-users.php" class="btn btn-small">Manage</a>
    </div>
    <div class="card" style="text-align: center;">
        <h3><?= count($allSecrets) ?></h3>
        <p class="text-muted">Total Secrets</p>
        <a href="/admin-secrets.php" class="btn btn-small">Manage</a>
    </div>
    <div class="card" style="text-align: center;">
        <h3><?= count($pendingRequests) ?></h3>
        <p class="text-muted">Pending Requests</p>
    </div>
    <div class="card" style="text-align: center;">
        <h3><?= count($allGrants) ?></h3>
        <p class="text-muted">Total Grants</p>
        <a href="/admin-grants.php" class="btn btn-small">View All</a>
    </div>
</div>

<div class="card">
    <h2>Pending Access Requests (<?= count($pendingRequests) ?>)</h2>
    <?php if (empty($pendingRequests)): ?>
        <p class="text-muted">No pending access requests.</p>
    <?php else: ?>
        <?php foreach ($pendingRequests as $request): ?>
            <div style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <strong><?= htmlspecialchars($request['requester_name']) ?></strong> 
                        (<?= htmlspecialchars($request['requester_email']) ?>)
                        <p style="margin: 0.5rem 0;">
                            <strong>Secret:</strong> <?= htmlspecialchars($request['secret_name']) ?><br>
                            <strong>Duration:</strong> <?= $request['requested_duration_hours'] ?> hours<br>
                            <strong>Requested:</strong> <?= date('Y-m-d H:i', strtotime($request['created_at'])) ?>
                        </p>
                        <p style="margin: 0.5rem 0; padding: 0.5rem; background: #f8f9fa; border-radius: 4px;">
                            <strong>Reason:</strong><br>
                            <?= nl2br(htmlspecialchars($request['reason'])) ?>
                        </p>
                    </div>
                    <div style="flex-shrink: 0; margin-left: 1rem;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <textarea name="note" placeholder="Optional note" 
                                      style="width: 200px; height: 60px; margin-bottom: 0.5rem;"></textarea><br>
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-small">
                                Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-small">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
