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

// Get Database connection
$db = getDatabase();
$conn = $db->getConnection();
$view_errors = [];
$available_rooms_data = [];
$hotel_capacity_data = [];

// Fetch data from View 1: Available Rooms Per Area
try {
    $stmt = $conn->query("SELECT Area, NumberOfAvailableRooms FROM AvailableRoomsPerArea ORDER BY Area");
    $available_rooms_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $view_errors[] = "Error fetching available rooms per area: " . $e->getMessage();
}

// Fetch data from View 2: Hotel Room Capacity
try {
    $stmt = $conn->query("SELECT Hotel_Address, TotalCapacity FROM HotelRoomCapacity ORDER BY Hotel_Address");
    $hotel_capacity_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $view_errors[] = "Error fetching hotel room capacity: " . $e->getMessage();
}

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
    // Display errors fetching view data
    if (!empty($view_errors)) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Could not load some dashboard data:</strong><ul>';
        foreach ($view_errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    ?>

    <div class="row g-4 mb-4">
        <!-- Check-in Section -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-calendar-check me-2 text-primary"></i>Process Check-in</h5>
                    <p class="card-text flex-grow-1">Find a customer's booking and convert it to a renting.</p>
                    <a href="check_in.php" class="btn btn-primary mt-auto">Go to Check-in</a> 
                </div>
            </div>
        </div>

        <!-- Direct Rental Section -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-key me-2 text-success"></i>Direct Room Rental</h5>
                    <p class="card-text flex-grow-1">Rent an available room directly for a walk-in customer.</p>
                    <a href="direct_rental.php" class="btn btn-success mt-auto">Go to Direct Rental</a>
                </div>
            </div>
        </div>

        <!-- Customer Management -->
         <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><i class="fas fa-users me-2 text-info"></i>Manage Customers</h5>
                    <p class="card-text flex-grow-1">View, add, edit, or delete customer information.</p>
                    <a href="manage_customers.php" class="btn btn-info mt-auto">Manage Customers</a> 
                </div>
            </div>
        </div>
    </div> <!-- /row for core actions -->

    <hr class="my-4">

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Available Rooms by Area (View 1)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($available_rooms_data) && empty($view_errors)): ?>
                        <p class="text-muted">No data available or view not created yet.</p>
                    <?php elseif (!empty($available_rooms_data)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Area</th>
                                        <th>Available Rooms</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($available_rooms_data as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Area']) ?></td>
                                            <td><?= htmlspecialchars($row['NumberOfAvailableRooms']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-hotel me-2"></i>Total Room Capacity by Hotel (View 2)</h5>
                </div>
                <div class="card-body">
                     <?php if (empty($hotel_capacity_data) && empty($view_errors)): ?>
                        <p class="text-muted">No data available or view not created yet.</p>
                    <?php elseif (!empty($hotel_capacity_data)): ?>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Hotel Address</th>
                                        <th>Total Capacity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hotel_capacity_data as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Hotel_Address']) ?></td>
                                            <td><?= htmlspecialchars($row['TotalCapacity']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                     <?php endif; ?>
                </div>
            </div>
        </div>
    </div><!-- /row for views -->


</div> <!-- /container -->

<?php
// Include main footer for now
require_once '../includes/footer.php'; 
?> 