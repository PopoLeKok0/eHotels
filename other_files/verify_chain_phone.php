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

echo "<h2>Verifying chain_phone Table</h2>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check table status
    echo "<h3>Checking status:</h3>";
    $stmt = $db->query("CHECK TABLE chain_phone");
    $status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($status, true) . "</pre>";

    // Describe table structure
    echo "<h3>Checking structure (DESCRIBE):</h3>";
    $stmt = $db->query("DESCRIBE chain_phone");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>General error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 