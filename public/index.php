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
$appTitle = $config['app']['title'] ?? 'Markor Web';

$action = $_GET['action'] ?? 'index';
$filename = $_GET['file'] ?? '';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appTitle) ?></title>
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?= htmlspecialchars($appTitle) ?></h1>
            <nav>
                <a href="/">编辑器</a>
                <span class="mode-indicator">模式: <?= htmlspecialchars($mode) ?></span>
            </nav>
        </header>

        <main>
            <?php if ($mode === 'preview'): ?>
                <div class="preview-container">
                    <?php
                    if ($filename && $targetFile === $filename) {
                        $file = $storage->getFile($filename);
                        if ($file) {
                            echo '<h2>' . htmlspecialchars($file['name']) . '</h2>';
                            echo $parsedown->text($file['content']);
                        }
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="editor-container">
                    <aside class="sidebar">
                        <h3>文件列表</h3>
                        <div id="file-list">
                            <?php
                            $files = $storage->listFiles();
                            foreach ($files as $file):
                            ?>
                                <div class="file-item" onclick="loadFile('<?= htmlspecialchars($file['name']) ?>')">
                                    <?= htmlspecialchars($file['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </aside>

                    <section class="editor-main">
                        <div class="editor-header">
                            <input type="text" id="current-file" placeholder="文件名" value="">
                            <?php if ($mode === 'full' || $mode === 'write'): ?>
                                <button onclick="saveFile()" class="btn btn-success">保存</button>
                            <?php endif; ?>
                        </div>

                        <div class="editor-content">
                            <?php if ($mode === 'full' || $mode === 'read'): ?>
                                <div class="editor-pane">
                                    <textarea id="editor" placeholder="在这里输入 Markdown..."></textarea>
                                </div>
                            <?php endif; ?>
                            <div class="preview-pane">
                                <div id="preview" class="markdown-body"></div>
                            </div>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; 2024 <?= htmlspecialchars($appTitle) ?>. 基于 Markor 项目。</p>
        </footer>
    </div>

    <script src="/static/js/app.js"></script>
    <script>
        const mode = '<?= htmlspecialchars($mode) ?>';
        const targetFile = '<?= htmlspecialchars($targetFile) ?>';
    </script>
</body>
</html>
