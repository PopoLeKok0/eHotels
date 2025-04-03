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
    
    // Query rooms
    $stmt = $db->query("SELECT Hotel_Address, Room_Num FROM Room ORDER BY Hotel_Address, Room_Num");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Available Rooms:</h2>";
    echo "<pre>";
    foreach ($rooms as $room) {
        echo "Hotel: " . $room['Hotel_Address'] . "\n";
        echo "Room Number: " . $room['Room_Num'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 