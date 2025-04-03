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

echo "<h2>Adding One Hotel Chain</h2>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add just one record
    $chainName = "Test Hotel Chain";
    $address = "123 Test Street, Test City";
    $numHotels = 5;
    
    echo "<p>Attempting to add: $chainName</p>";
    
    // Direct query approach (simpler than prepare/execute)
    $sql = "INSERT INTO hotel_chain (Chain_Name, Central_Office_Address, Num_Hotels) 
            VALUES ('$chainName', '$address', $numHotels)";
    
    $result = $db->exec($sql);
    
    if ($result) {
        echo "<p style='color: green;'>Successfully added hotel chain!</p>";
    } else {
        echo "<p style='color: orange;'>No error, but insert may not have worked.</p>";
    }
    
    echo "<p>Script completed!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>General error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Return to homepage</a></p>";
?> 