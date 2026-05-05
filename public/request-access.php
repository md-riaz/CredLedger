<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$secretModel = $services['secretModel'];
$accessRequestModel = $services['accessRequestModel'];

$secretId = (int)($_GET['secret_id'] ?? 0);
$secret = $secretModel->findById($secretId);

if (!$secret) {
    header('Location: /secrets.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0);
    
    if (!$reason || !$duration) {
        $error = 'Please provide a reason and duration';
    } elseif ($duration < 1 || $duration > 720) {
        $error = 'Duration must be between 1 and 720 hours (30 days)';
    } else {
        $requestId = $accessRequestModel->create($secretId, $currentUser['id'], $reason, $duration);
        
        $services['auditLogService']->log(
            $currentUser['id'],
            'access_request.created',
            'access_request',
            $requestId,
            "Requested access to secret: {$secret['name']}",
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        
        header('Location: /dashboard.php?success=request_submitted');
        exit;
    }
}

$title = 'Request Access';
ob_start();
?>

<div class="card">
    <h2>Request Access</h2>
    <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <strong>Secret:</strong> <?= htmlspecialchars($secret['name']) ?><br>
        <strong>Owner:</strong> <?= htmlspecialchars($secret['owner_name']) ?><br>
        <?php if ($secret['description']): ?>
            <strong>Description:</strong> <?= htmlspecialchars($secret['description']) ?><br>
        <?php endif; ?>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label for="reason">Reason for Access</label>
            <textarea id="reason" name="reason" required placeholder="Explain why you need access to this credential"></textarea>
        </div>
        <div class="form-group">
            <label for="duration">Duration (hours)</label>
            <input type="number" id="duration" name="duration" min="1" max="720" value="24" required>
            <small class="text-muted">Maximum 720 hours (30 days)</small>
        </div>
        <button type="submit" class="btn">Submit Request</button>
        <a href="/secrets.php" class="btn btn-danger">Cancel</a>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
