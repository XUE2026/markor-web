<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use App\Auth;
use App\Database;
use App\Router;
use App\Security;
use App\Renderer;

$db = Database::getInstance();
$db->initialize();
$auth = Auth\auth();

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if (strpos($uri, '/static/') === 0) {
    $filePath = __DIR__ . $uri;
    if (file_exists($filePath) && Security::isPathSafe(__DIR__ . '/static', $filePath)) {
        $mime = Security::getSafeMimeType($filePath);
        header("Content-Type: $mime");
        header('Cache-Control: public, max-age=86400');
        readfile($filePath);
        exit;
    }
    http_response_code(404);
    exit;
}

$router = new Router();

$router->get('/', function () {
    $resourceId = $_GET['id'] ?? '';
    if ($resourceId) {
        $resource = Database::getInstance()->fetch('SELECT * FROM resources WHERE resource_id = ?', [$resourceId]);
        if (!$resource) {
            $resource = Database::getInstance()->fetch('SELECT * FROM resources WHERE id = ?', [(int)$resourceId]);
        }
        if ($resource && ($resource['is_public'] || (Auth\auth()->isLoggedIn() && $resource['created_by'] == Auth\auth()->user()['id']))) {
            $filePath = $resource['file_path'];
            if (file_exists($filePath) && Security::isPathSafe(FILES_DIR, $filePath)) {
                Database::getInstance()->logAudit(null, 'VIEW_RESOURCE', 'Viewed resource: ' . $resource['name']);
                $content = renderer()->render($filePath);
                $title = Security::escapeHtml($resource['name']);
                include __DIR__ . '/templates/file-viewer.php';
                return;
            }
        }
    }
    include __DIR__ . '/templates/home.php';
});

$router->get('/r/{resourceId}', function ($params) {
    $resourceId = $params['resourceId'] ?? '';
    Router::redirect("/?id=" . urlencode($resourceId));
});

$router->get('/p/{customUrl}', function ($params) {
    $customUrl = $params['customUrl'] ?? '';
    $resource = Database::getInstance()->fetch('SELECT * FROM resources WHERE custom_url = ?', [$customUrl]);
    if ($resource) {
        Router::redirect("/?id=" . urlencode($resource['resource_id']));
    } else {
        http_response_code(404);
        include __DIR__ . '/templates/404.php';
    }
});

