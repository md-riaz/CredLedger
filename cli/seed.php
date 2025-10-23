#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/bootstrap.php';

echo "Seeding database with sample data...\n\n";

try {
    // Create admin user
    echo "Creating admin user...\n";
    $adminId = $userModel->create('admin@example.com', 'admin123', 'Admin User', 'admin');
    $auditLogService->log($adminId, 'user.created', 'user', $adminId, 'Admin user created via seeder');
    
    // Create regular users
    echo "Creating regular users...\n";
    $user1Id = $userModel->create('alice@example.com', 'password123', 'Alice Smith', 'user');
    $auditLogService->log($user1Id, 'user.created', 'user', $user1Id, 'User created via seeder');
    
    $user2Id = $userModel->create('bob@example.com', 'password123', 'Bob Jones', 'user');
    $auditLogService->log($user2Id, 'user.created', 'user', $user2Id, 'User created via seeder');
    
    // Create sample secrets
    echo "Creating sample secrets...\n";
    $secret1Id = $secretModel->create(
        'AWS Production',
        'AKIAIOSFODNN7EXAMPLE',
        $adminId,
        'Production AWS access key',
        'cloud'
    );
    $auditLogService->log($adminId, 'secret.created', 'secret', $secret1Id, 'Secret created via seeder');
    
    $secret2Id = $secretModel->create(
        'Database Password',
        'super_secret_db_password_123',
        $adminId,
        'Production database password',
        'database'
    );
    $auditLogService->log($adminId, 'secret.created', 'secret', $secret2Id, 'Secret created via seeder');
    
    $secret3Id = $secretModel->create(
        'API Key - Stripe',
        'sk_test_example_key_12345678901234567890',
        $adminId,
        'Stripe payment API key',
        'api'
    );
    $auditLogService->log($adminId, 'secret.created', 'secret', $secret3Id, 'Secret created via seeder');
    
    // Create sample access requests
    echo "Creating sample access requests...\n";
    $request1Id = $accessRequestModel->create(
        $secret1Id,
        $user1Id,
        'Need to debug production issue',
        24
    );
    $auditLogService->log($user1Id, 'access_request.created', 'access_request', $request1Id);
    
    $request2Id = $accessRequestModel->create(
        $secret2Id,
        $user2Id,
        'Database migration required',
        48
    );
    $auditLogService->log($user2Id, 'access_request.created', 'access_request', $request2Id);
    
    // Approve one request and create grant
    echo "Approving access request and creating grant...\n";
    $accessRequestModel->approve($request1Id, $adminId, 'Approved for production debugging');
    $auditLogService->log($adminId, 'access_request.approved', 'access_request', $request1Id);
    
    $grant1Id = $grantModel->create($secret1Id, $user1Id, $adminId, 24, $request1Id);
    $auditLogService->log($adminId, 'grant.created', 'grant', $grant1Id, "Granted to user {$user1Id} for 24 hours");
    
    // Create a direct grant (without request)
    echo "Creating direct grant...\n";
    $grant2Id = $grantModel->create($secret3Id, $user2Id, $adminId, 72);
    $auditLogService->log($adminId, 'grant.created', 'grant', $grant2Id, "Direct grant to user {$user2Id} for 72 hours");
    
    echo "\n✓ Seeding completed successfully!\n";
    echo "\nTest Credentials:\n";
    echo "  Admin: admin@example.com / admin123\n";
    echo "  User 1: alice@example.com / password123\n";
    echo "  User 2: bob@example.com / password123\n";
    
} catch (Exception $e) {
    echo "\n✗ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
