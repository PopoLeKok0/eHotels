<?php
/**
 * e-Hotels Employee Direct Rental Page
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
$message = $_SESSION['rental_message'] ?? null;
$messageType = $_SESSION['rental_message_type'] ?? '';

// Clear session messages after reading
if (isset($_SESSION['rental_message'])) {
    unset($_SESSION['rental_message']);
    unset($_SESSION['rental_message_type']);
}

// Search parameters (Using GET for persistence across steps)
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d', strtotime('+1 day'));
$capacity = $_GET['capacity'] ?? 0;
$area = $_GET['area'] ?? '';
$chain = $_GET['chain'] ?? ''; // Added chain filter
$min_price = $_GET['min_price'] ?? null;
$max_price = $_GET['max_price'] ?? null;
$action = $_GET['action'] ?? 'search_rooms'; // Control flow: search_rooms, search_customer, confirm

$available_rooms = [];
$selected_room = null; // Will hold details of room chosen for rental
$customer_to_rent = null; // Will hold details of customer chosen/entered

// Customer search/details from later steps
$customer_search_term = $_GET['customer_search_term'] ?? '';

$dbInstance = getDatabase();
$db = $dbInstance->getConnection();

// Fetch filter options
$areas = [];
$chains_list = [];
try {
    $areas = $db->query("SELECT DISTINCT Area FROM Hotel ORDER BY Area")->fetchAll(PDO::FETCH_COLUMN);
    $chains_list = $db->query("SELECT DISTINCT Chain_Name FROM Hotel_Chain ORDER BY Chain_Name")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $message = "Error loading filter options: " . $e->getMessage();
    $messageType = 'danger';
}

// --- State 1: Search for Available Rooms ---
if ($action === 'search_rooms' || isset($_GET['search_rooms_submit'])) {
    try {
        // Build the query dynamically based on filters
        $sql = "
            SELECT 
                h.Hotel_Address, h.Chain_Name, h.Area,
                r.Room_Num, r.Capacity, r.Price, r.View_Type, r.Amenities
            FROM Room r
            JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
            WHERE r.Availability = TRUE
        ";
        $params = [];

        if ($capacity > 0) { $sql .= " AND r.Capacity >= ?"; $params[] = $capacity; }
        if (!empty($area)) { $sql .= " AND h.Area = ?"; $params[] = $area; }
        if (!empty($chain)) { $sql .= " AND h.Chain_Name = ?"; $params[] = $chain; }
        if (is_numeric($min_price)) { $sql .= " AND r.Price >= ?"; $params[] = (float)$min_price; }
        if (is_numeric($max_price)) { $sql .= " AND r.Price <= ?"; $params[] = (float)$max_price; }

        // Date availability check using NOT EXISTS
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM Booking b JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
            WHERE rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
              AND (b.Start_Date < ? AND b.End_Date > ?)
        )";
        $params[] = $end_date;
        $params[] = $start_date;
        
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM Renting rent JOIN Rented_By rntb ON rent.Renting_ID = rntb.Renting_ID
            WHERE rntb.Hotel_Address = r.Hotel_Address AND rntb.Room_Num = r.Room_Num
              AND (rent.Start_Date < ? AND rent.End_Date > ?)
        )";
        $params[] = $end_date;
        $params[] = $start_date;

        $sql .= " ORDER BY h.Area, h.Hotel_Address, r.Room_Num LIMIT 100";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($available_rooms) && isset($_GET['search_rooms_submit'])) {
            $message = "No available rooms found matching the criteria for the selected dates.";
            $messageType = 'warning';
        }

    } catch (Exception $e) {
        error_log("Direct rental room search error: " . $e->getMessage());
        $message = "Error searching for available rooms: " . $e->getMessage();
        $messageType = 'danger';
    }
}

// --- State 2: Customer Search/Entry (After a room is selected) ---
if ($action === 'search_customer' && isset($_GET['hotel_addr']) && isset($_GET['room_num'])) {
    // Fetch selected room details to pass along
    try {
        $stmt = $db->prepare("SELECT r.*, h.Chain_Name, h.Area FROM Room r JOIN Hotel h ON r.Hotel_Address=h.Hotel_Address WHERE r.Hotel_Address = ? AND r.Room_Num = ?");
        $stmt->execute([$_GET['hotel_addr'], $_GET['room_num']]);
        $selected_room = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$selected_room) { 
             throw new Exception("Selected room details could not be found.");
        }
        // Keep dates associated with the selection
        $selected_room['start_date'] = $start_date;
        $selected_room['end_date'] = $end_date;
    } catch (Exception $e) {
        error_log("Direct rental room selection error: " . $e->getMessage());
        $message = "Error fetching selected room details: " . $e->getMessage();
        $messageType = 'danger';
        $action = 'search_rooms'; // Go back to room search
    }
    
    // If customer search term submitted, find customer
    if (!empty($customer_search_term)) {
        try {
            $stmt_cust = $db->prepare("SELECT Customer_ID, Full_Name, Email_Address, Address FROM Customer WHERE Full_Name LIKE ? OR Email_Address LIKE ? OR Customer_ID = ? LIMIT 1");
            $like_term = '%' . $customer_search_term . '%';
            $stmt_cust->execute([$like_term, $like_term, $customer_search_term]);
            $customer_to_rent = $stmt_cust->fetch(PDO::FETCH_ASSOC);
            if (!$customer_to_rent) {
                 $message = "No existing customer found matching '" . htmlspecialchars($customer_search_term) . "'. Please enter details manually below.";
                 $messageType = 'info';
            } else {
                 $message = "Found existing customer: " . htmlspecialchars($customer_to_rent['Full_Name']) . ". Verify details.";
                 $messageType = 'success';
            }
         } catch (Exception $e) {
             error_log("Direct rental customer search error: " . $e->getMessage());
             $message = "Error searching for customer: " . $e->getMessage();
             $messageType = 'danger';
         }
    }
}

// --- State 3: Handle FINAL Direct Rental Confirmation (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_direct_rental'])) {
    // Get submitted data
    $room_data = json_decode($_POST['selected_room_details'] ?? '[]', true);
    $customer_id = $_POST['customer_id'] ?? null; // Existing or newly generated
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    // We need a unique identifier for new customers - SSN/SIN/ID from registration is best
    // For direct rental, let's assume Customer_ID is the primary key (SSN/SIN)
    $customer_new_id = trim($_POST['customer_new_id'] ?? ''); // Get the ID field
    $payment_amount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT);

    // Basic validation
    if (empty($room_data)) {
        $message = "Room details missing. Please start again."; $messageType = 'danger'; $action = 'search_rooms';
    } elseif (empty($customer_id) && (empty($customer_new_id) || empty($customer_name) || empty($customer_address))) {
        $message = "Customer ID (SSN/SIN) and Name/Address required for new customer."; $messageType = 'danger'; $action = 'search_customer';
    } elseif ($payment_amount === false || $payment_amount < 0) {
         $message = "Invalid payment amount entered."; $messageType = 'danger'; $action = 'search_customer';
    } else {
        $db->beginTransaction();
        try {
            // A. Get/Create Customer
            $final_customer_id = $customer_id; // Use existing ID if provided
            
            if (empty($final_customer_id)) { // Create new customer if ID was empty
                 $final_customer_id = $customer_new_id; // Use the provided SSN/SIN as the ID
                 
                 // Check if ID already exists
                 $stmt_check = $db->prepare("SELECT COUNT(*) FROM Customer WHERE Customer_ID = ?");
                 $stmt_check->execute([$final_customer_id]);
                 if ($stmt_check->fetchColumn() > 0) {
                      throw new Exception("A customer with this ID (SSN/SIN) already exists. Please search for them instead.");
                 }
                 
                 // Insert new customer (NO password set here - they need to register properly for login)
                 $stmt_new_cust = $db->prepare("INSERT INTO Customer (Customer_ID, Full_Name, Address, Email_Address, Date_of_Registration) VALUES (?, ?, ?, ?, CURDATE())");
                 $stmt_new_cust->execute([
                     $final_customer_id,
                     $customer_name,
                     $customer_address,
                     $customer_email // Can be null if not provided
                 ]);
                 $message = "New customer created. "; // Append to final success message
                 $messageType = 'info';
            }

            // B. Re-verify room availability for the dates (using details from $room_data)
            $stmt_avail = $db->prepare("SELECT COUNT(*) FROM (
                 SELECT 1 FROM Booking b JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
                 WHERE rb.Hotel_Address = ? AND rb.Room_Num = ?
                   AND (b.Start_Date < ? AND b.End_Date > ?)
                 UNION ALL
                 SELECT 1 FROM Renting rent JOIN Rented_By rntb ON rent.Renting_ID = rntb.Renting_ID
                 WHERE rntb.Hotel_Address = ? AND rntb.Room_Num = ?
                   AND (rent.Start_Date < ? AND rent.End_Date > ?)
             ) AS Conflicts");
            $stmt_avail->execute([
                $room_data['Hotel_Address'], $room_data['Room_Num'], $room_data['end_date'], $room_data['start_date'],
                $room_data['Hotel_Address'], $room_data['Room_Num'], $room_data['end_date'], $room_data['start_date']
            ]); 
            if ($stmt_avail->fetchColumn() > 0) {
                 throw new Exception("Room " . htmlspecialchars($room_data['Room_Num']) . " at " . htmlspecialchars($room_data['Hotel_Address']) . " became unavailable. Please search again.");
            }

            // C. Create Renting record
            $renting_id = uniqid('rent_', true);
            $stmt_rent = $db->prepare("INSERT INTO Renting (Renting_ID, Customer_ID, Start_Date, End_Date, Payment_Amount) VALUES (?, ?, ?, ?, ?)");
            $stmt_rent->execute([
                $renting_id, 
                $final_customer_id, 
                $room_data['start_date'], 
                $room_data['end_date'], 
                $payment_amount
            ]);

            // D. Link Renting to Room
            $stmt_rented = $db->prepare("INSERT INTO Rented_By (Renting_ID, Hotel_Address, Room_Num) VALUES (?, ?, ?)");
            $stmt_rented->execute([$renting_id, $room_data['Hotel_Address'], $room_data['Room_Num']]);

            // E. Link Employee to Renting
            $stmt_proc = $db->prepare("INSERT INTO Processes (SSN, Renting_ID) VALUES (?, ?)");
            $stmt_proc->execute([$employee_id, $renting_id]);

            $db->commit();
            $_SESSION['rental_message'] = $message . "Direct rental successful! Renting ID: " . htmlspecialchars($renting_id) . ". Payment: $" . number_format($payment_amount, 2);
            $_SESSION['rental_message_type'] = 'success';
            header("Location: direct_rental.php"); // Redirect back to clean rental page
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Direct rental confirmation error: " . $e->getMessage());
            $_SESSION['rental_message'] = "Error processing direct rental: " . $e->getMessage();
            $_SESSION['rental_message_type'] = 'danger';
            // Redirect back to customer step with error
            header("Location: direct_rental.php?action=search_customer&hotel_addr=".urlencode($room_data['Hotel_Address'] ?? '')."&room_num=".urlencode($room_data['Room_Num'] ?? '')."&start_date=".urlencode($room_data['start_date'] ?? '')."&end_date=".urlencode($room_data['end_date'] ?? '')."&customer_search_term=".urlencode($customer_search_term ?? ''));
            exit;
        }
    }
}

?>

<div class="container my-5">
    <h1 class="mb-4">Direct Room Rental</h1>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php // --- STEP 1: Search Rooms --- ?>
    <?php if ($action === 'search_rooms'): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Step 1: Find Available Rooms
        </div>
        <div class="card-body">
            <form method="GET" action="direct_rental.php">
                <input type="hidden" name="action" value="search_rooms">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Check-in Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Check-out Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label for="capacity" class="form-label">Min Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($capacity) ?>" min="0">
                    </div>
                     <div class="col-md-4">
                        <label for="area" class="form-label">Area</label>
                        <select class="form-select" id="area" name="area">
                            <option value="">Any Area</option>
                            <?php foreach ($areas as $area_opt): ?>
                            <option value="<?= htmlspecialchars($area_opt) ?>" <?= ($area == $area_opt) ? 'selected' : '' ?>><?= htmlspecialchars($area_opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                 <div class="row g-3 mb-3">
                     <div class="col-md-4">
                        <label for="chain" class="form-label">Hotel Chain</label>
                        <select class="form-select" id="chain" name="chain">
                            <option value="">Any Chain</option>
                             <?php foreach ($chains_list as $chain_opt): ?>
                            <option value="<?= htmlspecialchars($chain_opt) ?>" <?= ($chain == $chain_opt) ? 'selected' : '' ?>><?= htmlspecialchars($chain_opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-4">
                         <label for="min_price" class="form-label">Min Price ($)</label>
                         <input type="number" step="0.01" class="form-control" id="min_price" name="min_price" value="<?= htmlspecialchars($min_price ?? '') ?>" placeholder="e.g., 50">
                     </div>
                     <div class="col-md-4">
                         <label for="max_price" class="form-label">Max Price ($)</label>
                         <input type="number" step="0.01" class="form-control" id="max_price" name="max_price" value="<?= htmlspecialchars($max_price ?? '') ?>" placeholder="e.g., 200">
                     </div>
                 </div>
                <button type="submit" name="search_rooms_submit" class="btn btn-primary">Search Available Rooms</button>
            </form>
        </div>
    </div>

    <?php if (!empty($available_rooms)): ?>
        <h3 class="h5 mt-4 mb-3">Available Rooms Found</h3>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover">
                <thead>
                    <tr><th>Hotel</th><th>Area</th><th>Room #</th><th>Capacity</th><th>Price/Night</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($available_rooms as $room): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['Chain_Name']) ?><br><small><?= htmlspecialchars($room['Hotel_Address']) ?></small></td>
                        <td><?= htmlspecialchars($room['Area']) ?></td>
                        <td><?= htmlspecialchars($room['Room_Num']) ?></td>
                        <td><?= htmlspecialchars($room['Capacity']) ?></td>
                        <td>$<?= number_format($room['Price'], 2) ?></td>
                        <td>
                            <a href="direct_rental.php?action=search_customer&hotel_addr=<?= urlencode($room['Hotel_Address']) ?>&room_num=<?= $room['Room_Num'] ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-sm btn-success">Select Room</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php endif; // End Step 1 ?>


    <?php // --- STEP 2: Select/Enter Customer --- ?>
    <?php if ($action === 'search_customer' && $selected_room): 
        // Calculate nights/price for display
        $nights = 0; $total_price = 0;
        try {
            $start = new DateTime($selected_room['start_date']);
            $end = new DateTime($selected_room['end_date']);
            $interval = $end->diff($start);
            $nights = $interval->days > 0 ? $interval->days : 1;
            $total_price = $selected_room['Price'] * $nights;
        } catch (Exception $e) { /* Ignore */ }
    ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            Step 2: Assign Customer & Payment
        </div>
        <div class="card-body">
            <h4 class="h6">Selected Room:</h4>
            <p>
                <strong>Hotel:</strong> <?= htmlspecialchars($selected_room['Chain_Name']) ?> - <?= htmlspecialchars($selected_room['Hotel_Address']) ?><br>
                <strong>Room:</strong> #<?= htmlspecialchars($selected_room['Room_Num']) ?> | <strong>Capacity:</strong> <?= htmlspecialchars($selected_room['Capacity']) ?> | <strong>Price:</strong> $<?= number_format($selected_room['Price'], 2) ?>/night<br>
                <strong>Dates:</strong> <?= date("M j, Y", strtotime($selected_room['start_date'])) ?> to <?= date("M j, Y", strtotime($selected_room['end_date'])) ?> (<?= $nights ?> nights)<br>
                <strong>Estimated Total:</strong> $<?= number_format($total_price, 2) ?>
            </p>
            <hr>

            <h4 class="h6">Find Existing Customer (Optional):</h4>
             <form method="GET" action="direct_rental.php" class="mb-3">
                 <input type="hidden" name="action" value="search_customer">
                 <input type="hidden" name="hotel_addr" value="<?= htmlspecialchars($selected_room['Hotel_Address']) ?>">
                 <input type="hidden" name="room_num" value="<?= htmlspecialchars($selected_room['Room_Num']) ?>">
                 <input type="hidden" name="start_date" value="<?= htmlspecialchars($selected_room['start_date']) ?>">
                 <input type="hidden" name="end_date" value="<?= htmlspecialchars($selected_room['end_date']) ?>">
                 <div class="input-group">
                     <input type="text" class="form-control" placeholder="Search by Name, Email, or Customer ID (SSN/SIN)..." name="customer_search_term" value="<?= htmlspecialchars($customer_search_term) ?>">
                     <button class="btn btn-secondary" type="submit"><i class="fas fa-search"></i> Find Customer</button>
                 </div>
             </form>

            <hr>
            <h4 class="h6">Customer Details:</h4>
            <form method="POST" action="direct_rental.php">
                 <input type="hidden" name="action" value="confirm_direct_rental">
                 <input type="hidden" name="selected_room_details" value='<?= htmlspecialchars(json_encode($selected_room)) ?>'>
                 <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_to_rent['Customer_ID'] ?? '') ?>"> 

                 <div class="row g-3 mb-3">
                     <div class="col-md-6">
                         <label for="customer_new_id" class="form-label">Customer ID (SSN/SIN) <span class="text-danger">*</span></label>
                         <input type="text" class="form-control" id="customer_new_id" name="customer_new_id" value="<?= htmlspecialchars($customer_to_rent['Customer_ID'] ?? '') ?>" <?= !empty($customer_to_rent) ? 'readonly' : 'required' ?>>
                         <div class="form-text">Required for new customer. Cannot be changed for existing customer.</div>
                     </div>
                      <div class="col-md-6">
                         <label for="customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                         <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?= htmlspecialchars($customer_to_rent['Full_Name'] ?? '') ?>" required>
                     </div>
                </div>
                <div class="row g-3 mb-3">
                     <div class="col-md-6">
                         <label for="customer_email" class="form-label">Email Address</label>
                         <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?= htmlspecialchars($customer_to_rent['Email_Address'] ?? '') ?>">
                     </div>
                     <div class="col-md-6">
                         <label for="customer_address" class="form-label">Address <span class="text-danger">*</span></label>
                         <input type="text" class="form-control" id="customer_address" name="customer_address" value="<?= htmlspecialchars($customer_to_rent['Address'] ?? '') ?>" required>
                     </div>
                </div>
                 <div class="row g-3 mb-3">
                      <div class="col-md-6">
                         <label for="payment_amount" class="form-label">Payment Amount Collected <span class="text-danger">*</span></label>
                         <div class="input-group">
                             <span class="input-group-text">$</span>
                             <input type="number" step="0.01" min="0" class="form-control" id="payment_amount" name="payment_amount" value="<?= number_format($total_price, 2) ?>" required>
                         </div>
                     </div>
                     <!-- Add Payment Method if needed -->
                 </div>

                 <div class="mt-3 d-flex justify-content-between">
                      <a href="direct_rental.php" class="btn btn-secondary">Cancel / Start Over</a>
                     <button type="submit" name="confirm_direct_rental" class="btn btn-success btn-lg">Confirm Rental & Payment</button>
                 </div>
            </form>
        </div>
    </div>
    <?php endif; // End Step 2 ?>

</div>

<?php
require_once '../includes/footer.php'; 
?> 