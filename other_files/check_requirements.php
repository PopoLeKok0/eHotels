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
    
    // 0. Describe Room Table
    echo "<h2>0. Room Table Structure:</h2>";
    try {
        $stmt = $db->query("DESCRIBE Room");
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($structure as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error describing table: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "<hr>";
    
    // 1. Check Hotel Chains
    echo "<h2>1. Hotel Chains Overview:</h2>";
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
    
    // 2. Check Rooms per Hotel
    echo "<h2>2. Rooms per Hotel:</h2>";
    $stmt = $db->query("
        SELECT h.Hotel_Address, h.Star_Rating,
               COUNT(r.Room_Num) as RoomCount,
               GROUP_CONCAT(DISTINCT r.Capacity) as Capacities,
               GROUP_CONCAT(DISTINCT r.View_Type) as ViewTypes
        FROM Hotel h
        LEFT JOIN Room r ON h.Hotel_Address = r.Hotel_Address
        GROUP BY h.Hotel_Address, h.Star_Rating
        ORDER BY h.Star_Rating DESC, h.Hotel_Address
    ");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($hotels as $hotel) {
        echo "Hotel: " . $hotel['Hotel_Address'] . "\n";
        echo "Star Rating: " . $hotel['Star_Rating'] . "\n";
        echo "Room Count: " . $hotel['RoomCount'] . "\n";
        echo "Capacities: " . $hotel['Capacities'] . "\n";
        echo "View Types: " . $hotel['ViewTypes'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
    // 3. Check Hotels in Same Area
    echo "<h2>3. Hotels in Same Area:</h2>";
    $stmt = $db->query("
        SELECT Area, COUNT(*) as HotelCount,
               GROUP_CONCAT(DISTINCT Star_Rating) as StarRatings
        FROM Hotel
        GROUP BY Area
        HAVING HotelCount >= 2
        ORDER BY HotelCount DESC
    ");
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($areas as $area) {
        echo "Area: " . $area['Area'] . "\n";
        echo "Number of Hotels: " . $area['HotelCount'] . "\n";
        echo "Star Ratings: " . $area['StarRatings'] . "\n";
        echo "-------------------\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 