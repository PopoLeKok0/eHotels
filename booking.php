<?php
/**
 * e-Hotels Room Booking Page
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463)
 */

// Start output buffering
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and header
require_once 'config/database.php'; // Use the standard config file
require_once 'includes/header.php'; // Assumes header starts session and sets user info

// Check if user is logged in as a customer
$loggedIn = isset($_SESSION['user_id']);
$isCustomer = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

if (!$isCustomer) {
    // Redirect to login if not a customer, saving the intended destination
    $redirectUrl = 'booking.php?' . http_build_query($_GET); // Rebuild query string
    $_SESSION['redirect_after_login'] = $redirectUrl;
    header("Location: login.php");
    exit;
}

// Get logged-in customer details from session
$customer_id = $_SESSION['user_id'] ?? null;

// Initialize variables
$message = '';
$messageType = '';
$hotel_address = $_GET['hotel'] ?? null; // Use 'hotel' from search results link
$room_num = $_GET['room'] ?? null;      // Use 'room' from search results link
$start_date = $_GET['start'] ?? '';     // Use 'start' from search results link
$end_date = $_GET['end'] ?? '';       // Use 'end' from search results link
$room = null;
$hotel = null;
$nights = 0;
$total_price = 0;

// Validate required parameters
if (!$hotel_address || !$room_num || !$start_date || !$end_date) {
    $message = "Missing required booking information (Hotel, Room, or Dates).";
    $messageType = "danger";
} elseif (!$customer_id) {
     $message = "Could not retrieve your customer identification. Please log in again.";
     $messageType = "danger";
} else {
    try {
        // Get database connection using the standard function
        $dbInstance = getDatabase();
        $db = $dbInstance->getConnection();
        
        // Fetch room details with hotel information using correct schema names
        $stmt = $db->prepare("
            SELECT r.*, h.Chain_Name, h.Star_Rating, h.Area
            FROM Room r
            JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
            WHERE r.Hotel_Address = :hotel_address AND r.Room_Num = :room_num
        ");
        $stmt->bindParam(':hotel_address', $hotel_address);
        $stmt->bindParam(':room_num', $room_num, PDO::PARAM_INT);
        $stmt->execute();
        
        $room = $stmt->fetch(PDO::FETCH_ASSOC); // Use fetch directly
        
        if ($room) {
            // Calculate nights and total price
            try {
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                if ($end <= $start) {
                    throw new Exception("End date must be after start date.");
                }
                $interval = $end->diff($start);
                $nights = $interval->days;
                $total_price = $room['Price'] * $nights;
            } catch (Exception $e) {
                $message = "Invalid date format or range.";
                $messageType = "danger";
                $room = null; // Prevent booking if dates are invalid
            }

            // Check availability (combine checks for booking and renting)
            if ($room) { // Only check if room and dates are valid
                $availabilityStmt = $db->prepare("
                    SELECT COUNT(*) FROM (
                        -- Check existing bookings
                        SELECT 1
                        FROM Booking b
                        JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
                        WHERE rb.Hotel_Address = :hotel_address1 AND rb.Room_Num = :room_num1
                          AND (b.Start_Date < :end_date1 AND b.End_Date > :start_date1)
                        UNION ALL
                        -- Check existing rentings
                        SELECT 1
                        FROM Renting rnt
                        JOIN Rented_By rntb ON rnt.Renting_ID = rntb.Renting_ID
                        WHERE rntb.Hotel_Address = :hotel_address2 AND rntb.Room_Num = :room_num2
                          AND (rnt.Start_Date < :end_date2 AND rnt.End_Date > :start_date2)
                    ) AS Conflicts
                ");
                $availabilityStmt->bindParam(':hotel_address1', $hotel_address);
                $availabilityStmt->bindParam(':room_num1', $room_num, PDO::PARAM_INT);
                $availabilityStmt->bindParam(':start_date1', $start_date);
                $availabilityStmt->bindParam(':end_date1', $end_date);
                $availabilityStmt->bindParam(':hotel_address2', $hotel_address);
                $availabilityStmt->bindParam(':room_num2', $room_num, PDO::PARAM_INT);
                $availabilityStmt->bindParam(':start_date2', $start_date);
                $availabilityStmt->bindParam(':end_date2', $end_date);
                $availabilityStmt->execute();
                $conflictCount = $availabilityStmt->fetchColumn(); // Get the count directly
                
                if ($conflictCount > 0) {
                    $message = "Sorry, this room is not available for the selected dates. It might have been booked or rented by someone else.";
                    $messageType = "warning";
                    $room = null; // Prevent booking form from showing if unavailable
                }
            }

        } else {
            $message = "Room not found.";
            $messageType = "danger";
        }
        
        // Process booking form submission
        if ($room && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
            // Re-verify availability just before booking
            // (Same query as above)
             $availabilityStmt->execute(); // Re-execute with same parameters
             $conflictCount = $availabilityStmt->fetchColumn();
             
             if ($conflictCount > 0) {
                 $message = "Unfortunately, the room became unavailable while you were confirming. Please search again.";
                 $messageType = "danger";
             } else {
                // Get Customer_ID from session
                $customer_id = $_SESSION['user_id'] ?? null;
                if (!$customer_id) {
                     // This should ideally not happen if login check passed, but good to double-check
                     throw new Exception("Customer session invalid. Please log in again.");
                }

                // Begin transaction using PDO method
                $db->beginTransaction();
                
                try {
                    // Generate a unique Booking ID (e.g., UUID or similar)
                    // Using uniqid() for simplicity, consider more robust UUID generation if needed
                    $booking_id = uniqid('book-', true); 

                    // 1. Create booking record
                    $bookingStmt = $db->prepare("
                        INSERT INTO Booking (Booking_ID, Customer_ID, Start_Date, End_Date)
                        VALUES (:booking_id, :customer_id, :start_date, :end_date)
                    ");
                    $bookingStmt->bindParam(':booking_id', $booking_id); // Bind the generated ID
                    $bookingStmt->bindParam(':customer_id', $customer_id); 
                    $bookingStmt->bindParam(':start_date', $start_date);
                    $bookingStmt->bindParam(':end_date', $end_date);
                    $bookingStmt->execute();
                    
                    // No need for lastInsertId() if we generated the ID
                    // $booking_id = $db->lastInsertId(); 
                    
                    // 2. Link booking to room in Reserved_By
                    $reserveStmt = $db->prepare("
                        INSERT INTO Reserved_By (Booking_ID, Hotel_Address, Room_Num) -- Removed Customer_SIN
                        VALUES (:booking_id, :hotel_address, :room_num)
                    ");
                    $reserveStmt->bindParam(':booking_id', $booking_id); // Bind the generated ID
                    $reserveStmt->bindParam(':hotel_address', $hotel_address);
                    $reserveStmt->bindParam(':room_num', $room_num, PDO::PARAM_INT);
                    $reserveStmt->execute();
                    
                    // Commit transaction using PDO method
                    $db->commit();
                    
                    // Success message
                    $_SESSION['success_message'] = "Booking successful! Your Booking ID is: " . $booking_id . ". You can view it in 'My Bookings'.";
                    
                    // Redirect to a confirmation page or booking history
                    header("Location: my_bookings.php"); 
                    exit;
                    
                } catch (Exception $e) {
                    // Rollback transaction on error using PDO method
                    $db->rollBack();
                    // Log the detailed error for admin/debug purposes
                    error_log("Booking Error for SIN " . $customer_id . ": " . $e->getMessage());
                    $message = "An error occurred while processing your booking. Please try again later.";
                    $messageType = "danger";
                }
            }
        }

    } catch (PDOException $e) {
        // Log the detailed error
        error_log("Database Error in booking.php: " . $e->getMessage());
        $message = "Database error occurred. Please try again later.";
        $messageType = "danger";
        $room = null; // Ensure booking form isn't shown if DB fails
    } catch (Exception $e) { // Catch date calculation errors etc.
         $message = "An application error occurred: " . $e->getMessage();
         $messageType = "danger";
         $room = null;
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($room): // Only show booking details if room is valid and available ?>
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0">Confirm Your Booking</h1>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <!-- Room Details -->
                            <div class="col-md-6 border-end">
                                <h4 class="mb-3">Room Details</h4>
                                <p><strong>Hotel:</strong> <?= htmlspecialchars($room['Chain_Name']) ?></p>
                                <p><strong>Address:</strong> <?= htmlspecialchars($hotel_address) ?></p>
                                <p><strong>Area:</strong> <?= htmlspecialchars($room['Area']) ?></p>
                                <p><strong>Room #:</strong> <?= htmlspecialchars($room_num) ?></p>
                                <p><strong>Stars:</strong> <?= str_repeat('<i class="fas fa-star text-warning"></i>', $room['Star_Rating']) ?></p>
                                <p><strong>Capacity:</strong> <?= htmlspecialchars($room['Capacity']) ?> <?= ($room['Capacity'] == 1) ? 'Person' : 'People' ?></p>
                                <p><strong>View:</strong> <?= htmlspecialchars($room['View_Type']) ?></p>
                                <p><strong>Amenities:</strong> <?= htmlspecialchars($room['Amenities']) ?></p>
                                <p><strong>Price per night:</strong> $<?= number_format($room['Price'], 2) ?></p>
                            </div>
                            <!-- Booking Summary -->
                            <div class="col-md-6">
                                <h4 class="mb-3">Booking Summary</h4>
                                <p><strong>Check-in:</strong> <?= date("l, F j, Y", strtotime($start_date)) ?></p>
                                <p><strong>Check-out:</strong> <?= date("l, F j, Y", strtotime($end_date)) ?></p>
                                <p><strong>Duration:</strong> <?= $nights ?> nights</p>
                                <hr>
                                <p class="fs-5 fw-bold">Total Price: $<?= number_format($total_price, 2) ?></p>
                                <hr>
                                <h4 class="h5 mt-4 mb-3">Your Information</h4>
                                <p><strong>Name:</strong> <?= htmlspecialchars($customer_name) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($customer_email) ?></p>
                                <p><strong>Address:</strong> <?= htmlspecialchars($customer_address) ?></p>
                                <!-- SIN is sensitive, usually not displayed -->
                                <!-- <p><strong>SIN:</strong> <?= htmlspecialchars($customer_sin) ?></p> --> 
                            </div>
                        </div>
                        
                        <hr>
                        
                        <form method="post" action="" class="mt-4">
                            <!-- Hidden fields to pass necessary data -->
                            <input type="hidden" name="hotel_address" value="<?= htmlspecialchars($hotel_address) ?>">
                            <input type="hidden" name="room_num" value="<?= htmlspecialchars($room_num) ?>">
                            <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                            
                            <p class="text-muted small">By clicking "Confirm Booking", you agree to the hotel's terms and conditions. Payment details will be handled at check-in or according to hotel policy.</p>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="search.php?<?= http_build_query($_GET) // Go back to search with same params ?>" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" name="confirm_booking" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i>Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif (empty($message)): // If no error message and no room, show generic message ?>
                <div class="alert alert-info">Please select a room from the search results to start booking.</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
</body>
</html>
<?php
// End output buffering and flush output
ob_end_flush();
?> 