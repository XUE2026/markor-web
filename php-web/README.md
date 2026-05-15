# Markor Web - PHP Edition

这是 Markor 的 PHP Web 实现版本。

## 功能特性

- 完整的 Markor Web 编辑器
- 支持多种存储模式（文件系统和数据库）
- 支持多种运行模式（完整、只读、只写、预览）
- Markdown 实时预览
- 文件管理功能
- 与原版 Markor 一致的样式

## 系统要求

- PHP >= 7.4
- Composer
- PDO 扩展（用于数据库存储）
- MySQL 或 SQLite（用于数据库存储模式）

## 安装与运行

### 1. 安装依赖

```bash
composer install
```

### 2. 配置

编辑 `config.php` 文件以配置应用：

```php
return [
    'storage_mode' => 'file',  // 'file' 或 'database'
    
    'file' => [
        'data_dir' => __DIR__ . '/data',
    ],
    
    'database' => [
        'dsn' => 'mysql:host=localhost;dbname=markor',
        'username' => 'root',
        'password' => '',
    ],
    
    'app' => [
        'mode' => 'full',  // full, read, write, preview
        'target_file' => '',
        'title' => 'Markor Web - PHP Edition',
    ],
];
```

### 3. 使用环境变量

也可以通过环境变量配置：

```bash
export STORAGE_MODE=file
export DATA_DIR=/path/to/data
export APP_MODE=full
```

### 4. 运行应用

使用 PHP 内置服务器：

```bash
php -S localhost:8000 -t public
```

或使用 Apache/Nginx 配置虚拟主机指向 `public` 目录。

### 5. 访问应用

打开浏览器访问：`http://localhost:8000`

## 运行模式

- `full`: 完整功能模式
- `read`: 只读模式
- `write`: 只写模式
- `preview`: 仅预览模式

## 存储模式

### 文件存储（默认）

默认使用文件系统存储，所有文件保存在 `data` 目录。

### 数据库存储

需要配置 MySQL 数据库：

1. 创建数据库
2. 修改 `config.php` 中的数据库配置
3. 设置 `STORAGE_MODE=database`

## API 接口

应用提供 RESTful API：

- `GET /api.php?path=files` - 获取文件列表
- `GET /api.php?path=file/{filename}` - 获取文件内容
- `POST /api.php?path=file/{filename}` - 保存文件
- `DELETE /api.php?path=file/{filename}` - 删除文件

## 技术栈

- PHP 7.4+
- Composer
- Parsedown (Markdown 解析)
- 原生 PHP (无框架依赖)