$router->get('/download/{resourceId}', function ($params) {
    $resourceId = $params['resourceId'] ?? '';
    $resource = Database::getInstance()->fetch('SELECT * FROM resources WHERE resource_id = ?', [$resourceId]);
    if (!$resource) {
        $resource = Database::getInstance()->fetch('SELECT * FROM resources WHERE id = ?', [(int)$resourceId]);
    }
    if (!$resource) {
        http_response_code(404);
        exit;
    }
    $filePath = $resource['file_path'];
    if (!file_exists($filePath) || !Security::isPathSafe(FILES_DIR, $filePath)) {
        http_response_code(404);
        exit;
    }
    Database::getInstance()->logAudit(null, 'DOWNLOAD', 'Downloaded: ' . $resource['name']);
    header('Content-Type: ' . Security::getSafeMimeType($filePath));
    header('Content-Disposition: attachment; filename="' . Security::escapeHtml($resource['name']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('X-Accel-Buffering: no');
    readfile($filePath);
    exit;
});

$router->get('/admin', function () use ($auth) {
    $auth->requireAdmin();
    $stats = [
        'users' => Database::getInstance()->fetch('SELECT COUNT(*) as count FROM users')['count'],
        'resources' => Database::getInstance()->fetch('SELECT COUNT(*) as count FROM resources')['count'],
        'public_resources' => Database::getInstance()->fetch('SELECT COUNT(*) as count FROM resources WHERE is_public = 1')['count'],
        'today_logs' => Database::getInstance()->fetch("SELECT COUNT(*) as count FROM access_logs WHERE date(created_at) = date('now')")['count'],
    ];
    $recentLogs = Database::getInstance()->fetchAll('SELECT al.*, u.username FROM access_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 20');
    include __DIR__ . '/templates/admin/dashboard.php';
});

$router->get('/admin/users', function () use ($auth) {
    $auth->requireAdmin();
    $users = Database::getInstance()->fetchAll('SELECT * FROM users ORDER BY created_at DESC');
    include __DIR__ . '/templates/admin/users.php';
});

$router->post('/admin/users', function () use ($auth) {
    $auth->requireAdmin();
    if (!$auth->validateCsrfToken($_POST['_csrf'] ?? '')) {
        Router::jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
    $action = $_POST['action'] ?? '';
    $db = Database::getInstance();

    switch ($action) {
        case 'create':
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
                Router::jsonResponse(['error' => 'Invalid username. Use 3-32 alphanumeric characters.'], 400);
            }
            if (strlen($password) < 8) {
                Router::jsonResponse(['error' => 'Password must be at least 8 characters.'], 400);
            }
            try {
                $db->insert('INSERT INTO users (username, password_hash, email, role, is_active) VALUES (?, ?, ?, ?, 1)', [
                    $username, password_hash($password, PASSWORD_BCRYPT), $email, $role
                ]);
                $db->logAudit($auth->user()['id'], 'USER_CREATED', "Created user: $username");
                Router::jsonResponse(['success' => true, 'message' => 'User created successfully.']);
            } catch (\Exception $e) {
                Router::jsonResponse(['error' => 'Username already exists.'], 400);
            }
            break;
        case 'update':
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = trim($_POST['password'] ?? '');
            $newRole = $_POST['role'] ?? '';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($userId === $auth->user()['id'] && !$isActive) {
                Router::jsonResponse(['error' => 'Cannot deactivate yourself.'], 400);
            }
            if ($newPassword) {
                if (strlen($newPassword) < 8) {
                    Router::jsonResponse(['error' => 'Password must be at least 8 characters.'], 400);
                }
                $db->query('UPDATE users SET role = ?, is_active = ?, password_hash = ? WHERE id = ?', [
                    $newRole, $isActive, password_hash($newPassword, PASSWORD_BCRYPT), $userId
                ]);
            } else {
                $db->query('UPDATE users SET role = ?, is_active = ? WHERE id = ?', [$newRole, $isActive, $userId]);
            }
            $db->logAudit($auth->user()['id'], 'USER_UPDATED', "Updated user ID: $userId");
            Router::jsonResponse(['success' => true, 'message' => 'User updated.']);
            break;
        case 'delete':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId === $auth->user()['id']) {
                Router::jsonResponse(['error' => 'Cannot delete yourself.'], 400);
            }
            $db->query('DELETE FROM users WHERE id = ?', [$userId]);
            $db->logAudit($auth->user()['id'], 'USER_DELETED', "Deleted user ID: $userId");
            Router::jsonResponse(['success' => true, 'message' => 'User deleted.']);
            break;
        default:
            Router::jsonResponse(['error' => 'Unknown action'], 400);
    }
});

$router->get('/admin/resources', function () use ($auth) {
    $auth->requireAdmin();
    $resources = Database::getInstance()->fetchAll('SELECT r.*, u.username as creator_name FROM resources r LEFT JOIN users u ON r.created_by = u.id ORDER BY r.created_at DESC');
    include __DIR__ . '/templates/admin/resources.php';
});

$router->post('/admin/resources', function () use ($auth) {
    $auth->requireAdmin();
    if (!$auth->validateCsrfToken($_POST['_csrf'] ?? '')) {
        Router::jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
    $action = $_POST['action'] ?? '';
    $db = Database::getInstance();

    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $customUrl = trim($_POST['custom_url'] ?? '');
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            $resourceId = trim($_POST['resource_id'] ?? '');
            if (empty($resourceId)) {
                $resourceId = bin2hex(random_bytes(8));
            }
            if (empty($name)) {
                Router::jsonResponse(['error' => 'Name is required.'], 400);
            }
            if (empty($_FILES['file'])) {
                Router::jsonResponse(['error' => 'File is required.'], 400);
            }
            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                Router::jsonResponse(['error' => 'Upload failed with error code: ' . $file['error']], 400);
            }
            if ($file['size'] > MAX_FILE_SIZE) {
                Router::jsonResponse(['error' => 'File exceeds maximum size.'], 400);
            }
            $originalName = Security::sanitizeFilename($file['name']);
            if (!Security::validateFileExtension($originalName)) {
                Router::jsonResponse(['error' => 'File extension not allowed.'], 400);
            }
            $destPath = FILES_DIR . '/' . $resourceId . '_' . $originalName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                Router::jsonResponse(['error' => 'Failed to save file.'], 500);
            }
            try {
                $db->insert('INSERT INTO resources (resource_id, name, file_path, mime_type, file_size, is_public, custom_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [
                    $resourceId, $originalName, $destPath, Security::getSafeMimeType($destPath), $file['size'], $isPublic, $customUrl ?: null, $auth->user()['id']
                ]);
                $db->logAudit($auth->user()['id'], 'RESOURCE_CREATED', "Created resource: $originalName (ID: $resourceId)");
                Router::jsonResponse(['success' => true, 'message' => 'Resource created.', 'resource_id' => $resourceId]);
            } catch (\Exception $e) {
                @unlink($destPath);
                Router::jsonResponse(['error' => 'Resource ID already exists.'], 400);
            }
            break;
        case 'update':
            $resId = (int)($_POST['resource_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $customUrl = trim($_POST['custom_url'] ?? '');
            $isPublic = isset($_POST['is_public']) ? 1 : 0;
            if (empty($name)) Router::jsonResponse(['error' => 'Name is required.'], 400);
            $db->query('UPDATE resources SET name = ?, is_public = ?, custom_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                $name, $isPublic, $customUrl ?: null, $resId
            ]);
            if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file'];
                if ($file['size'] <= MAX_FILE_SIZE && Security::validateFileExtension($file['name'])) {
                    $originalName = Security::sanitizeFilename($file['name']);
                    $resource = $db->fetch('SELECT * FROM resources WHERE id = ?', [$resId]);
                    if ($resource && file_exists($resource['file_path'])) {
                        @unlink($resource['file_path']);
                    }
                    $destPath = FILES_DIR . '/' . $resource['resource_id'] . '_' . $originalName;
                    move_uploaded_file($file['tmp_name'], $destPath);
                    $db->query('UPDATE resources SET file_path = ?, mime_type = ?, file_size = ?, name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [
                        $destPath, Security::getSafeMimeType($destPath), $file['size'], $originalName, $resId
                    ]);
                }
            }
            $db->logAudit($auth->user()['id'], 'RESOURCE_UPDATED', "Updated resource ID: $resId");
            Router::jsonResponse(['success' => true, 'message' => 'Resource updated.']);
            break;
        case 'delete':
            $resId = (int)($_POST['resource_id'] ?? 0);
            $resource = $db->fetch('SELECT * FROM resources WHERE id = ?', [$resId]);
            if ($resource && file_exists($resource['file_path'])) {
                @unlink($resource['file_path']);
            }
            $db->query('DELETE FROM resources WHERE id = ?', [$resId]);
            $db->logAudit($auth->user()['id'], 'RESOURCE_DELETED', "Deleted resource ID: $resId");
            Router::jsonResponse(['success' => true, 'message' => 'Resource deleted.']);
            break;
        default:
            Router::jsonResponse(['error' => 'Unknown action'], 400);
    }
});

