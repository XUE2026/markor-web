<?php

namespace Markor;

class DatabaseStorage implements StorageInterface
{
    private $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new \PDO($dsn, $username, $password);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->initTables();
    }

    private function initTables()
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL UNIQUE,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_filename (filename)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;
        
        $this->pdo->exec($sql);
    }

    public function listFiles(): array
    {
        $stmt = $this->pdo->query("SELECT filename AS name, LENGTH(content) AS size, updated_at AS modified FROM files ORDER BY filename");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getFile(string $filename): ?array
    {
        $stmt = $this->pdo->prepare("SELECT filename AS name, content, updated_at AS modified FROM files WHERE filename = ?");
        $stmt->execute([$filename]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function saveFile(string $filename, string $content): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO files (filename, content) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE 
            content = VALUES(content), 
            updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$filename, $content]);
    }

    public function deleteFile(string $filename): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM files WHERE filename = ?");
        return $stmt->execute([$filename]);
    }
}
