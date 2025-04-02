<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
include 'includes/header.php';

// --- Get Search Parameters ---
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d', strtotime('+1 day'));
$city_province = trim($_GET['city_province'] ?? '');
$capacity = filter_input(INPUT_GET, 'capacity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$chain_id = filter_input(INPUT_GET, 'chain_id', FILTER_VALIDATE_INT);
$min_stars = filter_input(INPUT_GET, 'min_stars', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$min_total_rooms = filter_input(INPUT_GET, 'min_total_rooms', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);

// Basic date validation
$errors = [];
if (strtotime($end_date) <= strtotime($start_date)) {
    $errors[] = "Check-out date must be after check-in date.";
    // Set default dates if invalid to prevent SQL errors
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 day'));
}

$availableRooms = [];

// Proceed only if dates are valid
if (empty($errors)) {
    try {
        $dbInstance = getDatabase();
        $db = $dbInstance->getConnection();

        // --- Construct Base Query --- 
        $sql = "
            SELECT 
                r.Room_ID, r.Room_Number, r.Price, r.Capacity, r.View_Type, r.Amenities, r.Is_Extendable,
                h.Hotel_ID, h.Hotel_Name, h.Star_Category, h.Street_Address, h.City, h.Province_State,
                hc.Chain_Name
            FROM Room r
            JOIN Hotel h ON r.Hotel_ID = h.Hotel_ID
            JOIN HotelChain hc ON h.Hotel_Chain_ID = hc.Hotel_Chain_ID
            WHERE 1=1 
        ";
        $params = [];

        // --- Apply Filters --- 
        if ($capacity) {
            if ($capacity >= 6) { // Handle 6+ case
                $sql .= " AND r.Capacity >= :capacity";
            } else {
                $sql .= " AND r.Capacity = :capacity";
            }
             $params[':capacity'] = $capacity;
        }
        if ($chain_id) {
            $sql .= " AND h.Hotel_Chain_ID = :chain_id";
            $params[':chain_id'] = $chain_id;
        }
        if ($min_stars) {
            $sql .= " AND h.Star_Category >= :min_stars";
            $params[':min_stars'] = $min_stars;
        }
        if ($min_total_rooms) {
            $sql .= " AND h.Number_of_Rooms >= :min_total_rooms";
            $params[':min_total_rooms'] = $min_total_rooms;
        }
         if ($max_price !== null) { // Check for null explicitly as 0 is valid
            $sql .= " AND r.Price <= :max_price";
            $params[':max_price'] = $max_price;
        }
        if (!empty($city_province)) {
            $sql .= " AND (h.City LIKE :location OR h.Province_State LIKE :location)";
            $params[':location'] = '%' . $city_province . '%';
        }

        // --- Availability Subquery --- 
        $sql .= " AND r.Room_ID NOT IN (
                    SELECT b.Room_ID 
                    FROM Booking b 
                    WHERE b.Room_ID IS NOT NULL 
                      AND (
                          (b.Start_Date < :end_date AND b.End_Date > :start_date) -- Check for overlap
                      )
                    UNION
                    SELECT rt.Room_ID 
                    FROM Renting rt 
                    WHERE rt.Room_ID IS NOT NULL
                      AND (
                          (rt.Start_Date < :end_date AND rt.End_Date > :start_date) -- Check for overlap
                      )
                )";
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
        
        // --- Ordering --- 
        $sql .= " ORDER BY hc.Chain_Name, h.Star_Category DESC, r.Price ASC";

        // --- Execute Query --- 
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Search Results DB Error: " . $e->getMessage());
        $errors[] = "Database error occurred while searching for rooms. Please try again later.";
    } catch (Exception $e) {
        error_log("Search Results Error: " . $e->getMessage());
        $errors[] = "An unexpected error occurred. Please try again later.";
    }
}

// --- Display Results --- 
?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Search Results</li>
        </ol>
    </nav>

    <h1 class="mb-4">Available Rooms</h1>

    <!-- Display Errors -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Display Search Criteria Summary (Optional but helpful) -->
    <div class="alert alert-info mb-4">
        Showing results for stays between <strong><?= htmlspecialchars($start_date) ?></strong> and <strong><?= htmlspecialchars($end_date) ?></strong>.
        <?php 
            $filters_applied = [];
            if ($city_province) $filters_applied[] = "Location like '" . htmlspecialchars($city_province) . "'";
            if ($capacity) $filters_applied[] = "Guests: " . htmlspecialchars($capacity) . ($capacity >= 6 ? '+' : '');
            // Fetch chain name if ID provided for better display
            if ($chain_id) {
                 try {
                    $chainNameStmt = $db->prepare("SELECT Chain_Name FROM HotelChain WHERE Hotel_Chain_ID = :id");
                    $chainNameStmt->bindParam(':id', $chain_id, PDO::PARAM_INT);
                    $chainNameStmt->execute();
                    $chainName = $chainNameStmt->fetchColumn();
                     if ($chainName) $filters_applied[] = "Chain: " . htmlspecialchars($chainName);
                 } catch(Exception $e) {/* Ignore error */} 
            }
            if ($min_stars) $filters_applied[] = "Min Rating: " . htmlspecialchars($min_stars) . " stars";
            if ($min_total_rooms) $filters_applied[] = "Min Hotel Rooms: " . htmlspecialchars($min_total_rooms);
            if ($max_price !== null) $filters_applied[] = "Max Price: $" . htmlspecialchars(number_format($max_price, 2));
            
            if (!empty($filters_applied)) {
                echo "<br>Filters: " . implode(", ", $filters_applied) . ".";
            }
        ?>
        <a href="index.php" class="ms-3">Modify Search</a>
    </div>

    <!-- Display Results -->
    <div class="row g-4">
        <?php if (empty($availableRooms) && empty($errors)): ?>
            <div class="col-12">
                <div class="alert alert-warning">No available rooms found matching your criteria. Please try broadening your search.</div>
            </div>
        <?php elseif (!empty($availableRooms)): ?>
            <?php foreach ($availableRooms as $room): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                         <div class="card-header bg-light">
                             <h5 class="card-title mb-0"><?= htmlspecialchars($room['Hotel_Name']) ?></h5>
                             <small class="text-muted"><?= htmlspecialchars($room['Chain_Name']) ?></small>
                         </div>
                        <div class="card-body">
                           <h6 class="card-subtitle mb-2">Room #<?= htmlspecialchars($room['Room_Number']) ?></h6>
                           <div><?= str_repeat('<i class="fas fa-star text-warning"></i> ', $room['Star_Category']) ?></div>
                            <p class="card-text mt-2">
                                <i class="fas fa-users me-1"></i> Capacity: <?= htmlspecialchars($room['Capacity']) ?><br>
                                <i class="fas fa-binoculars me-1"></i> View: <?= htmlspecialchars($room['View_Type'] ?: 'N/A') ?><br>
                                <?php if($room['Amenities']): ?>
                                <i class="fas fa-concierge-bell me-1"></i> Amenities: <?= htmlspecialchars(substr($room['Amenities'], 0, 50)) . (strlen($room['Amenities']) > 50 ? '...' : '') ?><br>
                                <?php endif; ?>
                                <?php if($room['Is_Extendable']): ?>
                                <i class="fas fa-expand-arrows-alt me-1"></i> Extendable Room
                                <?php endif; ?>
                            </p>
                             <p class="card-text fs-5 fw-bold text-success">$<?= number_format($room['Price'], 2) ?> <small class="text-muted fw-normal">/ night</small></p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <!-- Booking Form -->
                             <form action="booking_process.php" method="POST">
                                <input type="hidden" name="room_id" value="<?= htmlspecialchars($room['Room_ID']) ?>">
                                <input type="hidden" name="hotel_id" value="<?= htmlspecialchars($room['Hotel_ID']) ?>">
                                <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                <input type="hidden" name="price" value="<?= htmlspecialchars($room['Price']) ?>">
                                
                                <button type="submit" class="btn btn-primary w-100"> 
                                    <i class="fas fa-calendar-check me-1"></i> Book Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php
include 'includes/footer.php';
?> 