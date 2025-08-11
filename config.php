<?php
/**
 * Database Configuration File
 * Store your database credentials here
 * Keep this file secure and outside web root in production
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// Alternative: Using array for configuration
$config = [
    'database' => [
        'host' => 'localhost',
        'name' => 'your_database_name',
        'username' => 'your_username',
        'password' => 'your_password',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false
        ]
    ]
];

// Environment-based configuration (recommended for production)
$environment = $_ENV['APP_ENV'] ?? 'development';

switch ($environment) {
    case 'production':
        $config['database']['host'] = $_ENV['DB_HOST'] ?? 'localhost';
        $config['database']['name'] = $_ENV['DB_NAME'] ?? 'prod_database';
        $config['database']['username'] = $_ENV['DB_USER'] ?? 'prod_user';
        $config['database']['password'] = $_ENV['DB_PASS'] ?? 'prod_password';
        break;
        
    case 'testing':
        $config['database']['host'] = 'localhost';
        $config['database']['name'] = 'test_database';
        $config['database']['username'] = 'test_user';
        $config['database']['password'] = 'test_password';
        break;
        
    default: // development
        $config['database']['host'] = 'localhost';
        $config['database']['name'] = 'dev_database';
        $config['database']['username'] = 'dev_user';
        $config['database']['password'] = 'dev_password';
        break;
}
?>