<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use App\Auth;
use App\Database;
use App\Security;

$auth = Auth\auth();

if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$response = ['status' => 'ok', 'served_by' => gethostname()];

$modules = [
    'pdo_sqlite' => extension_loaded('pdo_sqlite'),
    'mbstring' => extension_loaded('mbstring'),
    'gd' => extension_loaded('gd'),
    'zip' => extension_loaded('zip'),
    'fileinfo' => extension_loaded('fileinfo'),
    'intl' => extension_loaded('intl'),
    'json' => extension_loaded('json'),
    'xml' => extension_loaded('xml'),
];
$response['extensions'] = $modules;
$response['all_extensions_loaded'] = !in_array(false, $modules);

$response['db_size'] = file_exists(DB_PATH) ? filesize(DB_PATH) : 0;
$response['users_count'] = Database::getInstance()->fetch('SELECT COUNT(*) as c FROM users')['c'];
$response['resources_count'] = Database::getInstance()->fetch('SELECT COUNT(*) as c FROM resources')['c'];

echo json_encode($response, JSON_PRETTY_PRINT);