<?php
// Set maximum error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Output basic info first
echo "PHP is running. Time: " . date('Y-m-d H:i:s');

// Test database connection
echo "<h2>Testing Database Connection</h2>";

// Database configuration
$host = 'localhost';
$port = 3307;
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123';

try {
    echo "<p>Attempting to connect...</p>";
    flush(); // Try to force output to browser
    
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>Connected successfully!</p>";
    
    // Run a simple query
    echo "<p>Running a test query...</p>";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tables in database:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p>Script completed</p>";
?> 