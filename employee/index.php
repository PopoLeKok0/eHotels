<?php
/**
 * e-Hotels Employee Dashboard
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    // Redirect non-employees or non-logged-in users to main login
    $_SESSION['error_message'] = "Access denied. Please log in as an employee.";
    header("Location: ../login.php"); // Go up one level to the main login
    exit;
}

// Include necessary files (adjust paths as needed)
require_once '../config/database.php'; 
// You might create a specific employee header/footer later
require_once '../includes/header.php'; 

// Get logged-in employee details
$employee_id = $_SESSION['user_id']; // Assuming user_id holds SSN for employees
$employee_name = $_SESSION['employee_name'] ?? 'Employee';
$employee_position = $_SESSION['employee_position'] ?? 'N/A';

?>

<div class="container my-5">
    <h1 class="mb-4">Employee Dashboard</h1>
    
    <p>Welcome, <?= htmlspecialchars($employee_name) ?> (<?= htmlspecialchars($employee_position) ?>)! </p>

    <?php 
    // Display any session messages (e.g., from successful operations)
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'.htmlspecialchars($_SESSION['success_message']).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'.htmlspecialchars($_SESSION['error_message']).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="row g-4">
        <!-- Check-in Section -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-calendar-check me-2"></i>Process Check-in</h5>
                    <p class="card-text">Find a customer's booking and convert it to a renting.</p>
                    <a href="check_in.php" class="btn btn-primary">Go to Check-in</a> 
                </div>
            </div>
        </div>

        <!-- Direct Rental Section -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-key me-2"></i>Direct Room Rental</h5>
                    <p class="card-text">Rent an available room directly for a walk-in customer.</p>
                    <a href="direct_rental.php" class="btn btn-success">Go to Direct Rental</a>
                </div>
            </div>
        </div>

        <!-- Room Management (Example) -->
         <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-door-open me-2"></i>Manage Rooms</h5>
                    <p class="card-text">View room status, update details, and manage availability.</p>
                    <a href="manage_rooms.php" class="btn btn-secondary">Manage Rooms</a> <!-- Link TBD -->
                </div>
            </div>
        </div>

        <!-- Customer Management (Example) -->
         <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users me-2"></i>Manage Customers</h5>
                    <p class="card-text">View customer information and booking history.</p>
                    <a href="manage_customers.php" class="btn btn-secondary">Manage Customers</a> <!-- Link TBD -->
                </div>
            </div>
        </div>
        
         <!-- View Reports (Example) -->
         <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>View Reports</h5>
                    <p class="card-text">Access database views like rooms per area and hotel capacity.</p>
                    <a href="reports.php" class="btn btn-info">View Reports</a> <!-- Link TBD -->
                </div>
            </div>
        </div>

    </div> <!-- /row -->

</div>

<?php
// Include main footer for now
require_once '../includes/footer.php'; 
?> 