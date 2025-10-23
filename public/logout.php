<?php

$services = require_once __DIR__ . '/../src/bootstrap.php';
$authService = $services['authService'];

$currentUser = $authService->getCurrentUser();
if ($currentUser) {
    $services['auditLogService']->log(
        $currentUser['id'],
        'auth.logout',
        'user',
        $currentUser['id'],
        'User logged out',
        $authService->getClientIp(),
        $authService->getUserAgent()
    );
}

$authService->logout();
header('Location: /index.php');
exit;
