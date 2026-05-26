<?php
define('APP_NAME', 'DocuView');
define('APP_VERSION', '1.0.0');
define('DATA_DIR', __DIR__ . '/data');
define('FILES_DIR', DATA_DIR . '/files');
define('DB_PATH', DATA_DIR . '/app.db');
define('SESSION_LIFETIME', 86400);
define('MAX_FILE_SIZE', 104857600);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('DEFAULT_THEME', 'light');
define('RENDER_CACHE_ENABLED', true);
define('RENDER_CACHE_DIR', DATA_DIR . '/cache');

$allowed_editable_extensions = ['md', 'txt', 'html', 'htm', 'css', 'js', 'json', 'xml', 'yaml', 'yml', 'ini', 'cfg', 'conf', 'sh', 'bat', 'ps1', 'py', 'rb', 'pl', 'lua', 'php', 'java', 'c', 'cpp', 'h', 'hpp', 'go', 'rs', 'swift', 'kt', 'ts', 'jsx', 'tsx', 'vue', 'scss', 'less', 'sql', 'r', 'm', 'mm'];

$rendered_extensions = ['md', 'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
if (!is_dir(FILES_DIR)) mkdir(FILES_DIR, 0755, true);
if (!is_dir(DATA_DIR . '/cache')) mkdir(DATA_DIR . '/cache', 0755, true);