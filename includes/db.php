<?php
/**
 * Database Connection Configuration (Legacy? See config/database.php)
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 */

// Database configuration
$host = 'localhost';
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123'; // Change this to your actual database password
$port = '3307'; // Using the new MySQL port

// Attempt to establish database connection
try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    
    // Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Display error message if connection fails
    // In production, consider logging the error instead of displaying it
    die("DATABASE CONNECTION ERROR: " . $e->getMessage());
}

/**
 * Helper function to execute queries and return results
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return array|bool Query results or false on failure
 */
function executeQuery($sql, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Check if the query is a SELECT statement
        if (stripos(trim($sql), 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        
        // For non-SELECT queries, return the number of affected rows
        return $stmt->rowCount();
    } catch(PDOException $e) {
        // Log error or handle it as needed
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to get a single row from a query
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return array|bool Single row result or false on failure
 */
function getRow($sql, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch(PDOException $e) {
        // Log error or handle it as needed
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to get a single value from a query
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind to the query
 * @return mixed|bool Single value or false on failure
 */
function getValue($sql, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        // Log error or handle it as needed
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to start a transaction
 * @return bool Success or failure
 */
function beginTransaction() {
    global $db;
    return $db->beginTransaction();
}

/**
 * Helper function to commit a transaction
 * @return bool Success or failure
 */
function commitTransaction() {
    global $db;
    return $db->commit();
}

/**
 * Helper function to rollback a transaction
 * @return bool Success or failure
 */
function rollbackTransaction() {
    global $db;
    return $db->rollBack();
}

/**
 * Helper function to get the last inserted ID
 * @return string|bool The last inserted ID or false on failure
 */
function getLastInsertId() {
    global $db;
    return $db->lastInsertId();
}

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 