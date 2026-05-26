<?php
use App\Security;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Security::escapeHtml($title ?? 'File Viewer') ?> - <?= Security::escapeHtml(APP_NAME) ?></title>
<link rel="stylesheet" href="/static/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a href="/" class="nav-brand"><?= Security::escapeHtml(APP_NAME) ?></a>
        <div class="nav-links">
            <a href="/">Home</a>
            <?php if ($auth->isLoggedIn()): ?>
                <a href="/admin">Admin</a>
                <a href="/logout">Logout (<?= Security::escapeHtml($auth->user()['username']) ?>)</a>
            <?php else: ?>
                <a href="/login">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container">
    <div class="file-viewer-header">
        <h1><?= Security::escapeHtml($title ?? 'File') ?></h1>
        <div class="file-actions">
            <a href="/download/<?= Security::escapeHtml($resource['resource_id'] ?? '') ?>" class="btn btn-secondary">Download</a>
            <?php if ($auth->isLoggedIn() && $auth->hasRole('admin')): ?>
            <button onclick="location.href='/admin/resources'" class="btn btn-secondary">Manage</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="file-viewer">
        <?= $content ?? '<p>No content available.</p>' ?>
    </div>
</main>
<footer class="footer">
    <div class="footer-inner">
        <span><?= Security::escapeHtml(APP_NAME) ?> v<?= Security::escapeHtml(APP_VERSION) ?></span>
    </div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>
<script>
function copyCode(btn) {
    const code = btn.closest('.code-content').querySelector('code');
    if (!code) return;
    const text = code.textContent;
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = 'Copy'; }, 2000);
    });
}
</script>
</body>
</html>