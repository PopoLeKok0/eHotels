<?php
/**
 * e-Hotels Employee Check-in Page
 * Convert a booking to a renting.
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
$search_term = $_POST['search_term'] ?? '';
$booking_to_process = null;
$search_results = [];
$message = '';
$messageType = '';

$dbInstance = getDatabase();
$db = $dbInstance->getConnection();

// --- Handle Search Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_booking'])) {
    if (!empty($search_term)) {
        try {
            // Search by Booking ID or Customer Name/Email
            $stmt = $db->prepare("
                SELECT 
                    b.Booking_ID, b.Start_Date, b.End_Date, b.Customer_ID,
                    c.Full_Name AS Customer_Name, c.Email_Address AS Customer_Email,
                    rb.Hotel_Address, rb.Room_Num,
                    h.Chain_Name
                FROM Booking b
                JOIN Customer c ON b.Customer_ID = c.Customer_ID
                JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
                JOIN Hotel h ON rb.Hotel_Address = h.Hotel_Address
                WHERE (b.Booking_ID LIKE :term 
                   OR c.Full_Name LIKE :term 
                   OR c.Email_Address LIKE :term)
                  AND b.Start_Date <= CURDATE() -- Only show bookings starting today or earlier
                ORDER BY b.Start_Date ASC
                LIMIT 10 -- Limit results for performance
            ");
            $like_term = '%' . $search_term . '%';
            $stmt->bindParam(':term', $like_term);
            $stmt->execute();
            $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($search_results)) {
                $message = "No matching active bookings found for '" . htmlspecialchars($search_term) . "'. Check Booking ID, Customer Name/Email, or start date.";
                $messageType = 'warning';
            }

        } catch (Exception $e) {
            error_log("Check-in search error: " . $e->getMessage());
            $message = "Error searching for bookings.";
            $messageType = 'danger';
        }
    } else {
        $message = "Please enter a Booking ID, Customer Name, or Email to search.";
        $messageType = 'warning';
    }
}

// --- Handle Check-in Confirmation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_check_in'])) {
    $booking_id_to_confirm = $_POST['booking_id'] ?? null;

    if ($booking_id_to_confirm) {
        $db->beginTransaction();
        try {
            // 1. Fetch original booking details again (to prevent race conditions)
            $stmt_get = $db->prepare("
                 SELECT b.Booking_ID, b.Start_Date, b.End_Date, b.Customer_ID,
                        rb.Hotel_Address, rb.Room_Num
                 FROM Booking b 
                 JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID 
                 WHERE b.Booking_ID = :booking_id
            ");
            $stmt_get->bindParam(':booking_id', $booking_id_to_confirm);
            $stmt_get->execute();
            $booking_data = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if (!$booking_data) {
                throw new Exception("Booking not found or already processed.");
            }

            // 2. Create Renting ID
            $renting_id = uniqid('rent-', true);
            $check_in_date = date('Y-m-d'); // Use current date for check-in
            $direct_renting = false; // This originated from a booking

            // 3. Insert into Renting table
            $stmt_rent = $db->prepare("
                INSERT INTO Renting (Renting_ID, Start_Date, End_Date, Check_in_Date, Direct_Renting, Customer_ID)
                VALUES (:renting_id, :start_date, :end_date, :check_in_date, :direct_renting, :customer_id)
            ");
            $stmt_rent->bindParam(':renting_id', $renting_id);
            $stmt_rent->bindParam(':start_date', $booking_data['Start_Date']);
            $stmt_rent->bindParam(':end_date', $booking_data['End_Date']);
            $stmt_rent->bindParam(':check_in_date', $check_in_date);
            $stmt_rent->bindParam(':direct_renting', $direct_renting, PDO::PARAM_BOOL);
            $stmt_rent->bindParam(':customer_id', $booking_data['Customer_ID']);
            $stmt_rent->execute();

            // 4. Insert into Rented_By table
            $stmt_rented = $db->prepare("
                INSERT INTO Rented_By (Renting_ID, Hotel_Address, Room_Num)
                VALUES (:renting_id, :hotel_address, :room_num)
            ");
            $stmt_rented->bindParam(':renting_id', $renting_id);
            $stmt_rented->bindParam(':hotel_address', $booking_data['Hotel_Address']);
            $stmt_rented->bindParam(':room_num', $booking_data['Room_Num'], PDO::PARAM_INT);
            $stmt_rented->execute();

            // 5. Insert into Processes table (link employee to renting)
            $stmt_proc = $db->prepare("INSERT INTO Processes (SSN, Renting_ID) VALUES (:ssn, :renting_id)");
            $stmt_proc->bindParam(':ssn', $employee_id); // Employee ID is SSN
            $stmt_proc->bindParam(':renting_id', $renting_id);
            $stmt_proc->execute();

            // 6. Delete original Booking (and Reserved_By via CASCADE constraint)
            $stmt_del_book = $db->prepare("DELETE FROM Booking WHERE Booking_ID = :booking_id");
            $stmt_del_book->bindParam(':booking_id', $booking_id_to_confirm);
            $stmt_del_book->execute();

            // Commit transaction
            $db->commit();

            $_SESSION['success_message'] = "Check-in successful! Booking ID " . htmlspecialchars($booking_id_to_confirm) . " converted to Renting ID " . htmlspecialchars($renting_id) . ".";
            header("Location: index.php"); // Redirect back to dashboard
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Check-in processing error: " . $e->getMessage());
            $message = "Error processing check-in: " . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = "No booking selected for check-in.";
        $messageType = 'danger';
    }
}

?>

<div class="container my-5">
    <h1 class="mb-4">Process Check-in</h1>
    <p>Search for an upcoming or current booking by Booking ID, Customer Name, or Customer Email to convert it into a renting record.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="POST" action="check_in.php" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Enter Booking ID, Customer Name, or Email..." 
                   name="search_term" value="<?= htmlspecialchars($search_term) ?>" required>
            <button class="btn btn-primary" type="submit" name="search_booking">
                <i class="fas fa-search me-1"></i> Search Bookings
            </button>
        </div>
    </form>

    <!-- Search Results -->
    <?php if (!empty($search_results)): ?>
        <h2 class="h4 mt-4 mb-3">Matching Bookings Found</h2>
        <div class="list-group">
            <?php foreach ($search_results as $booking): ?>
                <div class="list-group-item">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-1">Booking ID: <?= htmlspecialchars($booking['Booking_ID']) ?></h5>
                            <p class="mb-1">
                                <strong>Customer:</strong> <?= htmlspecialchars($booking['Customer_Name']) ?> (<?= htmlspecialchars($booking['Customer_Email'] ?? 'N/A') ?>)<br>
                                <strong>Hotel:</strong> <?= htmlspecialchars($booking['Chain_Name']) ?> - <?= htmlspecialchars($booking['Hotel_Address']) ?><br>
                                <strong>Room:</strong> #<?= htmlspecialchars($booking['Room_Num']) ?><br>
                                <strong>Dates:</strong> <?= date("M j, Y", strtotime($booking['Start_Date'])) ?> to <?= date("M j, Y", strtotime($booking['End_Date'])) ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <!-- Form to select this booking for check-in -->
                            <form method="POST" action="check_in.php">
                                <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['Booking_ID']) ?>">
                                <button type="submit" name="select_booking_for_checkin" class="btn btn-success">
                                    <i class="fas fa-calendar-check me-1"></i> Select for Check-in
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Check-in Confirmation Area (Shows when a booking is selected) -->
    <?php 
    // Handle display after selection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_booking_for_checkin'])) {
        $selected_booking_id = $_POST['booking_id'] ?? null;
        if ($selected_booking_id) {
             try {
                 // Fetch the selected booking details again for display
                 $stmt_select = $db->prepare("
                     SELECT b.Booking_ID, b.Start_Date, b.End_Date, b.Customer_ID,
                            c.Full_Name AS Customer_Name, c.Email_Address AS Customer_Email,
                            rb.Hotel_Address, rb.Room_Num, h.Chain_Name, r.Price
                     FROM Booking b
                     JOIN Customer c ON b.Customer_ID = c.Customer_ID
                     JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
                     JOIN Hotel h ON rb.Hotel_Address = h.Hotel_Address
                     JOIN Room r ON rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
                     WHERE b.Booking_ID = :booking_id
                 ");
                 $stmt_select->bindParam(':booking_id', $selected_booking_id);
                 $stmt_select->execute();
                 $booking_to_process = $stmt_select->fetch(PDO::FETCH_ASSOC);

                 if (!$booking_to_process) {
                     $message = "Selected booking could not be found.";
                     $messageType = 'danger';
                 }
             } catch (Exception $e) {
                 error_log("Check-in selection error: " . $e->getMessage());
                 $message = "Error retrieving selected booking details.";
                 $messageType = 'danger';
             }
        }
    }

    if ($booking_to_process):
        // Calculate nights/price for display
        try {
            $start = new DateTime($booking_to_process['Start_Date']);
            $end = new DateTime($booking_to_process['End_Date']);
            $interval = $end->diff($start);
            $nights = $interval->days;
            $total_price = $booking_to_process['Price'] * $nights;
        } catch (Exception $e) { $nights = 0; $total_price = 0; }
    ?>
        <div class="card border-success mt-5 shadow">
            <div class="card-header bg-success text-white">
                <h2 class="h4 mb-0">Confirm Check-in for Booking ID: <?= htmlspecialchars($booking_to_process['Booking_ID']) ?></h2>
            </div>
            <div class="card-body">
                <p><strong>Customer:</strong> <?= htmlspecialchars($booking_to_process['Customer_Name']) ?></p>
                <p><strong>Hotel:</strong> <?= htmlspecialchars($booking_to_process['Chain_Name']) ?> - <?= htmlspecialchars($booking_to_process['Hotel_Address']) ?></p>
                <p><strong>Room:</strong> #<?= htmlspecialchars($booking_to_process['Room_Num']) ?></p>
                <p><strong>Dates:</strong> <?= date("M j, Y", strtotime($booking_to_process['Start_Date'])) ?> to <?= date("M j, Y", strtotime($booking_to_process['End_Date'])) ?> (<?= $nights ?> nights)</p>
                <p><strong>Total Price (for reference):</strong> $<?= number_format($total_price, 2) ?></p>
                
                <form method="POST" action="check_in.php" onsubmit="return confirm('Are you sure you want to check in this customer and convert the booking to a renting?')">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_to_process['Booking_ID']) ?>">
                    <button type="submit" name="confirm_check_in" class="btn btn-lg btn-success">
                        <i class="fas fa-check-circle me-2"></i> Confirm Check-in & Create Renting
                    </button>
                    <a href="check_in.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
require_once '../includes/footer.php'; 
?> 