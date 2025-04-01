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
    
    // Query hotel chains with their hotel counts
    echo "<h2>Hotel Chains Overview:</h2>";
    $stmt = $db->query("
        SELECT hc.Chain_Name, hc.Number_of_Hotels, 
               COUNT(DISTINCT h.Star_Rating) as Star_Categories,
               GROUP_CONCAT(DISTINCT h.Area) as Areas
        FROM Hotel_Chain hc
        LEFT JOIN Hotel h ON hc.Chain_Name = h.Chain_Name
        GROUP BY hc.Chain_Name, hc.Number_of_Hotels
    ");
    $chains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($chains as $chain) {
        echo "Chain: " . $chain['Chain_Name'] . "\n";
        echo "Number of Hotels: " . $chain['Number_of_Hotels'] . "\n";
        echo "Star Categories: " . $chain['Star_Categories'] . "\n";
        echo "Areas: " . $chain['Areas'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
    // Query hotels per chain with star ratings
    echo "<h2>Hotels by Chain:</h2>";
    $stmt = $db->query("
        SELECT Chain_Name, Hotel_Address, Star_Rating, Area
        FROM Hotel
        ORDER BY Chain_Name, Star_Rating DESC
    ");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    $currentChain = '';
    foreach ($hotels as $hotel) {
        if ($currentChain !== $hotel['Chain_Name']) {
            echo "\nChain: " . $hotel['Chain_Name'] . "\n";
            $currentChain = $hotel['Chain_Name'];
        }
        echo "Hotel: " . $hotel['Hotel_Address'] . "\n";
        echo "Star Rating: " . $hotel['Star_Rating'] . "\n";
        echo "Area: " . $hotel['Area'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 