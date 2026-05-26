<?php
namespace App;

class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $this->pdo = new \PDO('sqlite:' . DB_PATH, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA foreign_keys=ON');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function initialize(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                email TEXT,
                role TEXT NOT NULL DEFAULT "user",
                is_active INTEGER NOT NULL DEFAULT 1,
                permissions TEXT DEFAULT "{}",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            )
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS resources (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                resource_id TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                file_path TEXT NOT NULL,
                mime_type TEXT,
                file_size INTEGER DEFAULT 0,
                is_public INTEGER NOT NULL DEFAULT 1,
                allowed_roles TEXT DEFAULT "[]",
                allowed_users TEXT DEFAULT "[]",
                custom_url TEXT UNIQUE,
                download_enabled INTEGER NOT NULL DEFAULT 1,
                created_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS access_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                resource_id INTEGER,
                user_id INTEGER,
                ip_address TEXT,
                user_agent TEXT,
                action TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (resource_id) REFERENCES resources(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            )
        ');

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $stmt->execute(['admin']);
        if ($stmt->fetchColumn() == 0) {
            $this->createDefaultAdmin();
        }
    }

    private function createDefaultAdmin(): void
    {
        $password = bin2hex(random_bytes(8));
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password_hash, email, role, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute(['admin', $hash, 'admin@docusite.local', 'admin']);

        $configFile = DATA_DIR . '/admin_credentials.txt';
        file_put_contents($configFile, "Admin credentials created at: " . date('Y-m-d H:i:s') . "\nUsername: admin\nPassword: $password\nIMPORTANT: Change this password immediately!\n");
        chmod($configFile, 0600);
        $this->logAudit(0, 'SYSTEM', 'Default admin account created');
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    public function logAudit(?int $userId, string $action, string $details = ''): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->insert(
            'INSERT INTO access_logs (resource_id, user_id, ip_address, user_agent, action) VALUES (?, ?, ?, ?, ?)',
            [$userId, auth()->isLoggedIn() ? auth()->user()['id'] : null, $ip, $ua, $action . ': ' . $details]
        );
    }
}