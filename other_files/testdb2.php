<?php
// Display all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

// Database configuration
$host = 'localhost';
$port = 3307;
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123';

// Test connection with timeout
try {
    // Set a timeout for the connection
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3, // 3 seconds timeout
        PDO::ATTR_PERSISTENT => false
    ];
    
    echo "<p>Attempting to connect to database...</p>";
    
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password, $options);
    
    echo "<p style='color: green;'>Connected to database successfully!</p>";
    
    // Get list of tables with timeout
    echo "<p>Attempting to show tables...</p>";
    $stmt = $db->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in the database:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Connection failed: " . $e->getMessage() . "</p>";
    echo "<p>DSN used: $dsn</p>";
    
    // Try to connect without database to see if server is reachable
    try {
        $dsn_no_db = "mysql:host=$host;port=$port";
        $db_no_db = new PDO($dsn_no_db, $username, $password, $options);
        echo "<p style='color: orange;'>Could connect to MySQL server, but database '$database' might not exist or user lacks access.</p>";
    } catch (PDOException $e2) {
        echo "<p style='color: red;'>Cannot connect to MySQL server either: " . $e2->getMessage() . "</p>";
    }
}
?> 