<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; 
    header("Location: ../login.php"); 
    exit;
}
require_once '../config/database.php'; 

$customerId = $_POST['customer_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$customerId) {
    $_SESSION['error_message'] = "Invalid request to delete customer.";
    header("Location: manage_customers.php");
    exit;
}

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Fetch customer name before deleting for the success message
    $stmt_name = $db->prepare("SELECT Full_Name FROM Customer WHERE Customer_ID = :id");
    $stmt_name->bindParam(':id', $customerId);
    $stmt_name->execute();
    $customerName = $stmt_name->fetchColumn();

    // Begin transaction in case related data needs deletion (though triggers handle archives)
    $db->beginTransaction();

    // Delete the customer
    // Note: ON DELETE CASCADE in schema should handle related Booking, Customer_Email
    //       Archive triggers should handle copying Booking/Renting history before cascade
    $stmt_delete = $db->prepare("DELETE FROM Customer WHERE Customer_ID = :id");
    $stmt_delete->bindParam(':id', $customerId);
    $deleted = $stmt_delete->execute();

    $db->commit();

    if ($deleted) {
        $_SESSION['success_message'] = "Customer '" . htmlspecialchars($customerName ?: $customerId) . "' deleted successfully.";
    } else {
        // This case might not be reached if execute() throws an exception on failure
        $_SESSION['error_message'] = "Failed to delete customer (ID: " . htmlspecialchars($customerId) . "). They might have associated records preventing deletion.";
    }

} catch (PDOException $e) {
     if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Customer DB Error: " . $e->getMessage());
    // Provide a more user-friendly error based on SQLSTATE if needed
    if ($e->getCode() == '23000') { // Integrity constraint violation
        $_SESSION['error_message'] = "Cannot delete customer (ID: " . htmlspecialchars($customerId) . "). They likely have active rentings or other associated records preventing deletion.";
    } else {
        $_SESSION['error_message'] = "Database error occurred while deleting customer.";
    }
} catch (Exception $e) {
     if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Customer Error: " . $e->getMessage());
    $_SESSION['error_message'] = "An unexpected error occurred: " . $e->getMessage();
}

// Redirect back to the management page
header("Location: manage_customers.php");
exit; 