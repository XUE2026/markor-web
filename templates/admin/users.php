<?php
use App\Security;
$auth = \App\Auth\auth();
$csrf = $auth->generateCsrfToken();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management - <?= Security::escapeHtml(APP_NAME) ?></title>
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
        <h1>User Management</h1>
        <button class="btn btn-primary" onclick="showCreateUser()">Create User</button>
    </div>

    <div class="section">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Active</th><th>Created</th><th>Last Login</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= Security::escapeHtml($u['username']) ?></td>
                        <td><?= Security::escapeHtml($u['email'] ?? '-') ?></td>
                        <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : 'info' ?>"><?= Security::escapeHtml($u['role']) ?></span></td>
                        <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
                        <td><?= Security::escapeHtml($u['created_at']) ?></td>
                        <td><?= Security::escapeHtml($u['last_login'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm" onclick='showEditUser(<?= json_encode($u) ?>)'>Edit</button>
                            <?php if ((int)$u['id'] !== (int)$auth->user()['id']): ?>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(<?= (int)$u['id'] ?>, '<?= Security::escapeHtml($u['username']) ?>')">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="userModal" class="modal" style="display:none">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Create User</h2>
        <form id="userForm" onsubmit="return submitUserForm(event)">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="user_id" id="formUserId" value="0">
            <div class="form-group">
                <label for="formUsername">Username</label>
                <input type="text" id="formUsername" name="username" required pattern="[a-zA-Z0-9_]{3,32}">
            </div>
            <div class="form-group">
                <label for="formEmail">Email</label>
                <input type="email" id="formEmail" name="email">
            </div>
            <div class="form-group">
                <label for="formPassword">Password</label>
                <input type="password" id="formPassword" name="password" minlength="8">
                <small>Leave empty to keep current password when editing.</small>
            </div>
            <div class="form-group">
                <label for="formRole">Role</label>
                <select id="formRole" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group checkbox-group" id="isActiveGroup" style="display:none">
                <label>
                    <input type="checkbox" id="formIsActive" name="is_active" checked>
                    Active
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
function showCreateUser() {
    document.getElementById('modalTitle').textContent = 'Create User';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formUserId').value = '0';
    document.getElementById('formUsername').value = '';
    document.getElementById('formUsername').disabled = false;
    document.getElementById('formEmail').value = '';
    document.getElementById('formPassword').required = true;
    document.getElementById('formPassword').value = '';
    document.getElementById('formRole').value = 'user';
    document.getElementById('isActiveGroup').style.display = 'none';
    document.getElementById('userModal').style.display = 'flex';
}
function showEditUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User: ' + user.username;
    document.getElementById('formAction').value = 'update';
    document.getElementById('formUserId').value = user.id;
    document.getElementById('formUsername').value = user.username;
    document.getElementById('formUsername').disabled = true;
    document.getElementById('formEmail').value = user.email || '';
    document.getElementById('formPassword').required = false;
    document.getElementById('formPassword').value = '';
    document.getElementById('formRole').value = user.role;
    document.getElementById('formIsActive').checked = user.is_active == 1;
    document.getElementById('isActiveGroup').style.display = 'block';
    document.getElementById('userModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}
async function submitUserForm(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    const data = new FormData(form);
    const resp = await fetch('/admin/users', { method: 'POST', body: data });
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