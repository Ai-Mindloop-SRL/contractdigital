<?php
/**
 * Database Helper Functions
 * Contract Digital Platform
 * 
 * @file includes/db_helpers.php
 * @description Reusable database query functions with prepared statements
 */

/**
 * Execute UPDATE/INSERT/DELETE query and return affected rows
 *
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders (?)
 * @param array $params Parameters to bind
 * @param string $types Parameter types (e.g., 'ssi' for string, string, int)
 * @return int|false Number of affected rows or false on failure
 */
function executeUpdate($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error . " | Query: " . $query);
        return false;
    }
    
    if (!empty($params) && !empty($types)) {
        if (count($params) !== strlen($types)) {
            error_log("Param count mismatch: " . count($params) . " params vs " . strlen($types) . " types");
            $stmt->close();
            return false;
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " | Query: " . $query);
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
 * @param string $query SQL query with placeholders (?)
 * @param array $params Parameters to bind
 * @param string $types Parameter types (e.g., 'i' for int, 's' for string)
 * @return array|null Associative array or null if not found
 */
function fetchOne($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error . " | Query: " . $query);
        return null;
    }
    
    if (!empty($params) && !empty($types)) {
        if (count($params) !== strlen($types)) {
            error_log("Param count mismatch: " . count($params) . " params vs " . strlen($types) . " types");
            $stmt->close();
            return null;
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " | Query: " . $query);
        $stmt->close();
        return null;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Fetch multiple rows from database
 *
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders (?)
 * @param array $params Parameters to bind
 * @param string $types Parameter types
 * @return array Array of associative arrays (empty if none found)
 */
function fetchAll($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error . " | Query: " . $query);
        return [];
    }
    
    if (!empty($params) && !empty($types)) {
        if (count($params) !== strlen($types)) {
            error_log("Param count mismatch: " . count($params) . " params vs " . strlen($types) . " types");
            $stmt->close();
            return [];
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " | Query: " . $query);
        $stmt->close();
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
 * Execute INSERT and return last insert ID
 *
 * @param mysqli $conn Database connection
 * @param string $query SQL INSERT query with placeholders
 * @param array $params Parameters to bind
 * @param string $types Parameter types
 * @return int|false Last insert ID or false on failure
 */
function executeInsert($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error . " | Query: " . $query);
        return false;
    }
    
    if (!empty($params) && !empty($types)) {
        if (count($params) !== strlen($types)) {
            error_log("Param count mismatch: " . count($params) . " params vs " . strlen($types) . " types");
            $stmt->close();
            return false;
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error . " | Query: " . $query);
        $stmt->close();
        return false;
    }
    
    $insert_id = $conn->insert_id;
    $stmt->close();
    
    return $insert_id;
}
