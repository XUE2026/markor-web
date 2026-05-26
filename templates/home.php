<?php
use App\Security;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
$resources = \App\Database::getInstance()->fetchAll('SELECT * FROM resources WHERE is_public = 1 OR created_by = ? ORDER BY created_at DESC', [$auth->isLoggedIn() ? $auth->user()['id'] : -1]);
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Security::escapeHtml(APP_NAME) ?> - Document Viewer</title>
<link rel="stylesheet" href="/static/css/style.css">
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
    <div class="hero">
        <h1><?= Security::escapeHtml(APP_NAME) ?></h1>
        <p>Document and Code Viewer. Upload, manage, and share files with ease.</p>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search documents..." onkeyup="filterResources()">
        </div>
    </div>
    <div class="resource-grid" id="resourceGrid">
        <?php if (empty($resources)): ?>
        <div class="empty-state">
            <p>No documents available yet.</p>
            <?php if ($auth->isLoggedIn()): ?>
                <a href="/admin/resources" class="btn btn-primary">Upload Your First Document</a>
            <?php else: ?>
                <a href="/login" class="btn btn-primary">Login to Upload</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach ($resources as $res): ?>
        <a href="/?id=<?= Security::escapeHtml($res['resource_id']) ?>" class="resource-card">
            <div class="card-icon"><?= \App\Security::getFileIcon($res['file_path']) ?></div>
            <div class="card-info">
                <div class="card-title"><?= Security::escapeHtml($res['name']) ?></div>
                <div class="card-meta"><?= \App\Renderer::formatBytes($res['file_size']) ?></div>
            </div>
            <div class="card-badge <?= $res['is_public'] ? 'public' : 'private' ?>">
                <?= $res['is_public'] ? 'Public' : 'Private' ?>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
<footer class="footer">
    <div class="footer-inner">
        <span><?= Security::escapeHtml(APP_NAME) ?> v<?= Security::escapeHtml(APP_VERSION) ?></span>
    </div>
</footer>
<script>
function filterResources() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.resource-card').forEach(card => {
        const title = card.querySelector('.card-title')?.textContent?.toLowerCase() || '';
        card.style.display = title.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>