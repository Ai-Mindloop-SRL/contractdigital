<?php
/**
 * Database Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to: config/database.php
 * 2. Replace the placeholder values with your actual database credentials
 * 3. NEVER commit config/database.php to GitHub (it's in .gitignore)
 */

// Database Connection Settings
define('DB_HOST', 'localhost');              // Database host (usually 'localhost')
define('DB_USER', 'your_db_username');       // Your MySQL username
define('DB_PASS', 'your_db_password');       // Your MySQL password
define('DB_NAME', 'contractdigital_ro');     // Database name

// Character Set
define('DB_CHARSET', 'utf8mb4');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Set character set
$conn->set_charset(DB_CHARSET);

// Optional: Set timezone (adjust to your needs)
$conn->query("SET time_zone = '+02:00'");  // Romania timezone

// For backward compatibility
$db = $conn;

?>
