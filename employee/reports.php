<?php
/**
 * e-Hotels Employee Reports Page
 */

// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied. Please log in as an employee.";
    header("Location: ../login.php");
    exit;
}

require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$employee_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

$availableRoomsData = [];
$hotelCapacityData = [];
// Add arrays for optional views if needed
// $hotelBookingStats = [];
// $customerHistory = [];

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch data from View 1: available_rooms_per_area
    $stmt1 = $db->query("SELECT Area, Total_Available_Rooms FROM available_rooms_per_area ORDER BY Area ASC");
    if ($stmt1) $availableRoomsData = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Fetch data from View 2: aggregated_hotel_capacity
    $stmt2 = $db->query("SELECT Hotel_Address, Chain_Name, Total_Capacity FROM aggregated_hotel_capacity ORDER BY Chain_Name, Hotel_Address ASC");
    if ($stmt2) $hotelCapacityData = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch data from Optional View 3: hotel_booking_statistics
    // $stmt3 = $db->query("SELECT * FROM hotel_booking_statistics ORDER BY Total_Bookings DESC");
    // if ($stmt3) $hotelBookingStats = $stmt3->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Error fetching report views: " . $e->getMessage());
    $message = "Could not load some or all report data.";
    $messageType = 'danger';
}

?>

<div class="container my-5">
    <h1 class="mb-4">Hotel System Reports</h1>

     <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- View 1 Display -->
    <div class="card mb-4">
        <div class="card-header">
           <h2 class="h5 mb-0">Available Rooms Per Area (Current)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($availableRoomsData)): ?>
                <div class="alert alert-warning">No availability data found for View 1.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Area</th>
                                <th>Total Available Rooms</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableRoomsData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Area']) ?></td>
                                <td><?= htmlspecialchars($row['Total_Available_Rooms']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
             <?php endif; ?>
        </div>
         <div class="card-footer text-muted small">
            Based on view: `available_rooms_per_area`
        </div>
    </div>

    <!-- View 2 Display -->
     <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Total Room Capacity Per Hotel</h2>
        </div>
        <div class="card-body">
             <?php if (empty($hotelCapacityData)): ?>
                 <div class="alert alert-warning">No capacity data found for View 2.</div>
            <?php else: ?>
                 <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Hotel Chain</th>
                                <th>Hotel Address</th>
                                <th>Total Guest Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotelCapacityData as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Chain_Name']) ?></td>
                                <td><?= htmlspecialchars($row['Hotel_Address']) ?></td>
                                <td><?= htmlspecialchars($row['Total_Capacity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
             <?php endif; ?>
        </div>
        <div class="card-footer text-muted small">
            Based on view: `aggregated_hotel_capacity`
        </div>
    </div>
    
    <!-- Placeholder for Optional View 3 -->
    <!-- 
    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0">Hotel Booking Statistics</h2></div>
        <div class="card-body">... Table for hotel_booking_statistics ...</div>
        <div class="card-footer text-muted small">Based on view: `hotel_booking_statistics`</div>
    </div>
    -->
    
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>

</div>

<?php
require_once '../includes/footer.php'; 
?>
?>