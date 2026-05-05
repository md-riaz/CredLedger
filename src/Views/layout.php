<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'CredLedger-Lite') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        header { background: #2c3e50; color: white; padding: 1rem 0; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        header h1 { font-size: 1.5rem; }
        nav a { color: white; text-decoration: none; margin-left: 1.5rem; padding: 0.5rem 1rem; border-radius: 4px; transition: background 0.2s; }
        nav a:hover { background: rgba(255,255,255,0.1); }
        nav a.active { background: rgba(255,255,255,0.2); }
        .card { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card h2 { margin-bottom: 1rem; color: #2c3e50; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 1rem; transition: background 0.2s; }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-small { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
        input, textarea, select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
        textarea { resize: vertical; min-height: 80px; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 3px; text-transform: uppercase; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .text-muted { color: #6c757d; font-size: 0.875rem; }
        .user-info { display: flex; align-items: center; gap: 0.5rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🔐 CredLedger-Lite</h1>
            <?php if (isset($currentUser)): ?>
            <nav>
                <span style="color: #ecf0f1; margin-right: 1rem;">
                    <?= htmlspecialchars($currentUser['name']) ?>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <span class="badge badge-info">Admin</span>
                    <?php endif; ?>
                </span>
                <a href="/dashboard.php">Dashboard</a>
                <a href="/secrets.php">Secrets</a>
                <a href="/grants.php">My Grants</a>
                <a href="/profile.php">Profile</a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="/admin.php">Admin</a>
                <?php endif; ?>
                <a href="/logout.php">Logout</a>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($info)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>
        
        <?= $content ?? '' ?>
    </div>
</body>
</html>
