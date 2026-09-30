<?php
/**
 * PawPal - Database Configuration
 * PDO MySQL Connection with Auto-Initialization
 */

// Enable output buffering to prevent headers already sent issues
ob_start();

// Database credentials
define('DB_HOST', '127.0.0.1'); // Using 127.0.0.1 is more reliable on Windows
define('DB_NAME', 'pawpal_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL - adjust if your project is in a subdirectory
define('BASE_URL', '/pet');

// Site name
define('SITE_NAME', 'PawPal');

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection & Auto-setup
try {
    // First connect to MySQL server without selecting DB
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);

    // Check if users table exists, if not, import the schema
    $tableExists = false;
    try {
        $result = $pdo->query("SELECT 1 FROM users LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }

    if (!$tableExists) {
        $sqlFile = __DIR__ . '/../database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Remove comments and split by semicolon
            // Basic SQL parser for importing
            $queries = explode(';', $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $pdo->exec($query);
                }
            }
        }
    }

} catch (PDOException $e) {
    die("Database connection/initialization failed: " . $e->getMessage());
}

