<?php
// Database configuration
$host = 'localhost';
$port = 3307;
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123';

// Display any PHP errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    
    // Set error mode
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>Connected to database successfully!</p>";
    
    // Get list of tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in the database:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Create test table if it doesn't exist
    if (!in_array('test_table', $tables)) {
        $db->exec("CREATE TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100))");
        echo "<p>Created test_table!</p>";
    }
    
    // If Hotel_Chain table doesn't exist, create it
    if (!in_array('hotel_chain', $tables)) {
        $db->exec("CREATE TABLE Hotel_Chain (
            Chain_Name VARCHAR(50) PRIMARY KEY,
            Central_Office_Address VARCHAR(100) NOT NULL,
            Num_Hotels INTEGER NOT NULL DEFAULT 0
        )");
        echo "<p>Created Hotel_Chain table!</p>";
    }
    
    // Insert test data
    $db->exec("INSERT INTO test_table (name) VALUES ('Test Data')");
    echo "<p>Inserted test data!</p>";
    
    // Show data
    $result = $db->query("SELECT * FROM test_table")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Test Table Data:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Connection failed: " . $e->getMessage() . "</p>";
}
?> 