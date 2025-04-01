<?php
/**
 * e-Hotels Database Connection Singleton
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 * 
 * This file handles the database connection and provides utility functions
 * for working with the database.
 */

class Database {
    // Database connection parameters
    private $host = "localhost";
    private $db_name = "ehotels";
    private $username = "ehotels_user"; // Updated MySQL username
    private $password = "password123"; // Change this to your actual password
    private $port = "3307";         // Updated to the new MySQL port
    private $conn = null;
    
    /**
     * Get the database connection
     * 
     * @return PDO The database connection object
     */
    public function getConnection() {
        try {
            // If connection already exists, return it
            if ($this->conn !== null) {
                return $this->conn;
            }
            
            // Create a new connection
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            return $this->conn;
        } catch (PDOException $e) {
            // Log the error and display a user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact the administrator.");
        }
    }
    
    /**
     * Execute a query and return the result
     * 
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return array The query result
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " - Query: " . $query);
            throw new Exception("Database query failed. Please try again later.");
        }
    }
    
    /**
     * Execute a query and return a single row
     * 
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return array|null The query result or null if no rows found
     */
    public function querySingle($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " - Query: " . $query);
            throw new Exception("Database query failed. Please try again later.");
        }
    }
    
    /**
     * Execute a query that modifies data (INSERT, UPDATE, DELETE)
     * 
     * @param string $query The SQL query to execute
     * @param array $params The parameters to bind to the query
     * @return int The number of affected rows
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Execute Error: " . $e->getMessage() . " - Query: " . $query);
            throw new Exception("Database operation failed. Please try again later.");
        }
    }
    
    /**
     * Insert data and return the last insert ID
     * 
     * @param string $query The INSERT query to execute
     * @param array $params The parameters to bind to the query
     * @return string The last insert ID
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            error_log("Insert Error: " . $e->getMessage() . " - Query: " . $query);
            throw new Exception("Database insert failed. Please try again later.");
        }
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
}

// Create a global database instance
$database = new Database();

/**
 * Function to get the database instance
 * 
 * @return Database The database instance
 */
function getDatabase() {
    global $database;
    return $database;
} 