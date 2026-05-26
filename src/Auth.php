<?php
namespace App;

class Auth
{
    private ?array $user = null;
    private bool $authenticated = false;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->checkSession();
    }

    private function checkSession(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $token = $_SESSION['auth_token'] ?? null;

        if ($userId && $token) {
            $user = Database::getInstance()->fetch('SELECT * FROM users WHERE id = ? AND is_active = 1', [$userId]);
            if ($user) {
                $expectedToken = hash('sha256', $user['password_hash'] . session_id());
                if (hash_equals($expectedToken, $token)) {
                    $this->user = $user;
                    $this->authenticated = true;
                }
            }
        }
    }

    public function login(string $username, string $password): array
    {
        $db = Database::getInstance();

        $lockoutKey = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attemptsFile = DATA_DIR . "/$lockoutKey.lock";
        $attempts = 0;
        $lockoutTime = 0;

        if (file_exists($attemptsFile)) {
            $data = json_decode(file_get_contents($attemptsFile), true) ?? [];
            if (isset($data['time']) && (time() - $data['time']) < LOGIN_LOCKOUT_TIME) {
                $attempts = $data['attempts'] ?? 0;
                $lockoutTime = $data['time'] ?? 0;
            } else {
                @unlink($attemptsFile);
            }
        }

        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $remaining = LOGIN_LOCKOUT_TIME - (time() - $lockoutTime);
            return ['success' => false, 'message' => "Account temporarily locked. Try again in " . ceil($remaining / 60) . " minutes."];
        }

        $user = $db->fetch('SELECT * FROM users WHERE username = ? AND is_active = 1', [$username]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts++;
            file_put_contents($attemptsFile, json_encode(['attempts' => $attempts, 'time' => time()]));
            $db->logAudit(null, 'LOGIN_FAILED', "Failed login attempt for user: $username");
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        @unlink($attemptsFile);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['auth_token'] = hash('sha256', $user['password_hash'] . session_id());
        $_SESSION['created'] = time();

        $db->query('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?', [$user['id']]);
        $db->logAudit($user['id'], 'LOGIN_SUCCESS', "User $username logged in");

        $this->user = $user;
        $this->authenticated = true;

        return ['success' => true, 'user' => $user];
    }

    public function logout(): void
    {
        if ($this->authenticated && $this->user) {
            Database::getInstance()->logAudit($this->user['id'], 'LOGOUT', 'User logged out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->authenticated = false;
        $this->user = null;
    }

    public function isLoggedIn(): bool
    {
        return $this->authenticated;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function hasRole(string $role): bool
    {
        return $this->authenticated && $this->user && $this->user['role'] === $role;
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->authenticated || !$this->user) return false;
        if ($this->user['role'] === 'admin') return true;
        $perms = json_decode($this->user['permissions'] ?? '{}', true);
        return $perms[$permission] ?? false;
    }

    public function requireAuth(): void
    {
        if (!$this->authenticated) {
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->hasRole('admin')) {
            http_response_code(403);
            die('Access denied. Admin privileges required.');
        }
    }

    public function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

function auth(): Auth
{
    static $instance = null;
    if ($instance === null) {
        $instance = new Auth();
    }
    return $instance;
}