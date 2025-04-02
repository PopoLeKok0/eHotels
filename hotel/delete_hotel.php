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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: manage_hotels.php");
    exit;
}

// Validate hotel_address
if (!isset($_POST['hotel_address']) || empty($_POST['hotel_address'])) {
    $_SESSION['error_message'] = "No hotel specified for deletion.";
    header("Location: manage_hotels.php");
    exit;
}

$hotel_address = trim($_POST['hotel_address']);
$db = getDatabase();
$conn = $db->getConnection();

// Start a transaction to ensure data integrity
try {
    $conn->beginTransaction();

    // Check if the hotel exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Hotel WHERE Hotel_Address = ?");
    $check_stmt->execute([$hotel_address]);
    
    if ($check_stmt->fetchColumn() === 0) {
        $_SESSION['error_message'] = "Hotel not found.";
        header("Location: manage_hotels.php");
        exit;
    }

    // First, delete all bookings for rooms in this hotel
    $delete_bookings_stmt = $conn->prepare("DELETE FROM Room_Booking WHERE Hotel_Address = ?");
    $delete_bookings_stmt->execute([$hotel_address]);

    // Delete all rentings for rooms in this hotel
    $delete_rentings_stmt = $conn->prepare("DELETE FROM Renting WHERE Hotel_Address = ?");
    $delete_rentings_stmt->execute([$hotel_address]);

    // Delete all reviews for this hotel
    $delete_reviews_stmt = $conn->prepare("DELETE FROM Hotel_Review WHERE Hotel_Address = ?");
    $delete_reviews_stmt->execute([$hotel_address]);

    // Delete all rooms in this hotel
    $delete_rooms_stmt = $conn->prepare("DELETE FROM Room WHERE Hotel_Address = ?");
    $delete_rooms_stmt->execute([$hotel_address]);

    // Finally, delete the hotel itself
    $delete_hotel_stmt = $conn->prepare("DELETE FROM Hotel WHERE Hotel_Address = ?");
    $delete_hotel_stmt->execute([$hotel_address]);

    // Commit the transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Hotel and all related records have been deleted successfully.";
    header("Location: manage_hotels.php");
    exit;
    
} catch (Exception $e) {
    // Rollback the transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['error_message'] = "Error deleting hotel: " . $e->getMessage();
    header("Location: manage_hotels.php");
    exit;
}
?> 