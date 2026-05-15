<?php

return [
    // 存储模式: 'file' 或 'database'
    'storage_mode' => getenv('STORAGE_MODE') ?: 'file',
    
    // 文件存储配置
    'file' => [
        'data_dir' => getenv('DATA_DIR') ?: __DIR__ . '/data',
    ],
    
    // 数据库存储配置
    'database' => [
        'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=markor',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    
    // 应用配置
    'app' => [
        'mode' => getenv('APP_MODE') ?: 'full', // full, read, write, preview
        'target_file' => getenv('TARGET_FILE') ?: '',
        'title' => 'Markor Web - PHP Edition',
    ],
];
