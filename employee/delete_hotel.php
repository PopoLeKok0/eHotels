<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; 
    header("Location: ../login.php"); 
    exit;
}
require_once '../config/database.php'; 

$hotelId = $_POST['hotel_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$hotelId || !ctype_digit($hotelId)) {
    $_SESSION['error_message'] = "Invalid request to delete hotel.";
    header("Location: manage_hotels.php");
    exit;
}

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Fetch hotel name before deleting for the success message
    $stmt_name = $db->prepare("SELECT Hotel_Name FROM Hotel WHERE Hotel_ID = :id");
    $stmt_name->bindParam(':id', $hotelId, PDO::PARAM_INT);
    $stmt_name->execute();
    $hotelName = $stmt_name->fetchColumn();

    // Begin transaction
    $db->beginTransaction();

    // Attempt to delete the hotel
    // Assumption: The database schema might have ON DELETE CASCADE for Rooms, 
    // or ON DELETE RESTRICT/SET NULL for related records (Booking, Renting via Room).
    // If RESTRICT is used and dependencies exist, this will throw a PDOException (caught below).
    $stmt_delete = $db->prepare("DELETE FROM Hotel WHERE Hotel_ID = :id");
    $stmt_delete->bindParam(':id', $hotelId, PDO::PARAM_INT);
    $deleted = $stmt_delete->execute();

    $rowCount = $stmt_delete->rowCount();

    $db->commit();

    if ($deleted && $rowCount > 0) {
        $_SESSION['success_message'] = "Hotel '" . htmlspecialchars($hotelName ?: 'ID: ' . $hotelId) . "' and its associated rooms (if any, due to CASCADE) deleted successfully.";
    } elseif ($rowCount === 0) {
        $_SESSION['error_message'] = "Hotel (ID: " . htmlspecialchars($hotelId) . ") not found or already deleted.";
    } else {
        // This case might not be reached if execute() throws an exception on failure
        $_SESSION['error_message'] = "Failed to delete hotel (ID: " . htmlspecialchars($hotelId) . "). An unknown error occurred during deletion.";
    }

} catch (PDOException $e) {
     if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Hotel DB Error: " . $e->getMessage() . " SQLSTATE: " . $e->getCode());
    // Provide a more user-friendly error based on common SQLSTATE codes for foreign key violations
    if ($e->getCode() == '23000') { // General Integrity constraint violation (covers foreign keys)
        $_SESSION['error_message'] = "Cannot delete hotel '" . htmlspecialchars($hotelName ?: 'ID: ' . $hotelId) . "'. It likely has associated bookings, rentings, or other records that prevent deletion. Please remove these associations first.";
    } else {
        $_SESSION['error_message'] = "Database error occurred while deleting hotel: " . $e->getMessage();
    }
} catch (Exception $e) {
     if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Hotel Error: " . $e->getMessage());
    $_SESSION['error_message'] = "An unexpected error occurred: " . $e->getMessage();
}

// Redirect back to the management page
header("Location: manage_hotels.php");
exit; 
?> 