$router->get('/admin/settings', function () use ($auth) {
    $auth->requireAdmin();
    $settings = Database::getInstance()->fetchAll('SELECT * FROM settings');
    $settingsMap = [];
    foreach ($settings as $s) {
        $settingsMap[$s['key']] = $s['value'];
    }
    include __DIR__ . '/templates/admin/settings.php';
});

$router->post('/admin/settings', function () use ($auth) {
    $auth->requireAdmin();
    if (!$auth->validateCsrfToken($_POST['_csrf'] ?? '')) {
        Router::jsonResponse(['error' => 'Invalid CSRF token'], 403);
    }
    $db = Database::getInstance();
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            $db->query('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)', [$settingKey, $value]);
        }
    }
    $db->logAudit($auth->user()['id'], 'SETTINGS_UPDATED', 'Settings updated');
    Router::jsonResponse(['success' => true, 'message' => 'Settings saved.']);
});

$router->get('/admin/logs', function () use ($auth) {
    $auth->requireAdmin();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 50;
    $offset = ($page - 1) * $perPage;
    $logs = Database::getInstance()->fetchAll(
        "SELECT al.*, u.username FROM access_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );
    $total = Database::getInstance()->fetch('SELECT COUNT(*) as count FROM access_logs')['count'];
    $totalPages = ceil($total / $perPage);
    include __DIR__ . '/templates/admin/logs.php';
});

$router->get('/login', function () {
    if (Auth\auth()->isLoggedIn()) {
        if (Auth\auth()->hasRole('admin')) {
            Router::redirect('/admin');
        }
        Router::redirect('/');
    }
    include __DIR__ . '/templates/login.php';
});

$router->post('/login', function () {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        Router::jsonResponse(['error' => 'Username and password are required.'], 400);
    }
    $result = Auth\auth()->login($username, $password);
    if ($result['success']) {
        $redirect = $_POST['redirect'] ?? '/admin';
        Router::jsonResponse(['success' => true, 'redirect' => $redirect]);
    } else {
        Router::jsonResponse(['error' => $result['message']], 401);
    }
});

$router->get('/logout', function () {
    Auth\auth()->logout();
    Router::redirect('/');
});

$router->get('/api/resources', function () {
    Auth\auth()->requireAuth();
    $resources = Database::getInstance()->fetchAll('SELECT id, resource_id, name, mime_type, file_size, is_public, custom_url, created_at FROM resources ORDER BY created_at DESC');
    Router::jsonResponse(['data' => $resources]);
});

$router->get('/api/stats', function () {
    Auth\auth()->requireAuth();
    $db = Database::getInstance();
    Router::jsonResponse([
        'data' => [
            'users' => $db->fetch('SELECT COUNT(*) as count FROM users')['count'],
            'resources' => $db->fetch('SELECT COUNT(*) as count FROM resources')['count'],
            'today_logs' => $db->fetch("SELECT COUNT(*) as count FROM access_logs WHERE date(created_at) = date('now')")['count'],
        ]
    ]);
});

$router->dispatch($method, $uri);