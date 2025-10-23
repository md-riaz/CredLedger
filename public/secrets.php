<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$secretModel = $services['secretModel'];

$secrets = $secretModel->getAll();

$title = 'Secrets';
$success = $_GET['success'] ?? null;
ob_start();
?>

<div class="card">
    <h2>Available Secrets</h2>
    <p>Browse and request access to credentials</p>
</div>

<?php if (empty($secrets)): ?>
    <div class="card">
        <p class="text-muted">No secrets available.</p>
    </div>
<?php else: ?>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Owner</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secrets as $secret): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($secret['name']) ?></strong></td>
                        <td>
                            <?php if ($secret['category']): ?>
                                <span class="badge badge-info"><?= htmlspecialchars($secret['category']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($secret['owner_name']) ?></td>
                        <td><?= htmlspecialchars($secret['description'] ?? '') ?></td>
                        <td>
                            <a href="/request-access.php?secret_id=<?= $secret['id'] ?>" class="btn btn-small">Request Access</a>
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
