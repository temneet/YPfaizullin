<?php
// config/security.php - Централизованная конфигурация безопасности

class Security {
    private static $instance = null;
    private $db;
    private $csrf_tokens = [];
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Генерация CSRF токена
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Проверка CSRF токена
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->logSecurityEvent('CSRF token mismatch', $_SERVER['REMOTE_ADDR']);
            return false;
        }
        return true;
    }
    
    // Защита от SQL инъекций (уже используется PDO)
    
    // Защита от XSS
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    // Валидация email
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Валидация пароля
    public function validatePassword($password) {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }
    
    // Логирование событий безопасности
    public function logSecurityEvent($event, $ip, $user_id = null) {
        $stmt = $this->db->prepare("
            INSERT INTO security_logs (event, ip_address, user_id, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$event, $ip, $user_id]);
    }
    
    // Ограничение количества попыток входа
    public function checkLoginAttempts($ip) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM security_logs 
            WHERE event = 'failed_login' 
            AND ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['attempts'] < 5;
    }
    
    // Генерация безопасного пароля
    public function generateSecurePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    // Шифрование данных (AES-256)
    public function encrypt($data, $key) {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    // Дешифрование данных
    public function decrypt($data, $key) {
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    // Установка безопасных заголовков
    public function setSecurityHeaders() {
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
    
    // Защита от брутфорса
    public function rateLimit($key, $limit = 10, $window = 60) {
        $redis = new Redis(); // Если используется Redis
        $current = $redis->get($key);
        if ($current && $current >= $limit) {
            return false;
        }
        $redis->incr($key);
        $redis->expire($key, $window);
        return true;
    }
}

// Функции для глобального использования
function sanitize($data) {
    return Security::getInstance()->sanitizeInput($data);
}

function csrf_token() {
    return Security::getInstance()->generateCSRFToken();
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::getInstance()->verifyCSRFToken($token)) {
            die('CSRF token validation failed');
        }
    }
}
?>