<?php
/**
 * Session Management and Authentication System
 * Secure session handling with CSRF protection and rate limiting
 */

// Start session with secure configuration
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // Set to 1 for HTTPS
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

require_once '../models/Database.php';

class Auth {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutTime = 900; // 15 minutes in seconds
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user with username/email and password
     */
    public function login($username, $password, $rememberMe = false) {
        try {
            // Check rate limiting
            if ($this->isRateLimited($username)) {
                throw new Exception('Too many login attempts. Please try again later.');
            }
            
            // Get user by username or email
            $user = $this->getUserByUsernameOrEmail($username);
            
            if (!$user) {
                $this->logFailedAttempt($username);
                throw new Exception('Invalid credentials');
            }
            
            // Check if user is active
            if (!$user['is_active']) {
                throw new Exception('Account is disabled');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->logFailedAttempt($username);
                throw new Exception('Invalid credentials');
            }
            
            // Clear failed attempts
            $this->clearFailedAttempts($username);
            
            // Create session
            $this->createSession($user, $rememberMe);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Log successful login
            $this->logActivity($user['id'], 'login', 'User logged in successfully');
            
            return [
                'success' => true,
                'user' => $this->sanitizeUserData($user),
                'message' => 'Login successful'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        // Check session
        if (isset($_SESSION['user_id']) && isset($_SESSION['csrf_token'])) {
            // Validate session integrity
            if ($this->validateSession()) {
                return true;
            }
        }
        
        // Check remember me token
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        $user = $this->getUserById($userId);
        
        return $user ? $this->sanitizeUserData($user) : null;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check user permission
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Define role permissions
        $permissions = [
            'admin' => ['*'], // Admin has all permissions
            'technician' => [
                'equipment.view', 'equipment.create', 'equipment.update', 'equipment.move',
                'maintenance.view', 'maintenance.create', 'maintenance.update',
                'incidents.view', 'incidents.create', 'incidents.update'
            ],
            'teacher' => [
                'equipment.view', 'equipment.assign', 'equipment.return',
                'sessions.create', 'sessions.view', 'usage.log'
            ],
            'student' => [
                'equipment.view', 'usage.log'
            ]
        ];
        
        $userPermissions = $permissions[$user['role']] ?? [];
        
        return in_array('*', $userPermissions) || in_array($permission, $userPermissions);
    }
    
    /**
     * Rate limiting check
     */
    private function isRateLimited($identifier) {
        $attempts = $this->getFailedAttempts($identifier);
        
        if ($attempts['count'] >= $this->maxLoginAttempts) {
            $timeSinceLastAttempt = time() - strtotime($attempts['last_attempt']);
            return $timeSinceLastAttempt < $this->lockoutTime;
        }
        
        return false;
    }
    
    /**
     * Get failed login attempts
     */
    private function getFailedAttempts($identifier) {
        $sql = "SELECT COUNT(*) as count, MAX(created_at) as last_attempt 
                FROM failed_login_attempts 
                WHERE identifier = :identifier 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $result = $this->db->fetchOne($sql, [':identifier' => $identifier]);
        
        return [
            'count' => $result['count'] ?? 0,
            'last_attempt' => $result['last_attempt'] ?? null
        ];
    }
    
    /**
     * Log failed login attempt
     */
    private function logFailedAttempt($identifier) {
        // Create failed_login_attempts table if it doesn't exist
        $this->createFailedAttemptsTable();
        
        $data = [
            'identifier' => $identifier,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $this->db->insert('failed_login_attempts', $data);
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts($identifier) {
        $this->db->delete('failed_login_attempts', 'identifier = :identifier', [':identifier' => $identifier]);
    }
    
    /**
     * Create session for authenticated user
     */
    private function createSession($user, $rememberMe = false) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['session_fingerprint'] = $this->generateSessionFingerprint();
        
        // Handle remember me
        if ($rememberMe) {
            $this->createRememberToken($user['id']);
        }
    }
    
    /**
     * Validate session integrity
     */
    private function validateSession() {
        // Check session timeout (24 hours)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
            return false;
        }
        
        // Check session fingerprint
        if (!isset($_SESSION['session_fingerprint']) || 
            $_SESSION['session_fingerprint'] !== $this->generateSessionFingerprint()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate session fingerprint
     */
    private function generateSessionFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $ipAddress);
    }
    
    /**
     * Create remember me token
     */
    private function createRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
        
        // Store token in database
        $data = [
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => $expiry
        ];
        
        $this->createRememberTokensTable();
        $this->db->insert('remember_tokens', $data);
        
        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    
    /**
     * Validate remember me token
     */
    private function validateRememberToken($token) {
        $hashedToken = hash('sha256', $token);
        
        $sql = "SELECT rt.user_id, u.* 
                FROM remember_tokens rt 
                JOIN users u ON rt.user_id = u.id 
                WHERE rt.token = :token 
                AND rt.expires_at > NOW() 
                AND u.is_active = 1";
        
        $result = $this->db->fetchOne($sql, [':token' => $hashedToken]);
        
        if ($result) {
            $this->createSession($result, true);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user by username or email
     */
    private function getUserByUsernameOrEmail($identifier) {
        $sql = "SELECT * FROM users 
                WHERE (username = :identifier OR email = :identifier) 
                AND is_active = 1";
        
        return $this->db->fetchOne($sql, [':identifier' => $identifier]);
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = :id AND is_active = 1";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $userId]);
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $description) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $this->createUserActivityTable();
        $this->db->insert('user_activity', $data);
    }
    
    /**
     * Sanitize user data for output
     */
    private function sanitizeUserData($user) {
        unset($user['password_hash']);
        return $user;
    }
    
    /**
     * Create failed login attempts table
     */
    private function createFailedAttemptsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS failed_login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_identifier (identifier),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB";
        
        $this->db->executeQuery($sql);
    }
    
    /**
     * Create remember tokens table
     */
    private function createRememberTokensTable() {
        $sql = "CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB";
        
        $this->db->executeQuery($sql);
    }
    
    /**
     * Create user activity table
     */
    private function createUserActivityTable() {
        $sql = "CREATE TABLE IF NOT EXISTS user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB";
        
        $this->db->executeQuery($sql);
    }
}

// Global authentication functions for backward compatibility
function isAuthenticated() {
    $auth = new Auth();
    return $auth->isAuthenticated();
}

function getCurrentUser() {
    $auth = new Auth();
    return $auth->getCurrentUser();
}

function getCurrentUserId() {
    $user = getCurrentUser();
    return $user ? $user['id'] : null;
}

function hasPermission($permission) {
    $auth = new Auth();
    return $auth->hasPermission($permission);
}

function requireAuth() {
    if (!isAuthenticated()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }
}

function requirePermission($permission) {
    requireAuth();
    
    if (!hasPermission($permission)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
}

function generateCSRFToken() {
    $auth = new Auth();
    return $auth->generateCSRFToken();
}

function validateCSRFToken($token) {
    $auth = new Auth();
    return $auth->validateCSRFToken($token);
}
?>