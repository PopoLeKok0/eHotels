<?php
// Allow script to run longer
set_time_limit(0);

// Set maximum error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'localhost';
$port = 3307;
$database = 'ehotels';
$username = 'ehotels_user';
$password = 'password123';

// Path to your schema file
// $schemaFile = 'sql/mysql_schema.sql'; // Original schema
// $schemaFile = 'sql/database_population.sql'; // Population script
// $schemaFile = 'sql/fix_hotel_chain.sql'; // Fix for Number_of_Hotels
// $schemaFile = 'sql/fix_hotel_requirements.sql'; // Add Wyndham hotels
// $schemaFile = 'sql/update_passwords.sql'; // Apply hashed passwords
// $schemaFile = 'sql/database_views.sql'; // Create database views
// $schemaFile = 'sql/database_indexes.sql';
// $schemaFile = 'sql/alter_user_tables.sql';
// $schemaFile = 'sql/add_payment_column.sql'; // Execute this script to add payment column
// $schemaFile = 'sql/database_triggers.sql';
// $schemaFile = 'populate_rooms.php';
// $schemaFile = 'sql/database_schema.sql'; // Main schema definition
// $schemaFile = 'sql/database_population.sql'; // Main data population
// $schemaFile = 'sql/fix_hotel_requirements.sql';
// $schemaFile = 'sql/fix_foreign_keys.sql'; // Run script to fix FKs and add Payment_Date
$schemaFile = 'sql/triggers.sql'; // Run script to create triggers

echo "<h1>Running Schema File: {$schemaFile}</h1>";
ob_flush();
flush();

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database";
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>Database connection successful!</p>";
    ob_flush();
    flush();

    // Check file type
    $fileExtension = pathinfo($schemaFile, PATHINFO_EXTENSION);

    if (strtolower($fileExtension) === 'php') {
        // If it's a PHP file, execute it directly
        echo "<p>Executing PHP script: {$schemaFile}</p>";
        echo "<hr>";
        ob_flush();
        flush();
        
        // Pass the database connection to the included script if needed
        // (populate_rooms.php already includes its own connection setup)
        require $schemaFile;
        
        echo "<hr>";
        echo "<h2>PHP Script Execution Finished</h2>";

    } else {
        // Assume it's an SQL file and process as before
        echo "<p>Reading SQL file...</p>";
        $sqlContent = file_get_contents($schemaFile);
        if ($sqlContent === false) {
            throw new Exception("Could not read the schema file: {$schemaFile}");
        }
        echo "<p>SQL file read successfully.</p>";
        ob_flush();
        flush();

        // Remove comments and potentially split into statements
        // Basic comment removal
        $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent); // Remove -- comments
        $sqlContent = preg_replace('/^\s*$/m', '', $sqlContent);   // Remove empty lines
        
        // Split statements by semicolon
        $statements = explode(';', $sqlContent);
        $statementCount = 0;
        $errorCount = 0;

        echo "<p>Executing SQL statements...</p>";
        echo "<hr>";
        ob_flush();
        flush();

        foreach ($statements as $statement) {
            $trimmedStatement = trim($statement);
            if (!empty($trimmedStatement)) {
                $statementCount++;
                echo "<pre>Executing: " . htmlspecialchars(substr($trimmedStatement, 0, 100)) . "...</pre>";
                ob_flush();
                flush();
                
                try {
                    // Check if the statement is a SELECT, DESCRIBE, or SHOW query
                    $commandType = strtoupper(substr($trimmedStatement, 0, strpos($trimmedStatement . ' ', ' ')));
                    if ($commandType === 'SELECT' || $commandType === 'DESCRIBE' || $commandType === 'SHOW') {
                        $stmt = $db->query($trimmedStatement);
                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($results) {
                            echo "<p style='color:blue;'>Query executed. Results:</p>";
                            echo "<table border='1'>";
                            // Header row
                            echo "<tr>";
                            foreach (array_keys($results[0]) as $header) {
                                echo "<th>" . htmlspecialchars($header) . "</th>";
                            }
                            echo "</tr>";
                            // Data rows
                            foreach ($results as $row) {
                                echo "<tr>";
                                foreach ($row as $col) {
                                    echo "<td>" . htmlspecialchars($col ?? 'NULL') . "</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "<p style='color:blue;'>Query executed. No results returned.</p>";
                        }
                    } else {
                        // For non-SELECT statements (INSERT, UPDATE, DELETE, CREATE, etc.)
                        $affectedRows = $db->exec($trimmedStatement);
                        echo "<p style='color:green'>Success. Affected rows: " . ($affectedRows !== false ? $affectedRows : 'N/A') . "</p>";
                    }
                } catch (PDOException $e) {
                    $errorCount++;
                    // Check for 'table already exists' error (code 1050)
                    if ($e->getCode() == '42S01') { // SQLSTATE for 'table already exists'
                         echo "<p style='color:orange;'>Warning: Table likely already exists. (" . htmlspecialchars($e->getMessage()) . ")</p>";
                    } else {
                        echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                echo "<hr>";
                ob_flush();
                flush();
            }
        }

        echo "<h2>SQL Execution Summary</h2>";
        echo "<p>Total statements attempted: {$statementCount}</p>";
        if ($errorCount > 0) {
            echo "<p style='color:red'>Errors encountered: {$errorCount}</p>";
        } else {
            echo "<p style='color:green'>All statements executed successfully (or were warnings).</p>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Connection Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>General Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 