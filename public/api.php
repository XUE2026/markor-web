<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Markor\FileStorage;
use Markor\DatabaseStorage;
use Erusev\Parsedown\Parsedown;

$config = require __DIR__ . '/../config.php';

$storage = null;
$storageMode = $config['storage_mode'] ?? 'file';

if ($storageMode === 'database') {
    $dbConfig = $config['database'];
    $storage = new DatabaseStorage($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
} else {
    $fileConfig = $config['file'];
    $storage = new FileStorage($fileConfig['data_dir']);
}

$parsedown = new Parsedown();
$parsedown->setSafeMode(true);

$mode = $config['app']['mode'] ?? 'full';
$targetFile = $config['app']['target_file'] ?? '';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

if ($method === 'GET' && $path === 'files') {
    $files = $storage->listFiles();
    echo json_encode($files);
    exit;
}

if ($method === 'GET' && preg_match('#^file/([^/]+)$#', $path, $matches)) {
    $filename = $matches[1];
    
    if ($targetFile && $targetFile !== $filename) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $file = $storage->getFile($filename);
    if ($file) {
        echo json_encode([
            'name' => $file['name'],
            'content' => $file['content'],
            'html' => $parsedown->text($file['content']),
            'modified' => $file['modified'] ?? null
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
    }
    exit;
}

if ($method === 'POST' && preg_match('#^file/([^/]+)$#', $path, $matches)) {
    if ($mode !== 'full' && $mode !== 'write') {
        http_response_code(403);
        echo json_encode(['error' => 'Write access denied']);
        exit;
    }
    
    $filename = $matches[1];
    
    if ($targetFile && $targetFile !== $filename) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $content = $input['content'] ?? '';
    
    if ($storage->saveFile($filename, $content)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
    }
    exit;
}

if ($method === 'DELETE' && preg_match('#^file/([^/]+)$#', $path, $matches)) {
    if ($mode !== 'full') {
        http_response_code(403);
        echo json_encode(['error' => 'Delete access denied']);
        exit;
    }
    
    $filename = $matches[1];
    
    if ($targetFile && $targetFile !== $filename) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    if ($storage->deleteFile($filename)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete file']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
