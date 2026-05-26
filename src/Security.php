<?php
namespace App;

class Security
{
    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^\w\.\-\(\) ]/u', '_', $filename);
        $filename = preg_replace('/\.\.+/', '.', $filename);
        return trim($filename);
    }

    public static function sanitizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $parts = array_filter(explode('/', $path), fn($p) => $p !== '' && $p !== '.');
        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }
        return implode('/', $resolved);
    }

    public static function isPathSafe(string $baseDir, string $targetPath): bool
    {
        $baseReal = realpath($baseDir);
        $targetReal = realpath($targetPath);
        if ($targetReal === false) return false;
        return strpos($targetReal, $baseReal) === 0;
    }

    public static function sanitizeUrl(string $url): string
    {
        $url = preg_replace('/[^\w\-\/\.]/u', '_', $url);
        $url = preg_replace('/\/+/', '/', $url);
        return trim($url, '/');
    }

    public static function escapeHtml(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }

    public static function getSafeMimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'md' => 'text/markdown',
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'csv' => 'text/csv',
            'html' => 'text/html',
            'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'py' => 'text/x-python',
            'php' => 'text/x-php',
            'java' => 'text/x-java',
            'rb' => 'text/x-ruby',
            'go' => 'text/x-go',
            'rs' => 'text/x-rust',
            'yaml' => 'text/yaml',
            'yml' => 'text/yaml',
            'sh' => 'text/x-sh',
            'bat' => 'text/x-bat',
            'sql' => 'text/x-sql',
        ];
        return $mimeMap[$ext] ?? 'application/octet-stream';
    }

    public static function isTextFile(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $textExtensions = ['md', 'txt', 'html', 'htm', 'css', 'js', 'json', 'xml', 'yaml', 'yml', 'ini', 'cfg', 'conf', 'sh', 'bat', 'ps1', 'py', 'rb', 'pl', 'lua', 'php', 'java', 'c', 'cpp', 'h', 'hpp', 'go', 'rs', 'swift', 'kt', 'ts', 'jsx', 'tsx', 'vue', 'scss', 'less', 'sql', 'r', 'm', 'mm', 'log', 'env', 'gitignore', 'dockerfile', 'makefile', 'csv', 'svg'];
        return in_array($ext, $textExtensions);
    }

    public static function isRenderedFile(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, ['md', 'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
    }

    public static function getFileIcon(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $icons = [
            'md' => '📝', 'pdf' => '📄', 'doc' => '📘', 'docx' => '📘',
            'xls' => '📊', 'xlsx' => '📊', 'ppt' => '📙', 'pptx' => '📙',
            'txt' => '📃', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️',
            'gif' => '🖼️', 'svg' => '🖼️', 'zip' => '📦', 'tar' => '📦',
            'gz' => '📦', 'py' => '🐍', 'js' => '📜', 'php' => '🐘',
            'html' => '🌐', 'css' => '🎨', 'json' => '📋', 'xml' => '📋',
            'java' => '☕', 'go' => '🔵', 'rs' => '🦀',
        ];
        return $icons[$ext] ?? '📁';
    }

    public static function generatePassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public static function validateFileExtension(string $filename): bool
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['md', 'txt', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'zip', 'tar', 'gz', 'html', 'htm', 'css', 'js', 'json', 'xml', 'yaml', 'yml', 'csv', 'py', 'rb', 'php', 'java', 'c', 'cpp', 'h', 'hpp', 'go', 'rs', 'swift', 'kt', 'ts', 'sh', 'bat', 'sql', 'log', 'ini', 'cfg', 'conf'];
        return in_array($ext, $allowed);
    }
}