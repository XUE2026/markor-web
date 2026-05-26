<?php
use App\Security;
$auth = \App\Auth\auth();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Security::escapeHtml($title ?? APP_NAME) ?> - <?= Security::escapeHtml(APP_NAME) ?></title>
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
    <?= $content ?? '' ?>
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
function deleteResource(id, name) {
    if (!confirm('Delete "' + name + '"?')) return;
    const form = new FormData();
    form.append('_csrf', '<?= $auth->generateCsrfToken() ?>');
    form.append('action', 'delete');
    form.append('resource_id', id);
    fetch('/admin/resources', { method: 'POST', body: form }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload(); else alert(d.error);
    });
}
function deleteUser(id, name) {
    if (!confirm('Delete user "' + name + '"?')) return;
    const form = new FormData();
    form.append('_csrf', '<?= $auth->generateCsrfToken() ?>');
    form.append('action', 'delete');
    form.append('user_id', id);
    fetch('/admin/users', { method: 'POST', body: form }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload(); else alert(d.error);
    });
}
</script>
</body>
</html>