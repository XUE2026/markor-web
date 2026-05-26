<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
use App\Security;

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$auth = \App\Auth\auth();

if ($auth->isLoggedIn() && $auth->hasRole('admin')) {
    $allowedPaths = ['/admin/', '/admin/users', '/admin/resources', '/admin/settings', '/admin/logs'];
    $isAdminPath = false;
    foreach ($allowedPaths as $p) {
        if (strpos($requestUri, $p) === 0) $isAdminPath = true;
    }
    if ($requestUri === '/admin' || $requestUri === '/admin/' || $requestUri === '/admin/index.php') {
        header('Location: /admin');
        exit;
    }
}

if (strpos($requestUri, '/admin') === 0) {
    require_once __DIR__ . '/../index.php';
    exit;
}

http_response_code(404);
echo '<h1>404 - Not Found</h1>';
echo '<p>Admin page not found.</p>';
echo '<a href="/admin">Back to Admin</a>';