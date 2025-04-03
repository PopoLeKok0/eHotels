<?php
session_start();
require_once 'config/database.php';

// 1. Check Login & Role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    $_SESSION['error_message'] = "You must be logged in as a customer to book a room.";
    // Redirect to login or search page? Redirecting to search might be better contextually.
    header("Location: search.php"); 
    exit;
}

// 2. Check if POST request and required data is present
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['hotel_address']) || 
    !isset($_POST['room_number']) || 
    !isset($_POST['start_date']) || 
    !isset($_POST['end_date']) ||
    !isset($_POST['total_price'])) 
{
    $_SESSION['error_message'] = "Invalid booking request. Missing required data.";
    header("Location: search.php");
    exit;
}

// 3. Sanitize and retrieve data
$hotel_address = trim($_POST['hotel_address']);
$room_number = (int)$_POST['room_number'];
$start_date = trim($_POST['start_date']);
$end_date = trim($_POST['end_date']);
$total_price = filter_var($_POST['total_price'], FILTER_VALIDATE_FLOAT); // Validate as float
$customer_ssn = $_SESSION['user_id']; // Assumes user_id holds customer SSN

// Basic date validation
try {
    $startDateObj = new DateTime($start_date);
    $endDateObj = new DateTime($end_date);
    $today = new DateTime('today');

    if ($startDateObj < $today || $endDateObj <= $startDateObj) {
        throw new Exception("Invalid booking dates provided.");
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Invalid date format or range: " . $e->getMessage();
    header("Location: search.php");
    exit;
}

if ($total_price === false || $total_price < 0) {
     $_SESSION['error_message'] = "Invalid total price received.";
    header("Location: search.php");
    exit;
}

// 4. Database interaction
$db = getDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Final Availability Check (to prevent race conditions - Uses Booking/Reserved_By)
    $check_sql = "
        SELECT COUNT(*) 
        FROM Room r
        WHERE r.Hotel_Address = ? AND r.Room_Num = ? -- Corrected Room_Num based on search.php
        AND r.Availability = TRUE -- Also check basic availability flag
        AND NOT EXISTS (
            -- Check existing bookings
            SELECT 1 FROM Booking b
            JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
            WHERE rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
            AND (
                (? < b.End_Date AND ? > b.Start_Date) -- Check for overlap
            )
        )
        AND NOT EXISTS (
             -- Check existing rentings 
            SELECT 1 FROM Renting rent
            JOIN Rented_By rented ON rent.Renting_ID = rented.Renting_ID -- Assuming Rented_By links renting to room
            WHERE rented.Hotel_Address = r.Hotel_Address AND rented.Room_Num = r.Room_Num
             AND (
                 (? < rent.End_Date AND ? > rent.Start_Date) -- Check for overlap, corrected date columns for Renting
             )
        )
    ";
    // Parameters: HotelAddr, RoomNum, NewEndDate, NewStartDate, NewEndDate, NewStartDate
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([
        $hotel_address, $room_number, 
        $end_date, $start_date, 
        $end_date, $start_date
    ]);

    if ($check_stmt->fetchColumn() == 0) {
        // Room became unavailable
        $conn->rollBack();
        $_SESSION['error_message'] = "Sorry, Room $room_number at $hotel_address is no longer available for the selected dates. Please search again.";
        header("Location: search.php");
        exit;
    }

    // 5. Generate unique Booking ID
    $booking_id = uniqid('book_', true); // Generate unique ID in PHP

    // 6. Insert into booking table first
    $insert_booking_sql = "
        INSERT INTO booking (Booking_ID, Customer_ID, Start_Date, End_Date, Creation_Date) 
        VALUES (?, ?, ?, ?, CURDATE()) -- Insert the generated Booking_ID
    ";
    $insert_booking_stmt = $conn->prepare($insert_booking_sql);
    
    $booking_result = $insert_booking_stmt->execute([
        $booking_id, // Use generated ID
        $customer_ssn,
        $start_date,
        $end_date
    ]);

    if ($booking_result) {
        // $booking_id = $conn->lastInsertId(); // No longer needed, we generated it

        // 7. Insert into reserved_by table to link booking to room
        $insert_reserved_sql = "
            INSERT INTO reserved_by (Booking_ID, Hotel_Address, Room_Num)
            VALUES (?, ?, ?)
        ";
        $insert_reserved_stmt = $conn->prepare($insert_reserved_sql);
        $reserved_result = $insert_reserved_stmt->execute([
            $booking_id, // Use the SAME generated ID
            $hotel_address,
            $room_number // Corrected Room_Num based on search.php
        ]);

        if ($reserved_result) {
            $conn->commit();
            $_SESSION['success_message'] = "Booking successful! Your booking ID is $booking_id. Room $room_number at $hotel_address is reserved for you from $start_date to $end_date.";
            header("Location: my_bookings.php"); 
            exit;
        } else {
            $conn->rollBack(); // Rollback if linking failed
            $_SESSION['error_message'] = "Failed to link booking to room. Please try again.";
        }
    } else {
        $conn->rollBack();
        $_SESSION['error_message'] = "Failed to create booking record. Please try again.";
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Booking PDO Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error during booking. Please try again later.";

} catch (Exception $e) {
     if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Booking General Error: " . $e->getMessage());
    $_SESSION['error_message'] = "An unexpected error occurred during booking: " . $e->getMessage();
}

// If we reached here, something went wrong before redirecting
header("Location: search.php");
exit;

?> 