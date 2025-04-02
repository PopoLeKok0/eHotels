<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Access denied. Please log in.";
    header("Location: ../login.php");
    exit;
}

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error_message'] = "Invalid request method. Chain deletion must be a POST request.";
    header("Location: manage_chains.php");
    exit;
}

// Check if chain_name is provided
if (!isset($_POST['chain_name']) || empty($_POST['chain_name'])) {
    $_SESSION['error_message'] = "No hotel chain specified for deletion.";
    header("Location: manage_chains.php");
    exit;
}

$chain_name = trim($_POST['chain_name']);
$db = getDatabase();
$db_connection = $db->getConnection();

try {
    // Begin transaction since we may need to delete from multiple tables
    $db_connection->beginTransaction();
    
    // Check if the chain exists
    $check_stmt = $db_connection->prepare("SELECT COUNT(*) FROM Hotel_Chain WHERE Chain_Name = ?");
    $check_stmt->execute([$chain_name]);
    if ($check_stmt->fetchColumn() == 0) {
        $_SESSION['error_message'] = "Hotel chain '{$chain_name}' not found.";
        header("Location: manage_chains.php");
        exit;
    }
    
    // Check if there are hotels in this chain
    $hotels_stmt = $db_connection->prepare("SELECT COUNT(*) FROM Hotel WHERE Chain_Name = ?");
    $hotels_stmt->execute([$chain_name]);
    $hotel_count = $hotels_stmt->fetchColumn();
    
    if ($hotel_count > 0) {
        // If there are hotels, prompt user to confirm deletion or redirect to a page to manage hotels first
        // For simplicity, we'll show a warning but proceed with deletion
        // In a real application, you might want a confirmation page for this destructive action
        
        // Get a list of hotel addresses that will be affected
        $hotels_list_stmt = $db_connection->prepare("SELECT Hotel_Address FROM Hotel WHERE Chain_Name = ?");
        $hotels_list_stmt->execute([$chain_name]);
        $hotels = $hotels_list_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete room bookings and rentings for hotels in this chain
        foreach ($hotels as $hotel_address) {
            // First, delete bookings for rooms in this hotel
            $delete_bookings_stmt = $db_connection->prepare("
                DELETE FROM Room_Booking
                WHERE Hotel_Address = ?
            ");
            $delete_bookings_stmt->execute([$hotel_address]);
            
            // Then delete renting records for rooms in this hotel  
            $delete_rentings_stmt = $db_connection->prepare("
                DELETE FROM Renting
                WHERE Hotel_Address = ?
            ");
            $delete_rentings_stmt->execute([$hotel_address]);
            
            // Delete rooms in this hotel
            $delete_rooms_stmt = $db_connection->prepare("
                DELETE FROM Room
                WHERE Hotel_Address = ?
            ");
            $delete_rooms_stmt->execute([$hotel_address]);
        }
        
        // Finally, delete all hotels in this chain
        $delete_hotels_stmt = $db_connection->prepare("
            DELETE FROM Hotel
            WHERE Chain_Name = ?
        ");
        $delete_hotels_stmt->execute([$chain_name]);
    }
    
    // Delete the chain itself
    $delete_chain_stmt = $db_connection->prepare("
        DELETE FROM Hotel_Chain
        WHERE Chain_Name = ?
    ");
    $delete_chain_stmt->execute([$chain_name]);
    
    // Also delete related chain contact information (emails, phones)
    $delete_emails_stmt = $db_connection->prepare("
        DELETE FROM Chain_Email
        WHERE Chain_Name = ?
    ");
    $delete_emails_stmt->execute([$chain_name]);
    
    $delete_phones_stmt = $db_connection->prepare("
        DELETE FROM Chain_Phone
        WHERE Chain_Name = ?
    ");
    $delete_phones_stmt->execute([$chain_name]);
    
    // Commit the transaction
    $db_connection->commit();
    
    // Success message including info about what was deleted
    if ($hotel_count > 0) {
        $_SESSION['success_message'] = "Hotel chain '{$chain_name}' deleted successfully, along with {$hotel_count} hotels and their associated rooms.";
    } else {
        $_SESSION['success_message'] = "Hotel chain '{$chain_name}' deleted successfully.";
    }
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if ($db_connection->inTransaction()) {
        $db_connection->rollBack();
    }
    
    // Check for specific error types
    if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
        $_SESSION['error_message'] = "Cannot delete hotel chain '{$chain_name}'. It has dependent records that cannot be automatically removed.";
    } else {
        $_SESSION['error_message'] = "Error deleting hotel chain '{$chain_name}': " . $e->getMessage();
    }
}

// Redirect back to the manage chains page
header("Location: manage_chains.php");
exit; 