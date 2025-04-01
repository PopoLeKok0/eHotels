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

echo "<h2>Fixing hotel_chain Table</h2>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Drop the existing (potentially corrupted) table
    echo "<p>Attempting to DROP TABLE hotel_chain...</p>";
    try {
        $db->exec("DROP TABLE hotel_chain");
        echo "<p style='color:orange;'>DROP TABLE successful (or table didn't exist).</p>";
    } catch (PDOException $e) {
        // Ignore error if table doesn't exist, but report others
        if (strpos($e->getMessage(), 'Unknown table') === false) {
             echo "<p style='color: red;'>Error dropping table: " . htmlspecialchars($e->getMessage()) . "</p>";
             exit; // Stop if we can't drop it (and it wasn't an 'unknown table' error)
        } else {
            echo "<p style='color:grey;'>Table didn't exist, proceeding.</p>";
        }
    }
    
    // 2. Recreate the table using the correct schema
    echo "<p>Attempting to CREATE TABLE hotel_chain...</p>";
    $createTableSQL = "
    CREATE TABLE Hotel_Chain (
        Chain_Name VARCHAR(50) PRIMARY KEY,
        Central_Office_Address VARCHAR(100) NOT NULL,
        Num_Hotels INTEGER NOT NULL DEFAULT 0,
        CONSTRAINT check_num_hotels CHECK (Num_Hotels >= 0)
    );
    ";
    
    $db->exec($createTableSQL);
    echo "<p style='color: green;'>CREATE TABLE successful!</p>";
    
    echo "<p style='font-weight:bold;'>Table hotel_chain has been fixed.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error during fix process: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>General error during fix process: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 