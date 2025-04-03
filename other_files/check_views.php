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

function display_results($title, $results) {
    echo "<h2>" . htmlspecialchars($title) . ":</h2>";
    if (empty($results)) {
        echo "<p>No results found.</p>";
        return;
    }
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    // Header row
    echo "<tr style='background-color: #f2f2f2;'>";
    foreach (array_keys($results[0]) as $header) {
        echo "<th style='padding: 5px;'>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr>";
    // Data rows
    foreach ($results as $row) {
        echo "<tr>";
        foreach ($row as $col) {
            echo "<td style='padding: 5px;'>" . htmlspecialchars($col ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Testing Database Views</h1>";

    // Test View 1: available_rooms_per_area
    $stmt1 = $db->query("SELECT * FROM available_rooms_per_area");
    $results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    display_results("View: available_rooms_per_area", $results1);

    // Test View 2: aggregated_hotel_capacity
    // Limiting to 20 rows for brevity
    $stmt2 = $db->query("SELECT * FROM aggregated_hotel_capacity LIMIT 20"); 
    $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    display_results("View: aggregated_hotel_capacity (First 20 Rows)", $results2);

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 