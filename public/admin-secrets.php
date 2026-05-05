<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

if (!$authService->isLoggedIn() || !$authService->isAdmin()) {
    header('Location: /dashboard.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$secretModel = $services['secretModel'];

$success = null;
$error = null;

// Handle secret creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_secret'])) {
    $name = trim($_POST['name'] ?? '');
    $value = trim($_POST['value'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    if ($name && $value) {
        $secretId = $secretModel->create($name, $value, $currentUser['id'], $description ?: null, $category ?: null);
        
        $services['auditLogService']->log(
            $currentUser['id'],
            'secret.created',
            'secret',
            $secretId,
            "Created secret: {$name}",
            $authService->getClientIp(),
            $authService->getUserAgent()
        );
        
        $success = 'Secret created successfully';
    } else {
        $error = 'Name and value are required';
    }
}

$secrets = $secretModel->getAll(true);

$title = 'Manage Secrets';
ob_start();
?>

<div class="card">
    <h2>Secret Management</h2>
    <a href="/admin.php" class="btn btn-small">← Back to Admin</a>
</div>

<div class="card">
    <h2>Create New Secret</h2>
    <form method="POST">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="value">Value (will be encrypted)</label>
            <textarea id="value" name="value" required></textarea>
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" placeholder="e.g., cloud, database, api">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>
        <button type="submit" name="create_secret" class="btn">Create Secret</button>
    </form>
</div>

<div class="card">
    <h2>Existing Secrets</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Owner</th>
                <th>Description</th>
                <th>Created</th>
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
                    <td><?= date('Y-m-d', strtotime($secret['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../src/Views/layout.php';
