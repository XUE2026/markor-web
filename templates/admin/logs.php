<?php
use App\Security;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access Logs - <?= Security::escapeHtml(APP_NAME) ?></title>
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
    <h1>Access Logs</h1>
    <div class="section">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>Time</th><th>User</th><th>Action</th><th>IP</th><th>User Agent</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= Security::escapeHtml($log['created_at']) ?></td>
                        <td><?= Security::escapeHtml($log['username'] ?? '-') ?></td>
                        <td><?= Security::escapeHtml($log['action']) ?></td>
                        <td><?= Security::escapeHtml($log['ip_address'] ?? '-') ?></td>
                        <td class="ua-cell"><?= Security::escapeHtml(mb_substr($log['user_agent'] ?? '', 0, 80)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center">No logs yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages && $i <= 20; $i++): ?>
                <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>