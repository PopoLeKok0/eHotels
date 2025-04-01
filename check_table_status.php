<?php
// Set maximum error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'localhost';
$port = 3307;
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123';

echo "<h2>Checking Table Status</h2>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check hotel_chain table status
    echo "<h3>Checking hotel_chain:</h3>";
    $stmt = $db->query("CHECK TABLE hotel_chain");
    $status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($status, true) . "</pre>";
    
    // Show any open/locked tables
    echo "<h3>Checking for Locked Tables:</h3>";
    $stmt = $db->query("SHOW OPEN TABLES WHERE In_use > 0");
    $lockedTables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($lockedTables) > 0) {
        echo "<pre>" . print_r($lockedTables, true) . "</pre>";
    } else {
        echo "<p>No locked tables found.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 