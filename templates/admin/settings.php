<?php
use App\Security;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
$defaultSettings = [
    'site_name' => APP_NAME,
    'site_description' => 'Document and Code Viewer',
    'max_upload_size' => '100',
    'allow_registration' => '0',
    'default_user_role' => 'user',
    'maintenance_mode' => '0',
];
foreach ($defaultSettings as $key => $val) {
    if (!isset($settingsMap[$key])) $settingsMap[$key] = $val;
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - <?= Security::escapeHtml(APP_NAME) ?></title>
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
    <h1>Settings</h1>
    <div class="section">
        <form id="settingsForm" onsubmit="return submitSettings(event)">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div class="form-group">
                <label for="setting_site_name">Site Name</label>
                <input type="text" id="setting_site_name" name="setting_site_name" value="<?= Security::escapeHtml($settingsMap['site_name']) ?>">
            </div>
            <div class="form-group">
                <label for="setting_site_description">Site Description</label>
                <textarea id="setting_site_description" name="setting_site_description"><?= Security::escapeHtml($settingsMap['site_description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="setting_max_upload_size">Max Upload Size (MB)</label>
                <input type="number" id="setting_max_upload_size" name="setting_max_upload_size" value="<?= (int)$settingsMap['max_upload_size'] ?>" min="1" max="500">
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" id="setting_allow_registration" name="setting_allow_registration" value="1" <?= $settingsMap['allow_registration'] === '1' ? 'checked' : '' ?>>
                    Allow User Registration
                </label>
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" id="setting_maintenance_mode" name="setting_maintenance_mode" value="1" <?= $settingsMap['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                    Maintenance Mode
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</main>
<script>
async function submitSettings(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    const data = new FormData(e.target);
    document.querySelectorAll('#settingsForm input[type="checkbox"]:not(:checked)').forEach(cb => {
        if (cb.value === '1') data.append(cb.name, '0');
    });
    const resp = await fetch('/admin/settings', { method: 'POST', body: data });
    const result = await resp.json();
    btn.disabled = false;
    if (result.success) {
        alert('Settings saved successfully.');
    } else {
        alert(result.error || 'Failed to save settings.');
    }
    return false;
}
</script>
</body>
</html>