<?php
session_start();
require_once '../config/database.php';
require_once '../utils/log_error.php'; // Assuming you have a logging function

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied. Please log in as an employee.";
    header("Location: ../login.php");
    exit;
}

// Ensure this is a POST request and SSN is provided
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ssn']) || empty(trim($_POST['ssn']))) {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: manage_employees.php");
    exit;
}

$ssn_to_delete = trim($_POST['ssn']);
$db = getDatabase();
$conn = $db->getConnection();

// Prevent self-deletion
if ($ssn_to_delete === $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own account.";
    header("Location: manage_employees.php");
    exit;
}

// Start transaction
try {
    $conn->beginTransaction();

    // Check if the employee exists
    $stmt = $conn->prepare("SELECT Full_Name, Position FROM Employee WHERE SSN = ?");
    $stmt->execute([$ssn_to_delete]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        $_SESSION['error_message'] = "Employee not found.";
        header("Location: manage_employees.php");
        exit;
    }
    
    $employee_name = $employee['Full_Name'];
    $employee_position = $employee['Position'];

    // If the employee is a Manager, set the Manager_SSN to NULL in associated hotels
    if ($employee_position === 'Manager') {
        $update_hotel_stmt = $conn->prepare("UPDATE Hotel SET Manager_SSN = NULL WHERE Manager_SSN = ?");
        $update_hotel_stmt->execute([$ssn_to_delete]);
    }

    // --- Handle other potential dependencies ---
    // If employees are linked to Renting table (e.g., Processed_By_SSN):
    // You might need to decide how to handle this. Options:
    // 1. SET NULL: Update Renting SET Processed_By_SSN = NULL WHERE Processed_By_SSN = ?
    // 2. Prevent Deletion: Check if rentings exist and block deletion.
    // 3. Cascade (Not recommended here unless business logic dictates)
    // Example (SET NULL - adapt column name if needed):
    // $update_renting_stmt = $conn->prepare("UPDATE Renting SET Employee_SSN_Check_In = NULL WHERE Employee_SSN_Check_In = ?");
    // $update_renting_stmt->execute([$ssn_to_delete]);
    // Note: The current schema might not link Renting directly to Employee. Add if necessary.
    // For simplicity now, we assume no critical dependencies block deletion, 
    // besides the Manager link handled above.

    // Delete the employee
    $delete_stmt = $conn->prepare("DELETE FROM Employee WHERE SSN = ?");
    $delete_result = $delete_stmt->execute([$ssn_to_delete]);

    if ($delete_result) {
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = "Employee '" . htmlspecialchars($employee_name) . "' (SSN: " . htmlspecialchars($ssn_to_delete) . ") deleted successfully.";
    } else {
        // Rollback if deletion failed
        $conn->rollBack();
        $_SESSION['error_message'] = "Failed to delete employee.";
    }

} catch (PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Check for foreign key constraint errors (code 1451 in MySQL/MariaDB)
    if ($e->getCode() == '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451)) { 
        $_SESSION['error_message'] = "Cannot delete employee '" . htmlspecialchars($employee_name ?? $ssn_to_delete) . "'. They might be associated with existing records (e.g., processed rentings or bookings). Please reassign responsibilities or archive related records first.";
    } else {
        $_SESSION['error_message'] = "Database error during deletion: " . $e->getMessage();
    }
} catch (Exception $e) {
    // Rollback on general error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
     $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
}

// Redirect back to the management page
header("Location: manage_employees.php");
exit;