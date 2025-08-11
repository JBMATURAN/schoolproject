<?php
/**
 * Database Connection Class
 * Secure database connection using PDO with error handling
 */
class DatabaseConnection {
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    
    public function __construct($host = 'localhost', $database = 'your_database', $username = 'your_username', $password = 'your_password', $charset = 'utf8mb4') {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
    }
    
    /**
     * Establish database connection
     * @return PDO|null
     */
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            return $this->pdo;
            
        } catch (PDOException $e) {
            // Log error instead of displaying it in production
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get the PDO instance
     * @return PDO|null
     */
    public function getPdo() {
        if ($this->pdo === null) {
            $this->connect();
        }
        return $this->pdo;
    }
    
    /**
     * Close database connection
     */
    public function disconnect() {
        $this->pdo = null;
    }
    
    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $this->connect();
            $stmt = $this->pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Alternative: Simple procedural approach
 */
function createDatabaseConnection($host = 'localhost', $database = 'your_database', $username = 'your_username', $password = 'your_password') {
    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Example usage:
try {
    // Using the class approach
    $db = new DatabaseConnection('localhost', 'your_database', 'your_username', 'your_password');
    $connection = $db->connect();
    
    if ($db->testConnection()) {
        echo "Database connected successfully!<br>";
    }
    
    // Example query with prepared statement (secure way)
    $stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([1]);
    $user = $stmt->fetch();
    
    // Close connection when done
    $db->disconnect();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Alternative usage with procedural approach
/*
$pdo = createDatabaseConnection('localhost', 'your_database', 'your_username', 'your_password');

// Example secure query
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['user@example.com']);
$user = $stmt->fetch();
*/
?>