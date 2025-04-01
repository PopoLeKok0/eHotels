<?php
/**
 * e-Hotels My Bookings Page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and header
require_once 'config/database.php'; 
require_once 'includes/header.php'; 

// Check if user is logged in as a customer
$loggedIn = isset($_SESSION['user_id']);
$isCustomer = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

if (!$isCustomer) {
    // Redirect to login if not a customer
    $_SESSION['error_message'] = "Please log in to view your bookings.";
    header("Location: login.php");
    exit;
}

// Get logged-in customer details
$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Customer';

// Initialize variables
$bookings = [];
$message = '';
$messageType = '';

// Display success message from booking/cancellation if set
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $messageType = 'success';
    unset($_SESSION['success_message']); // Clear message after displaying
}
// Display error message if set
if (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    $messageType = 'danger';
    unset($_SESSION['error_message']); // Clear message after displaying
}


try {
    // Get database connection
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch bookings for the current customer
    // Join Booking with Reserved_By, Room, and Hotel to get details
    $stmt = $db->prepare("
        SELECT 
            b.Booking_ID, 
            b.Start_Date, 
            b.End_Date, 
            b.Creation_Date,
            rb.Hotel_Address, 
            rb.Room_Num,
            h.Chain_Name,
            r.Price,
            r.Capacity,
            r.View_Type
        FROM Booking b
        JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
        JOIN Room r ON rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
        JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
        WHERE b.Customer_ID = :customer_id
        ORDER BY b.Start_Date DESC, b.Creation_Date DESC 
    ");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in my_bookings.php: " . $e->getMessage());
    $message = "Could not retrieve your bookings due to a database error.";
    $messageType = "danger";
} catch (Exception $e) {
    error_log("Error in my_bookings.php: " . $e->getMessage());
    $message = "An unexpected error occurred while retrieving your bookings.";
    $messageType = "danger";
}

?>

<div class="container my-5">
    <h1 class="mb-4">My Bookings</h1>
    
    <p>Welcome back, <?= htmlspecialchars($customer_name) ?>! Here are your current and past bookings.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($bookings) && empty($message)): ?>
        <div class="alert alert-info">You currently have no bookings.</div>
    <?php elseif (!empty($bookings)): ?>
        <div class="list-group shadow-sm">
            <?php foreach ($bookings as $booking): 
                // Calculate nights and total price for display
                try {
                    $start = new DateTime($booking['Start_Date']);
                    $end = new DateTime($booking['End_Date']);
                    $interval = $end->diff($start);
                    $nights = $interval->days;
                    $total_price = $booking['Price'] * $nights;
                } catch (Exception $e) {
                    $nights = '?';
                    $total_price = '?'; // Handle potential date errors
                }
                
                // Determine if booking is upcoming, current, or past
                $today = new DateTime();
                $startDate = new DateTime($booking['Start_Date']);
                $endDate = new DateTime($booking['End_Date']);
                $status = '';
                $statusClass = '';

                if ($endDate < $today) {
                    $status = 'Completed';
                    $statusClass = 'text-muted';
                } elseif ($startDate <= $today && $endDate >= $today) {
                    $status = 'Active';
                    $statusClass = 'text-success fw-bold';
                } else { // $startDate > $today
                    $status = 'Upcoming';
                    $statusClass = 'text-primary';
                }

            ?>
                <div class="list-group-item list-group-item-action flex-column align-items-start mb-3 border rounded">
                    <div class="d-flex w-100 justify-content-between mb-2">
                        <h5 class="mb-1"><?= htmlspecialchars($booking['Chain_Name']) ?> - <?= htmlspecialchars($booking['Hotel_Address']) ?></h5>
                        <small class="<?= $statusClass ?>"><?= $status ?></small>
                    </div>
                    <div class="row">
                         <div class="col-md-8">
                            <p class="mb-1">
                                <strong>Room <?= htmlspecialchars($booking['Room_Num']) ?>:</strong> 
                                Check-in: <?= date("D, M j, Y", strtotime($booking['Start_Date'])) ?>, 
                                Check-out: <?= date("D, M j, Y", strtotime($booking['End_Date'])) ?> 
                                (<?= $nights ?> nights)
                            </p>
                             <p class="mb-1 small">
                                 Capacity: <?= htmlspecialchars($booking['Capacity']) ?>, 
                                 View: <?= htmlspecialchars($booking['View_Type']) ?>,
                                 Price/Night: $<?= number_format($booking['Price'], 2) ?>
                             </p>
                            <p class="mb-1 small text-muted">Booked on: <?= date("M j, Y", strtotime($booking['Creation_Date'])) ?> | Booking ID: <?= htmlspecialchars($booking['Booking_ID']) ?></p>
                        </div>
                        <div class="col-md-4 text-md-end">
                             <p class="mb-1 fw-bold">Total: $<?= number_format($total_price, 2) ?></p>
                             <?php if ($status === 'Upcoming'): // Allow cancellation only for upcoming bookings ?>
                                <form action="cancel_booking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                     <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['Booking_ID']) ?>">
                                     <button type="submit" class="btn btn-sm btn-outline-danger mt-2">
                                        <i class="fas fa-times-circle me-1"></i> Cancel Booking
                                     </button>
                                 </form>
                             <?php endif; ?>
                         </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php
// Include footer
include 'includes/footer.php'; 
?> 