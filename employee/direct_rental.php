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
$message = '';
$messageType = '';

// Search parameters
$start_date = $_POST['start_date'] ?? date('Y-m-d'); // Default today
$end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 day')); // Default tomorrow
$capacity = $_POST['capacity'] ?? 0;
$area = $_POST['area'] ?? '';
$hotel_address = $_POST['hotel_address'] ?? ''; // Specific hotel if known

$available_rooms = [];
$selected_room = null;
$customer_details = null; // To hold details if existing customer found

$dbInstance = getDatabase();
$db = $dbInstance->getConnection();

// --- Handle Room Search ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_rooms'])) {
    // Basic availability query (similar to main search but simpler for employee)
    try {
        $query = "
            SELECT 
                h.Hotel_Address, h.Chain_Name, h.Area,
                r.Room_Num, r.Capacity, r.Price, r.View_Type, r.Amenities
            FROM Room r
            JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address
            WHERE r.Availability = TRUE
        ";
        $params = [];
        
        // Add filters
        if ($capacity > 0) {
            $query .= " AND r.Capacity >= :capacity";
            $params[':capacity'] = $capacity;
        }
        if (!empty($area)) {
            $query .= " AND h.Area = :area";
            $params[':area'] = $area;
        }
         if (!empty($hotel_address)) {
            $query .= " AND h.Hotel_Address = :hotel_address";
            $params[':hotel_address'] = $hotel_address;
        }
        
        // Date availability check
        $query .= " AND NOT EXISTS (
            SELECT 1 FROM Booking b JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
            WHERE rb.Hotel_Address = r.Hotel_Address AND rb.Room_Num = r.Room_Num
              AND (b.Start_Date < :end_date1 AND b.End_Date > :start_date1)
        )";
        $query .= " AND NOT EXISTS (
            SELECT 1 FROM Renting rnt JOIN Rented_By rntb ON rnt.Renting_ID = rntb.Renting_ID
            WHERE rntb.Hotel_Address = r.Hotel_Address AND rntb.Room_Num = r.Room_Num
              AND (rnt.Start_Date < :end_date2 AND rnt.End_Date > :start_date2)
        )";
        $params[':start_date1'] = $start_date;
        $params[':end_date1'] = $end_date;
        $params[':start_date2'] = $start_date;
        $params[':end_date2'] = $end_date;

        $query .= " ORDER BY h.Area, h.Hotel_Address, r.Room_Num LIMIT 50"; // Limit results
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($available_rooms)) {
            $message = "No available rooms found matching the criteria.";
            $messageType = 'warning';
        }

    } catch (Exception $e) {
        error_log("Direct rental room search error: " . $e->getMessage());
        $message = "Error searching for available rooms.";
        $messageType = 'danger';
    }
}

// --- Handle Room Selection ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_room'])) {
    $selected_hotel_address = $_POST['selected_hotel_address'] ?? null;
    $selected_room_num = $_POST['selected_room_num'] ?? null;
    // Keep dates from the form
    $start_date = $_POST['start_date'] ?? date('Y-m-d'); 
    $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 day')); 

    if ($selected_hotel_address && $selected_room_num) {
        try {
            $stmt = $db->prepare("SELECT r.*, h.Chain_Name, h.Area FROM Room r JOIN Hotel h ON r.Hotel_Address=h.Hotel_Address WHERE r.Hotel_Address = :addr AND r.Room_Num = :num");
            $stmt->bindParam(':addr', $selected_hotel_address);
            $stmt->bindParam(':num', $selected_room_num, PDO::PARAM_INT);
            $stmt->execute();
            $selected_room = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$selected_room) { 
                 $message = "Selected room details could not be found."; 
                 $messageType='danger'; 
            } else {
                 // Keep dates associated with the selection
                 $selected_room['start_date'] = $start_date;
                 $selected_room['end_date'] = $end_date;
            }
        } catch (Exception $e) {
            error_log("Direct rental room selection error: " . $e->getMessage());
            $message = "Error fetching selected room details.";
            $messageType = 'danger';
        }
    }
}

