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

// Sample hotel addresses to check
$hotelAddressesToCheck = [
    '100 Front Street West, Toronto, ON M5J 1E3', // 5-star, Toronto
    '475 Howe Street, Vancouver, BC V6C 2B3',     // 5-star, Vancouver
    '1180 Phillips Square, Montreal, QC H3B 3C8', // 4-star, Montreal
    '100 Kent Street, Ottawa, ON K1P 5R7'         // 3-star, Ottawa
];

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Checking Room Details for Sample Hotels:</h2>";
    
    foreach ($hotelAddressesToCheck as $address) {
        echo "<h3>Hotel: " . htmlspecialchars($address) . "</h3>";
        
        $stmt = $db->prepare("SELECT Room_Num, Capacity, Amenities, View_Type, Price FROM Room WHERE Hotel_Address = ? ORDER BY Room_Num");
        $stmt->execute([$address]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rooms)) {
            echo "<p style='color:red'>No rooms found for this hotel!</p>";
        } else {
            echo "<table border='1'>";
            echo "<tr><th>Room Num</th><th>Capacity</th><th>Amenities</th><th>View Type</th><th>Price</th></tr>";
            foreach ($rooms as $room) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($room['Room_Num']) . "</td>";
                echo "<td>" . htmlspecialchars($room['Capacity']) . "</td>";
                echo "<td>" . htmlspecialchars($room['Amenities']) . "</td>";
                echo "<td>" . htmlspecialchars($room['View_Type']) . "</td>"; // Check this value
                echo "<td>" . htmlspecialchars($room['Price']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "<hr>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 