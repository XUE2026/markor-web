<?php
use App\Security;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - <?= Security::escapeHtml(APP_NAME) ?></title>
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a href="/" class="nav-brand"><?= Security::escapeHtml(APP_NAME) ?></a>
        <div class="nav-links">
            <a href="/">Home</a>
            <a href="/admin">Dashboard</a>
            <a href="/admin/users">Users</a>
            <a href="/admin/resources">Resources</a>
            <a href="/admin/settings">Settings</a>
            <a href="/admin/logs">Logs</a>
            <a href="/logout">Logout (<?= Security::escapeHtml($auth->user()['username']) ?>)</a>
        </div>
    </div>
</nav>
<main class="container">
    <h1>Admin Dashboard</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= (int)$stats['users'] ?></div>
            <div class="stat-label">Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int)$stats['resources'] ?></div>
            <div class="stat-label">Resources</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int)$stats['public_resources'] ?></div>
            <div class="stat-label">Public Documents</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int)$stats['today_logs'] ?></div>
            <div class="stat-label">Today's Access</div>
        </div>
    </div>

    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="/admin/resources" class="btn btn-primary">Upload Document</a>
            <a href="/admin/users" class="btn btn-secondary">Manage Users</a>
        </div>
    </div>

    <div class="section">
        <h2>Recent Activity</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Time</th><th>User</th><th>Action</th><th>IP</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><?= Security::escapeHtml($log['created_at']) ?></td>
                        <td><?= Security::escapeHtml($log['username'] ?? 'System') ?></td>
                        <td><?= Security::escapeHtml($log['action']) ?></td>
                        <td><?= Security::escapeHtml($log['ip_address'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="4" class="text-center">No activity yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>