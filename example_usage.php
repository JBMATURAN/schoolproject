<?php
/**
 * Example Usage of Database Connection
 * This demonstrates how to use the database connection class with configuration
 */

// Include configuration file
require_once 'config.php';
require_once 'database_connection.php';

try {
    // Method 1: Using the configuration array
    $db = new DatabaseConnection(
        $config['database']['host'],
        $config['database']['name'],
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['charset']
    );
    
    $connection = $db->connect();
    
    // Test connection
    if ($db->testConnection()) {
        echo "✅ Database connected successfully!<br><br>";
    }
    
    // Example 1: Create a table (if it doesn't exist)
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $connection->exec($createTableSQL);
    echo "✅ Table 'users' created or already exists<br>";
    
    // Example 2: Insert data (using prepared statements for security)
    $insertSQL = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $connection->prepare($insertSQL);
    
    // Hash password for security
    $hashedPassword = password_hash('mypassword123', PASSWORD_DEFAULT);
    
    $stmt->execute(['John Doe', 'john@example.com', $hashedPassword]);
    echo "✅ User inserted successfully<br>";
    
    // Example 3: Select data
    $selectSQL = "SELECT id, name, email, created_at FROM users WHERE email = ?";
    $stmt = $connection->prepare($selectSQL);
    $stmt->execute(['john@example.com']);
    
    $user = $stmt->fetch();
    if ($user) {
        echo "✅ User found:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Created: " . $user['created_at'] . "<br><br>";
    }
    
    // Example 4: Update data
    $updateSQL = "UPDATE users SET name = ? WHERE email = ?";
    $stmt = $connection->prepare($updateSQL);
    $stmt->execute(['John Smith', 'john@example.com']);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ User updated successfully<br>";
    }
    
    // Example 5: Count records
    $countSQL = "SELECT COUNT(*) as total FROM users";
    $stmt = $connection->query($countSQL);
    $result = $stmt->fetch();
    echo "Total users: " . $result['total'] . "<br>";
    
    // Example 6: Transaction example (for multiple related operations)
    $connection->beginTransaction();
    
    try {
        // Multiple operations that should all succeed or all fail
        $stmt1 = $connection->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt1->execute(['Jane Doe', 'jane@example.com', password_hash('password456', PASSWORD_DEFAULT)]);
        
        $stmt2 = $connection->prepare("UPDATE users SET name = ? WHERE email = ?");
        $stmt2->execute(['Jane Smith', 'jane@example.com']);
        
        // If we get here, commit the transaction
        $connection->commit();
        echo "✅ Transaction completed successfully<br>";
        
    } catch (Exception $e) {
        // Something went wrong, rollback
        $connection->rollback();
        echo "❌ Transaction failed: " . $e->getMessage() . "<br>";
    }
    
    // Clean up connection
    $db->disconnect();
    echo "<br>✅ Database connection closed<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

/**
 * Alternative method using constants from config
 */
function alternativeConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

// Security tips:
echo "<br><h3>🔒 Security Best Practices:</h3>";
echo "1. ✅ Always use prepared statements<br>";
echo "2. ✅ Never store passwords in plain text (use password_hash())<br>";
echo "3. ✅ Keep database credentials in a separate config file<br>";
echo "4. ✅ Use environment variables in production<br>";
echo "5. ✅ Set proper PDO options for security<br>";
echo "6. ✅ Handle errors gracefully without exposing sensitive info<br>";
echo "7. ✅ Use transactions for related operations<br>";
echo "8. ✅ Validate and sanitize all user input<br>";
?>