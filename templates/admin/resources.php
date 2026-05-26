<?php
use App\Security;
use App\Renderer;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resource Management - <?= Security::escapeHtml(APP_NAME) ?></title>
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
    <div class="page-header">
        <h1>Resource Management</h1>
        <button class="btn btn-primary" onclick="showCreateResource()">Upload Document</button>
    </div>

    <div class="section">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Resource ID</th><th>Name</th><th>Type</th><th>Size</th><th>Public</th><th>Custom URL</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><code><?= Security::escapeHtml($r['resource_id']) ?></code></td>
                        <td><?= Security::escapeHtml($r['name']) ?></td>
                        <td><?= Security::escapeHtml($r['mime_type'] ?? '-') ?></td>
                        <td><?= Renderer::formatBytes((int)$r['file_size']) ?></td>
                        <td><?= $r['is_public'] ? 'Yes' : 'No' ?></td>
                        <td><?= $r['custom_url'] ? Security::escapeHtml($r['custom_url']) : '-' ?></td>
                        <td><?= Security::escapeHtml($r['created_at']) ?></td>
                        <td>
                            <a href="/?id=<?= Security::escapeHtml($r['resource_id']) ?>" class="btn btn-sm">View</a>
                            <button class="btn btn-sm" onclick='showEditResource(<?= json_encode($r) ?>)'>Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteResource(<?= (int)$r['id'] ?>, '<?= Security::escapeHtml($r['name']) ?>')">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($resources)): ?>
                    <tr><td colspan="9" class="text-center">No resources yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="resourceModal" class="modal" style="display:none">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Upload Document</h2>
        <form id="resourceForm" enctype="multipart/form-data" onsubmit="return submitResourceForm(event)">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="resource_id" id="formResourceId" value="0">
            <div class="form-group">
                <label for="formName">Name</label>
                <input type="text" id="formName" name="name" required>
            </div>
            <div class="form-group">
                <label for="formResourceIdDisplay">Resource ID (leave empty for auto-generate)</label>
                <input type="text" id="formResourceIdDisplay" name="resource_id" pattern="[a-zA-Z0-9_-]+">
            </div>
            <div class="form-group">
                <label for="formCustomUrl">Custom URL Path</label>
                <input type="text" id="formCustomUrl" name="custom_url" pattern="[a-zA-Z0-9_\/-]+" placeholder="my-document">
                <small>Accessible at: /p/my-document</small>
            </div>
            <div class="form-group">
                <label for="formFile">File</label>
                <input type="file" id="formFile" name="file" id="fileInput">
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" id="formIsPublic" name="is_public" checked>
                    Public (accessible without login)
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateResource() {
    document.getElementById('modalTitle').textContent = 'Upload Document';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formResourceId').value = '0';
    document.getElementById('formName').value = '';
    document.getElementById('formResourceIdDisplay').value = '';
    document.getElementById('formCustomUrl').value = '';
    document.getElementById('formIsPublic').checked = true;
    document.getElementById('fileInput').required = true;
    document.getElementById('resourceModal').style.display = 'flex';
}
function showEditResource(resource) {
    document.getElementById('modalTitle').textContent = 'Edit: ' + resource.name;
    document.getElementById('formAction').value = 'update';
    document.getElementById('formResourceId').value = resource.id;
    document.getElementById('formName').value = resource.name;
    document.getElementById('formResourceIdDisplay').value = resource.resource_id;
    document.getElementById('formResourceIdDisplay').disabled = true;
    document.getElementById('formCustomUrl').value = resource.custom_url || '';
    document.getElementById('formIsPublic').checked = resource.is_public == 1;
    document.getElementById('fileInput').required = false;
    document.getElementById('resourceModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('resourceModal').style.display = 'none';
}
async function submitResourceForm(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    const data = new FormData(form);
    const resp = await fetch('/admin/resources', { method: 'POST', body: data });
    const result = await resp.json();
    btn.disabled = false;
    if (result.success) {
        location.reload();
    } else {
        alert(result.error || 'Operation failed');
    }
    return false;
}
</script>
</body>
</html>