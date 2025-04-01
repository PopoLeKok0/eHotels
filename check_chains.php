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

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query hotel chains
    echo "<h2>Current Hotel Chains:</h2>";
    $stmt = $db->query("SELECT * FROM Hotel_Chain ORDER BY Chain_Name");
    $chains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($chains as $chain) {
        echo "Chain Name: " . $chain['Chain_Name'] . "\n";
        echo "Central Office: " . $chain['Central_Office_Address'] . "\n";
        echo "Number of Hotels: " . $chain['Number_of_Hotels'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
    // Query hotels per chain
    echo "<h2>Hotels per Chain:</h2>";
    $stmt = $db->query("SELECT Chain_Name, COUNT(*) as HotelCount FROM Hotel GROUP BY Chain_Name");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($hotels as $hotel) {
        echo "Chain: " . $hotel['Chain_Name'] . "\n";
        echo "Number of Hotels: " . $hotel['HotelCount'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 