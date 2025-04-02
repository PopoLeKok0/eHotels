<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Access denied. Please log in.";
    header("Location: ../login.php");
    exit;
}

// Ensure the request is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: manage_hotels.php");
    exit;
}

// Check required fields
if (!isset($_POST['room_number']) || !isset($_POST['hotel_address'])) {
    $_SESSION['error_message'] = "Missing required parameters.";
    header("Location: manage_hotels.php");
    exit;
}

$room_number = (int)$_POST['room_number'];
$hotel_address = trim($_POST['hotel_address']);
$db = getDatabase();
$errors = [];

// Validate price
if (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
    $errors[] = "Price must be a positive number";
} else {
    $price = (float)$_POST['price'];
}

// Validate capacity
if (empty($_POST['capacity']) || !is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0) {
    $errors[] = "Capacity must be a positive number";
} else {
    $capacity = (int)$_POST['capacity'];
}

// Get optional fields
$view = !empty($_POST['view']) ? trim($_POST['view']) : null;
$amenities = !empty($_POST['amenities']) ? trim($_POST['amenities']) : null;
$extendable = isset($_POST['extendable']) ? 1 : 0;
$damages = !empty($_POST['damages']) ? trim($_POST['damages']) : null;

// If there are errors, store in session and redirect back
if (!empty($errors)) {
    $_SESSION['error_message'] = implode("; ", $errors);
    header("Location: manage_rooms.php?hotel_address=" . urlencode($hotel_address));
    exit;
}

// Update the room
try {
    $stmt = $db->getConnection()->prepare("
        UPDATE Room 
        SET Price = ?, 
            Capacity = ?, 
            View = ?, 
            Amenities = ?, 
            Extendable = ?, 
            Damages = ?
        WHERE Room_Number = ? AND Hotel_Address = ?
    ");
    
    $result = $stmt->execute([
        $price,
        $capacity,
        $view,
        $amenities,
        $extendable,
        $damages,
        $room_number,
        $hotel_address
    ]);
    
    if ($result) {
        $_SESSION['success_message'] = "Room {$room_number} updated successfully";
    } else {
        $_SESSION['error_message'] = "Failed to update room";
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to the manage rooms page
header("Location: manage_rooms.php?hotel_address=" . urlencode($hotel_address));
exit;
?> 