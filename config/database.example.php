<?php
/**
 * Database Configuration Template
 * Multi-tenant Contract Management Platform
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to: config/database.php
 * 2. Replace the placeholder values with your actual database credentials
 * 3. NEVER commit config/database.php to GitHub (it's in .gitignore)
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 * @throws Exception on connection failure
 */
function getDBConnection() {
    static $connection = null;
    
    // Return existing connection if available
    if ($connection !== null && $connection->ping()) {
        return $connection;
    }
    
    // Create new connection
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($connection->connect_error) {
        error_log("Database connection failed: " . $connection->connect_error);
        throw new Exception("Eroare la conectarea la baza de date");
    }
    
    // Set charset
    if (!$connection->set_charset(DB_CHARSET)) {
        error_log("Error setting charset: " . $connection->error);
        throw new Exception("Eroare la setarea charset-ului");
    }
    
    return $connection;
}

/**
 * Close database connection
 */
function closeDBConnection() {
    global $connection;
    if ($connection !== null) {
        $connection->close();
        $connection = null;
    }
}

/**
 * Escape string for SQL (helper function)
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeString($string) {
    $db = getDBConnection();
    return $db->real_escape_string($string);
}

/**
 * Execute a prepared statement with error handling
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types (e.g., 'ssi' for string, string, int)
 * @param array $params Parameters to bind
 * @return mysqli_stmt|false Statement object or false on failure
 */
function executePreparedStatement($query, $types, $params) {
    $db = getDBConnection();
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $db->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

/**
 * Get single row from database
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types
 * @param array $params Parameters to bind
 * @return array|null Associative array or null if not found
 */
function getRow($query, $types = '', $params = []) {
    $stmt = executePreparedStatement($query, $types, $params);
    if (!$stmt) {
        return null;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Get multiple rows from database
 * 
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types
 * @param array $params Parameters to bind
 * @return array Array of associative arrays
 */
function getRows($query, $types = '', $params = []) {
    $stmt = executePreparedStatement($query, $types, $params);
    if (!$stmt) {
        return [];
    }
    
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    
    return $rows;
}

/**
 * Execute UPDATE/INSERT/DELETE query and return affected rows
 * 
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @param string $types Parameter types (e.g., 'ssi' for string, string, int)
 * @return int|false Number of affected rows or false on failure
 */
function executeUpdate($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

/**
 * Fetch one row from database
 * 
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders
 * @param array $params Parameters to bind
 * @param string $types Parameter types (e.g., 'i' for int)
 * @return array|null Associative array or null if not found
 */
function fetchOne($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Test database connection
 * 
 * @return bool True if connection successful
 */
function testDBConnection() {
    try {
        $db = getDBConnection();
        return true;
    } catch (Exception $e) {
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}

// Enable error reporting for mysqli (development only - remove in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ===== BACKWARDS COMPATIBILITY =====
// Create global $conn variable for legacy code
try {
    $conn = getDBConnection();
} catch (Exception $e) {
    error_log("Failed to create global \$conn: " . $e->getMessage());
    $conn = null;
}

// Also create $pdo for PDO compatibility (some code might use it)
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Failed to create PDO connection: " . $e->getMessage());
    $pdo = null;
}
?>
