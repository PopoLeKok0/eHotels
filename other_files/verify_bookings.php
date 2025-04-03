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
    
    // Query Reserved_By
    echo "<h2>Reserved Rooms:</h2>";
    $stmt = $db->query("SELECT * FROM Reserved_By ORDER BY Booking_ID");
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($reservations as $reservation) {
        echo "Booking ID: " . $reservation['Booking_ID'] . "\n";
        echo "Hotel: " . $reservation['Hotel_Address'] . "\n";
        echo "Room Number: " . $reservation['Room_Num'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
    // Query Rented_By
    echo "<h2>Rented Rooms:</h2>";
    $stmt = $db->query("SELECT * FROM Rented_By ORDER BY Renting_ID");
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($rentals as $rental) {
        echo "Renting ID: " . $rental['Renting_ID'] . "\n";
        echo "Hotel: " . $rental['Hotel_Address'] . "\n";
        echo "Room Number: " . $rental['Room_Num'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 