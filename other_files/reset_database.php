<?php
// Set maximum error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration - **Connect WITHOUT specifying a database initially**
$host = 'localhost';
$port = 3307;
$admin_user = 'root'; // Use root or another user with DROP/CREATE privileges
$admin_password = '';   // Default XAMPP root password is often empty
$database_to_reset = 'ehotels';


echo "<h1>Resetting Database: {$database_to_reset}</h1>";

try {
    // Connect to MySQL server (without selecting a database)
    $dsn = "mysql:host=$host;port=$port";
    $db = new PDO($dsn, $admin_user, $admin_password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green;'>Connected to MySQL server successfully.</p>";

    // 1. Drop the database if it exists
    echo "<p>Attempting to DROP DATABASE `{$database_to_reset}`...</p>";
    try {
        $db->exec("DROP DATABASE IF EXISTS `{$database_to_reset}`");
        echo "<p style='color:orange;'>DROP DATABASE successful (or database didn't exist).</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error dropping database: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit; // Stop if we can't drop it
    }

    // 2. Create the database again
    echo "<p>Attempting to CREATE DATABASE `{$database_to_reset}`...</p>";
    try {
        $db->exec("CREATE DATABASE `{$database_to_reset}`");
        echo "<p style='color: green;'>CREATE DATABASE successful!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error creating database: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit; // Stop if we can't create it
    }
    
    // 3. Grant privileges back to the application user
    echo "<p>Attempting to GRANT privileges to 'ehotels_user'...</p>";
    try {
        // Grant all privileges on the new database to your application user
        $db->exec("GRANT ALL PRIVILEGES ON `{$database_to_reset}`.* TO 'ehotels_user'@'localhost' IDENTIFIED BY 'password123'");
        $db->exec("FLUSH PRIVILEGES"); // Make sure grants are applied
        echo "<p style='color: green;'>Privileges granted successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error granting privileges: " . htmlspecialchars($e->getMessage()) . "</p>";
        // Don't exit here, maybe privileges were already okay
    }

    echo "<hr>";
    echo "<p style='font-weight:bold;'>Database `{$database_to_reset}` has been reset.</p>";
    echo "<p>You should now run the schema creation script.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Connection/Reset Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>Note:</b> Make sure the user '{$admin_user}' exists and has the necessary permissions (and correct password if not empty).</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>General Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 