<?php
// Database configuration
$host = 'localhost';
$port = 3307;
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123';

// Display errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to ensure text is displayed
ob_start();
echo "<h1>Adding Sample Data</h1>";
echo "<p>Script started at: " . date('Y-m-d H:i:s') . "</p>";
ob_flush();
flush();

try {
    echo "<p>Attempting database connection...</p>";
    ob_flush();
    flush();
    
    // Connect to database
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>Connection successful!</p>";
    ob_flush();
    flush();
    
    // Clear existing data (optional)
    echo "<p>Clearing existing data...</p>";
    ob_flush();
    flush();
    
    $db->exec("DELETE FROM hotel_chain");
    
    echo "<p>Data cleared. Adding sample hotel chains...</p>";
    ob_flush();
    flush();
    
    // Add sample hotel chains
    $chains = [
        ['Marriott International', '10400 Fernwood Road, Bethesda, MD, USA', 7600],
        ['Hilton Worldwide', 'McLean, Virginia, USA', 6500],
        ['InterContinental Hotels Group', 'Denham, Buckinghamshire, UK', 5900],
        ['AccorHotels', 'Paris, France', 5100],
        ['Wyndham Hotels & Resorts', 'Parsippany, New Jersey, USA', 9000]
    ];
    
    $insertChain = $db->prepare("INSERT INTO hotel_chain (Chain_Name, Central_Office_Address, Num_Hotels) VALUES (?, ?, ?)");
    
    foreach ($chains as $index => $chain) {
        echo "<p>Processing chain " . ($index + 1) . " of " . count($chains) . ": {$chain[0]}...</p>";
        ob_flush();
        flush();
        
        $insertChain->execute($chain);
        echo "<p style='color:green'>Added hotel chain: {$chain[0]}</p>";
        ob_flush();
        flush();
    }
    
    echo "<p style='color: green; font-weight: bold;'>All sample data added successfully!</p>";
    echo "<p><a href='index.php'>Return to homepage</a></p>";
    ob_flush();
    flush();
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>General Error: " . $e->getMessage() . "</p>";
}

echo "<p>Script completed at: " . date('Y-m-d H:i:s') . "</p>";
ob_end_flush();
?> 