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
$search_term = $_GET['search_term'] ?? ''; // Use GET for search term persistence
$booking_to_process = null;
$search_results = [];
$message = $_SESSION['checkin_message'] ?? null;
$messageType = $_SESSION['checkin_message_type'] ?? '';

// Clear session messages after reading
if (isset($_SESSION['checkin_message'])) {
    unset($_SESSION['checkin_message']);
    unset($_SESSION['checkin_message_type']);
}

$dbInstance = getDatabase();
$db = $dbInstance->getConnection();

// --- Perform Search if search term exists ---
if (!empty($search_term)) {
    try {
        // Search by Booking ID or Customer Name/Email/SSN
        // Show only bookings starting today or in the past, that haven't already been converted (i.e., still exist in Booking table)
        $stmt = $db->prepare("
            SELECT 
                b.Booking_ID, b.Start_Date, b.End_Date, b.Customer_ID,
                c.Full_Name AS Customer_Name, c.Email_Address AS Customer_Email,
                rb.Hotel_Address, rb.Room_Num,
                h.Chain_Name,
                r.Price AS Price_Per_Night
            FROM Booking b
            JOIN Customer c ON b.Customer_ID = c.Customer_ID -- Corrected c.SSN_SIN to c.Customer_ID
            JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
            JOIN Hotel h ON rb.Hotel_Address = h.Hotel_Address
            JOIN Room r ON rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
            WHERE (b.Booking_ID = ? 
               OR c.Full_Name LIKE ? 
               OR c.Email_Address LIKE ?
               OR b.Customer_ID LIKE ?)
              AND b.Start_Date <= CURDATE() -- Allow check-in on or after start date
              -- Additional check: Ensure no Renting record exists for this booking_id maybe? Requires schema change.
            ORDER BY b.Start_Date ASC
            LIMIT 10 -- Limit results for performance
        ");
        $like_term = '%' . $search_term . '%';
        $stmt->execute([
            $search_term, // For Booking_ID = ?
            $like_term,   // For Full_Name LIKE ?
            $like_term,   // For Email_Address LIKE ?
            $like_term    // For Customer_ID LIKE ?
        ]); // Pass params for each placeholder
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($search_results)) {
            $message = "No matching active bookings found for '" . htmlspecialchars($search_term) . "'. Check Booking ID, Customer Name/Email/SSN, or start date.";
            $messageType = 'warning';
        }

    } catch (Exception $e) {
        error_log("Check-in search error: " . $e->getMessage());
        $message = "Error searching for bookings: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// --- Handle Check-in Action (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_check_in'])) {
    $booking_id_to_confirm = $_POST['booking_id'] ?? null;
    $payment_amount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT);
    $payment_method = trim($_POST['payment_method'] ?? 'Cash'); // Example payment method

    if ($booking_id_to_confirm && $payment_amount !== false && $payment_amount >= 0) {
        $db->beginTransaction();
        try {
            // 1. Fetch original booking details again (lock for update if possible/needed)
            $stmt_get = $db->prepare("
                 SELECT b.Booking_ID, b.Start_Date, b.End_Date, b.Customer_ID,
                        rb.Hotel_Address, rb.Room_Num
                 FROM Booking b 
                 JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID 
                 WHERE b.Booking_ID = ?
            "); // Optional: Add FOR UPDATE if database supports it and high concurrency is expected
            $stmt_get->execute([$booking_id_to_confirm]);
            $booking_data = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if (!$booking_data) {
                throw new Exception("Booking not found or already processed.");
            }
            
            // --- ADDED DATE VALIDATION ---
            $booking_start_date = $booking_data['Start_Date'];
            $booking_end_date = $booking_data['End_Date'];
            if (strtotime($booking_end_date) <= strtotime($booking_start_date)) {
                throw new Exception("Check-in failed: Booking End Date (" . htmlspecialchars($booking_end_date) . ") must be after Start Date (" . htmlspecialchars($booking_start_date) . ").");
            }
            // --- END ADDED DATE VALIDATION ---

            // Optional: Add check to ensure start date is today or in the past?
            $startDateObj = new DateTime($booking_data['Start_Date']);
            $today = new DateTime('today');
            if ($startDateObj > $today) {
                // This check prevents checking in *before* the booked start date.
                // Comment out if early check-in is allowed.
                 // throw new Exception("Cannot check-in before the booked start date ({$booking_data['Start_Date']}).");
            }

            // 2. Generate a unique Renting ID (assuming it's not auto-increment)
            $renting_id = uniqid('rent_', true); // Generate unique ID like rent_xxxxxxxxxxxx.xxxxxx

            // 3. Insert into Renting table using the generated ID
            $check_in_date = date('Y-m-d'); // Actual check-in date is today
            $stmt_rent = $db->prepare("
                INSERT INTO Renting (Renting_ID, Customer_ID, Start_Date, End_Date, Payment_Amount) 
                VALUES (?, ?, ?, ?, ?) 
            ");
            $renting_inserted = $stmt_rent->execute([
                $renting_id,                   // Use generated ID
                $booking_data['Customer_ID'],
                $booking_data['Start_Date'],   // Use ORIGINAL booking start date for record
                $booking_data['End_Date'],     // Use ORIGINAL booking end date
                $payment_amount
            ]);

            if (!$renting_inserted) {
                 throw new Exception("Failed to create renting record.");
            }

            // 4. Insert into Rented_By table to link Renting to Room
            $stmt_rented = $db->prepare("
                INSERT INTO Rented_By (Renting_ID, Hotel_Address, Room_Num)
                VALUES (?, ?, ?)
            ");
            $rented_by_inserted = $stmt_rented->execute([
                $renting_id,
                $booking_data['Hotel_Address'],
                $booking_data['Room_Num']
            ]);
            
            if (!$rented_by_inserted) {
                 throw new Exception("Failed to link renting to room.");
            }
            
            // 5. Insert into Processes table to link Employee to Renting
            $stmt_proc = $db->prepare("INSERT INTO Processes (SSN, Renting_ID) VALUES (?, ?)");
            $proc_inserted = $stmt_proc->execute([$employee_id, $renting_id]); // Use generated ID
            
            if (!$proc_inserted) {
                 error_log("Failed to link employee $employee_id to renting $renting_id in processes table.");
                 // Decide if this is a fatal error - potentially throw Exception here if required
            }

            // 6. Delete original Booking (and Reserved_By via CASCADE constraint if set)
            // Important: Ensure Reserved_By has ON DELETE CASCADE for Booking_ID FK
            $stmt_del_book = $db->prepare("DELETE FROM Booking WHERE Booking_ID = ?");
            $stmt_del_book->execute([$booking_id_to_confirm]);

            // Commit transaction
            $db->commit();

            $_SESSION['checkin_message'] = "Check-in successful! Booking ID " . htmlspecialchars($booking_id_to_confirm) . " converted to Renting ID " . htmlspecialchars($renting_id) . ". Payment recorded: $" . number_format($payment_amount, 2);
            $_SESSION['checkin_message_type'] = 'success';
            header("Location: check_in.php"); // Redirect back to clean check-in page
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Check-in processing error: " . $e->getMessage());
            // Store error in session for display after redirect
            $_SESSION['checkin_message'] = "Error processing check-in: " . $e->getMessage();
            $_SESSION['checkin_message_type'] = 'danger';
            header("Location: check_in.php?search_term=".urlencode($search_term)); // Redirect back to search results if possible
            exit;
        }
    } else {
        // Store error in session for display after redirect
        $_SESSION['checkin_message'] = "Invalid Booking ID or Payment Amount for check-in.";
        $_SESSION['checkin_message_type'] = 'danger';
        header("Location: check_in.php?search_term=".urlencode($search_term)); // Redirect back
        exit;
    }
}

?>

<div class="container my-5">
    <h1 class="mb-4">Process Check-in</h1>
    <p>Search for an existing booking by Booking ID, Customer Name, Customer Email, or Customer SSN to convert it into a renting record. Only bookings starting today or earlier are shown.</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Form (Using GET now) -->
    <form method="GET" action="check_in.php" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Enter Booking ID, Customer Name, Email, or SSN..." 
                   name="search_term" value="<?= htmlspecialchars($search_term) ?>" required>
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-search me-1"></i> Search Bookings
            </button>
             <?php if (!empty($search_term)): ?>
                 <a href="check_in.php" class="btn btn-outline-secondary">Clear Search</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Search Results -->
    <?php if (!empty($search_term) && empty($search_results) && $messageType !== 'danger'): ?>
         <!-- Message is shown above if no results -->
    <?php elseif (!empty($search_results)): ?>
        <h2 class="h4 mt-4 mb-3">Select Booking to Check-in</h2>
        <div class="list-group">
            <?php foreach ($search_results as $booking): 
                 // Calculate nights/price for display
                $booking_total_price = 0;
                $nights = 0;
                try {
                    $start = new DateTime($booking['Start_Date']);
                    $end = new DateTime($booking['End_Date']);
                    $interval = $end->diff($start);
                    $nights = $interval->days > 0 ? $interval->days : 1; // Ensure at least 1 night
                    $booking_total_price = $booking['Price_Per_Night'] * $nights;
                } catch (Exception $e) { /* Handle error if needed */ }
            ?>
                <div class="list-group-item list-group-item-action">
                     <form method="POST" action="check_in.php?search_term=<?= urlencode($search_term) ?>">
                        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['Booking_ID']) ?>">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h5 class="mb-1">Booking ID: <?= htmlspecialchars($booking['Booking_ID']) ?></h5>
                                <p class="mb-1">
                                    <strong>Customer:</strong> <?= htmlspecialchars($booking['Customer_Name']) ?> (<?= htmlspecialchars($booking['Customer_ID']) ?>)<br>
                                    <strong>Hotel:</strong> <?= htmlspecialchars($booking['Chain_Name']) ?> - <?= htmlspecialchars($booking['Hotel_Address']) ?><br>
                                    <strong>Room:</strong> #<?= htmlspecialchars($booking['Room_Num']) ?><br>
                                    <strong>Dates:</strong> <?= date("M j, Y", strtotime($booking['Start_Date'])) ?> to <?= date("M j, Y", strtotime($booking['End_Date'])) ?> (<?= $nights ?> nights)<br>
                                    <strong>Est. Price:</strong> $<?= number_format($booking_total_price, 2) ?> ($<?= number_format($booking['Price_Per_Night'], 2) ?>/night)
                                </p>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <div class="mb-2">
                                     <label for="payment_amount_<?= $booking['Booking_ID'] ?>" class="form-label">Payment Amount Collected</label>
                                     <div class="input-group input-group-sm">
                                         <span class="input-group-text">$</span>
                                         <input type="number" step="0.01" min="0" class="form-control" 
                                                id="payment_amount_<?= $booking['Booking_ID'] ?>" name="payment_amount" 
                                                value="<?= number_format($booking_total_price, 2) ?>" required>
                                     </div>
                                </div>
                                <!-- Can add payment method selection here if needed -->
                                <!-- <select name="payment_method" class="form-select form-select-sm mb-2"><option>Cash</option><option>Card</option></select> -->
                                <button type="submit" name="confirm_check_in" class="btn btn-success w-100">
                                    <i class="fas fa-calendar-check me-1"></i> Confirm Check-in & Create Renting
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php
require_once '../includes/footer.php'; 
?> 