// --- Handle Customer Search/Selection ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_customer'])) {
    $customer_search_term = $_POST['customer_search_term'] ?? '';
    $selected_room = json_decode($_POST['selected_room_details'] ?? '[]', true); // Get room details back
    
    if (!empty($customer_search_term) && !empty($selected_room)) {
         try {
            $stmt = $db->prepare("SELECT Customer_ID, Full_Name, Email_Address, Address FROM Customer WHERE Full_Name LIKE :term OR Email_Address LIKE :term LIMIT 1");
            $like_term = '%' . $customer_search_term . '%';
            $stmt->bindParam(':term', $like_term);
            $stmt->execute();
            $customer_details = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$customer_details) {
                 $message = "No existing customer found matching '" . htmlspecialchars($customer_search_term) . "'. Please enter details manually.";
                 $messageType = 'info';
            }
         } catch (Exception $e) {
             error_log("Direct rental customer search error: " . $e->getMessage());
             $message = "Error searching for customer.";
             $messageType = 'danger';
         }
    } elseif (empty($selected_room)) {
        $message = "Room selection lost. Please search for a room again.";
        $messageType = 'danger';
        $selected_room = null; // Reset room selection
    }
}

// --- Handle FINAL Direct Rental Confirmation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_direct_rental'])) {
    $selected_room = json_decode($_POST['selected_room_details'] ?? '[]', true);
    $customer_id = $_POST['customer_id'] ?? null; // Existing customer ID
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_email = $_POST['customer_email'] ?? '';
    $customer_address = $_POST['customer_address'] ?? '';
    $customer_id_type = $_POST['customer_id_type'] ?? 'Passport'; // Default ID type

    if (!empty($selected_room) && (!empty($customer_id) || (!empty($customer_name) && !empty($customer_email) && !empty($customer_address)))){
        $db->beginTransaction();
        try {
            // A. Get/Create Customer ID
            if (empty($customer_id)) { // Create new customer
                $customer_id = uniqid('cust-', true);
                $stmt_new_cust = $db->prepare("INSERT INTO Customer (Customer_ID, Full_Name, Address, Email_Address, ID_Type, Date_of_Registration) VALUES (:id, :name, :addr, :email, :id_type, CURDATE())");
                // NOTE: Needs Password_Hash if registering properly
                $stmt_new_cust->bindParam(':id', $customer_id);
                $stmt_new_cust->bindParam(':name', $customer_name);
                $stmt_new_cust->bindParam(':addr', $customer_address);
                $stmt_new_cust->bindParam(':email', $customer_email);
                $stmt_new_cust->bindParam(':id_type', $customer_id_type);
                $stmt_new_cust->execute();
                // TODO: Add email to Customer_Email table if needed by schema design
            }
            
             // B. Re-verify room availability for the dates
            $stmt_avail = $db->prepare("SELECT COUNT(*) FROM (
                 SELECT 1 FROM Booking b JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
                 WHERE rb.Hotel_Address = :haddr1 AND rb.Room_Num = :rnum1
                   AND (b.Start_Date < :end_date1 AND b.End_Date > :start_date1)
                 UNION ALL
                 SELECT 1 FROM Renting rnt JOIN Rented_By rntb ON rnt.Renting_ID = rntb.Renting_ID
                 WHERE rntb.Hotel_Address = :haddr2 AND rntb.Room_Num = :rnum2
                   AND (rnt.Start_Date < :end_date2 AND rnt.End_Date > :start_date2)
             ) AS Conflicts");
            $stmt_avail->bindParam(':haddr1', $selected_room['Hotel_Address']);
            $stmt_avail->bindParam(':rnum1', $selected_room['Room_Num'], PDO::PARAM_INT);
            $stmt_avail->bindParam(':start_date1', $selected_room['start_date']);
            $stmt_avail->bindParam(':end_date1', $selected_room['end_date']);
            $stmt_avail->bindParam(':haddr2', $selected_room['Hotel_Address']);
            $stmt_avail->bindParam(':rnum2', $selected_room['Room_Num'], PDO::PARAM_INT);
            $stmt_avail->bindParam(':start_date2', $selected_room['start_date']);
            $stmt_avail->bindParam(':end_date2', $selected_room['end_date']);
            $stmt_avail->execute(); 
            $conflictCount = $stmt_avail->fetchColumn(); 
            if ($conflictCount > 0) {
                 throw new Exception("Room became unavailable during processing. Please search again.");
            }

            // C. Create Renting
            $renting_id = uniqid('rent-', true);
            $check_in_date = date('Y-m-d'); // Use current date for check-in
            $direct_renting = true; 
            $stmt_rent = $db->prepare("INSERT INTO Renting (Renting_ID, Start_Date, End_Date, Check_in_Date, Direct_Renting, Customer_ID) VALUES (:rid, :start, :end, :checkin, :direct, :cid)");
            $stmt_rent->bindParam(':rid', $renting_id);
            $stmt_rent->bindParam(':start', $selected_room['start_date']);
            $stmt_rent->bindParam(':end', $selected_room['end_date']);
            $stmt_rent->bindParam(':checkin', $check_in_date);
            $stmt_rent->bindParam(':direct', $direct_renting, PDO::PARAM_BOOL);
            $stmt_rent->bindParam(':cid', $customer_id);
            $stmt_rent->execute();

            // D. Create Rented_By
            $stmt_rented = $db->prepare("INSERT INTO Rented_By (Renting_ID, Hotel_Address, Room_Num) VALUES (:rid, :addr, :num)");
            $stmt_rented->bindParam(':rid', $renting_id);
            $stmt_rented->bindParam(':addr', $selected_room['Hotel_Address']);
            $stmt_rented->bindParam(':num', $selected_room['Room_Num'], PDO::PARAM_INT);
            $stmt_rented->execute();

            // E. Create Processes
            $stmt_proc = $db->prepare("INSERT INTO Processes (SSN, Renting_ID) VALUES (:ssn, :rid)");
            $stmt_proc->bindParam(':ssn', $employee_id);
            $stmt_proc->bindParam(':rid', $renting_id);
            $stmt_proc->execute();

            $db->commit();
            $_SESSION['success_message'] = "Direct rental successful! Renting ID: " . htmlspecialchars($renting_id);
            header("Location: index.php");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Direct rental confirmation error: " . $e->getMessage());
            // Add specific check for duplicate customer email if needed
            $message = "Error processing direct rental: " . $e->getMessage();
            $messageType = 'danger';
            // Keep $selected_room and $customer_details to refill form
            $customer_details = [ // Refill manual entry fields if needed
                 'Customer_ID' => $customer_id, 'Full_Name' => $customer_name, 'Email_Address' => $customer_email, 'Address' => $customer_address
            ];
        }
    } else {
         $message = "Missing room or customer details for rental confirmation.";
         $messageType = 'danger';
         // Attempt to keep state if possible
         if(isset($_POST['selected_room_details'])) $selected_room = json_decode($_POST['selected_room_details'] ?? '[]', true); 
         $customer_details = [ 'Customer_ID' => $customer_id, 'Full_Name' => $customer_name, 'Email_Address' => $customer_email, 'Address' => $customer_address];
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

    <?php if (!$selected_room): ?>
        <!-- Step 1: Search for Available Rooms -->
        <h2 class="h4 mb-3">Step 1: Find Available Room</h2>
        <form method="POST" action="direct_rental.php" class="border p-3 rounded bg-light mb-5">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($capacity) ?>" min="1">
                </div>
                <div class="col-md-6">
                    <label for="area" class="form-label">Area (Optional)</label>
                    <input type="text" class="form-control" id="area" name="area" value="<?= htmlspecialchars($area) ?>">
                </div>
                <div class="col-md-6">
                    <label for="hotel_address" class="form-label">Hotel Address (Optional)</label>
                    <input type="text" class="form-control" id="hotel_address" name="hotel_address" value="<?= htmlspecialchars($hotel_address) ?>">
                </div>
            </div>
            <button type="submit" name="search_rooms" class="btn btn-primary mt-3"><i class="fas fa-search me-1"></i> Search Rooms</button>
        </form>

        <?php if (!empty($available_rooms)): ?>
            <h3 class="h5 mt-4 mb-3">Available Rooms Found</h3>
            <div class="list-group">
                 <?php foreach ($available_rooms as $room): ?>
                    <div class="list-group-item">
                         <div class="row align-items-center">
                            <div class="col-md-8">
                                 <h5 class="mb-1"><?= htmlspecialchars($room['Chain_Name']) ?> - <?= htmlspecialchars($room['Hotel_Address']) ?> (Room #<?= htmlspecialchars($room['Room_Num']) ?>)</h5>
                                 <p class="mb-1 small">Area: <?= htmlspecialchars($room['Area']) ?>, Capacity: <?= htmlspecialchars($room['Capacity']) ?>, View: <?= htmlspecialchars($room['View_Type']) ?></p>
                                 <p class="mb-0 fw-bold">Price: $<?= number_format($room['Price'], 2) ?> / night</p>
                            </div>
                             <div class="col-md-4 text-md-end">
                                 <form method="POST" action="direct_rental.php">
                                    <input type="hidden" name="selected_hotel_address" value="<?= htmlspecialchars($room['Hotel_Address']) ?>">
                                    <input type="hidden" name="selected_room_num" value="<?= htmlspecialchars($room['Room_Num']) ?>">
                                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                    <button type="submit" name="select_room" class="btn btn-success">
                                        <i class="fas fa-check me-1"></i> Select Room
                                    </button>
                                </form>
                             </div>
                         </div>
                    </div>
                 <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Step 2 & 3: Enter Customer Details & Confirm -->
        <div class="card shadow mb-4">
             <div class="card-header bg-success text-white">
                 <h2 class="h4 mb-0">Step 2: Enter Customer Details</h2>
             </div>
             <div class="card-body">
                 <p><strong>Selected Room:</strong> <?= htmlspecialchars($selected_room['Chain_Name']) ?> - <?= htmlspecialchars($selected_room['Hotel_Address']) ?> (Room #<?= htmlspecialchars($selected_room['Room_Num']) ?>)</p>
                 <p><strong>Dates:</strong> <?= date("M j, Y", strtotime($selected_room['start_date'])) ?> to <?= date("M j, Y", strtotime($selected_room['end_date'])) ?></p>
                 <hr>
                 
                 <!-- Customer Search -->
                 <form method="POST" action="direct_rental.php" class="mb-4">
                     <label for="customer_search_term" class="form-label">Search Existing Customer (Name or Email)</label>
                     <div class="input-group">
                         <input type="text" class="form-control" id="customer_search_term" name="customer_search_term">
                         <input type="hidden" name="selected_room_details" value='<?= htmlspecialchars(json_encode($selected_room)) ?>'>
                         <button class="btn btn-secondary" type="submit" name="search_customer"><i class="fas fa-search me-1"></i> Find Customer</button>
                     </div>
                 </form>
                 
                 <!-- Customer Details Form -->
                 <form method="POST" action="direct_rental.php" onsubmit="return confirm('Confirm direct rental for this room and customer?')">
                     <input type="hidden" name="selected_room_details" value='<?= htmlspecialchars(json_encode($selected_room)) ?>'>
                     
                     <?php if ($customer_details): // If existing customer found ?>
                         <h3 class="h5">Selected Customer:</h3>
                         <p><strong>Name:</strong> <?= htmlspecialchars($customer_details['Full_Name']) ?></p>
                         <p><strong>Email:</strong> <?= htmlspecialchars($customer_details['Email_Address']) ?></p>
                         <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_details['Customer_ID']) ?>">
                         <hr>
                     <?php else: // Manual entry for new or not-found customer ?>
                        <h3 class="h5">New Customer Details:</h3>
                         <input type="hidden" name="customer_id" value=""> <!-- No existing ID -->
                         <div class="mb-3">
                             <label for="customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                             <input type="text" class="form-control" id="customer_name" name="customer_name" value="<?= htmlspecialchars($customer_details['Full_Name'] ?? '') ?>" required>
                         </div>
                         <div class="mb-3">
                             <label for="customer_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                             <input type="email" class="form-control" id="customer_email" name="customer_email" value="<?= htmlspecialchars($customer_details['Email_Address'] ?? '') ?>" required>
                         </div>
                         <div class="mb-3">
                             <label for="customer_address" class="form-label">Address <span class="text-danger">*</span></label>
                             <input type="text" class="form-control" id="customer_address" name="customer_address" value="<?= htmlspecialchars($customer_details['Address'] ?? '') ?>" required>
                         </div>
                          <div class="mb-3">
                             <label for="customer_id_type" class="form-label">ID Type</label>
                             <select class="form-select" id="customer_id_type" name="customer_id_type">
                                 <option value="Passport" selected>Passport</option>
                                 <option value="Driver License">Driver License</option>
                                 <option value="National ID">National ID</option>
                             </select>
                         </div>
                         <p class="small text-muted">Note: A password will not be set for this customer via direct rental.</p>
                         <hr>
                     <?php endif; ?>
                     
                     <button type="submit" name="confirm_direct_rental" class="btn btn-lg btn-success">
                         <i class="fas fa-check-circle me-2"></i> Confirm Direct Rental
                     </button>
                     <a href="direct_rental.php" class="btn btn-secondary ms-2">Start Over</a>
                 </form>
             </div>
        </div>
    <?php endif; ?>

</div>

<?php
require_once '../includes/footer.php'; 
?> 