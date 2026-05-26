<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use App\Security;

$redirect = $_GET['redirect'] ?? '/admin';
$csrf = \App\Auth\auth()->generateCsrfToken();
$isLoggedIn = \App\Auth\auth()->isLoggedIn();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?= Security::escapeHtml(APP_NAME) ?></title>
<link rel="stylesheet" href="/static/css/style.css">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-card">
        <h1><?= Security::escapeHtml(APP_NAME) ?></h1>
        <p class="subtitle">Document Viewer & Manager</p>
        <?php if ($isLoggedIn): ?>
            <p>You are already logged in.</p>
            <a href="/admin" class="btn btn-primary">Go to Admin</a>
        <?php else: ?>
        <form id="loginForm" method="post">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="redirect" value="<?= Security::escapeHtml($redirect) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
            <div id="loginError" class="error-message" style="display:none"></div>
        </form>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('loginForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const errorDiv = document.getElementById('loginError');
    errorDiv.style.display = 'none';
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Signing in...';
    try {
        const formData = new FormData(this);
        const resp = await fetch('/login', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            window.location.href = data.redirect || '/admin';
        } else {
            errorDiv.textContent = data.error || 'Login failed';
            errorDiv.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Sign In';
        }
    } catch(e) {
        errorDiv.textContent = 'Network error. Please try again.';
        errorDiv.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Sign In';
    }
});
</script>
</body>
</html>