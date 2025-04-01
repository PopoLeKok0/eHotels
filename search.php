<?php
/**
 * e-Hotels Room Search Page
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 */

// Start session if not already started

// Include database connection
require_once 'config/database.php';

// Include header
include 'includes/header.php';

// Initialize the database
$db = getDatabase();

// Get filter parameters
$area = isset($_GET['area']) ? trim($_GET['area']) : '';
$chain = isset($_GET['chain']) ? trim($_GET['chain']) : '';
$stars = isset($_GET['stars']) ? intval($_GET['stars']) : 0;
$capacity = isset($_GET['capacity']) ? intval($_GET['capacity']) : 0;
$hotel = isset($_GET['hotel']) ? trim($_GET['hotel']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-d', strtotime('+1 day'));
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-d', strtotime('+3 days'));
$extendable = isset($_GET['extendable']) ? true : false;
$total_rooms_range = isset($_GET['total_rooms']) ? trim($_GET['total_rooms']) : ''; // Get total rooms filter

// Process price range
$min_price = 0;
$max_price = 10000; // Default max price
if (isset($_GET['price_range']) && !empty($_GET['price_range'])) {
    $price_parts = explode('-', $_GET['price_range']);
    if (count($price_parts) == 2) {
        $min_price = floatval($price_parts[0]);
        $max_price = floatval($price_parts[1]);
    }
}

// Process total rooms range
$min_total_rooms = 0;
$max_total_rooms = 10000; // Default max
if (!empty($total_rooms_range)) {
    $room_range_parts = explode('-', $total_rooms_range);
    if (count($room_range_parts) == 2) {
        $min_total_rooms = intval($room_range_parts[0]);
        $max_total_rooms = intval($room_range_parts[1]);
    }
}

// Build search query
try {
    $query = "
        SELECT 
            h.Hotel_Address, 
            h.Chain_Name, 
            h.Star_Rating, 
            h.Area,
            r.Room_Num,
            r.Capacity,
            r.View_Type,
            r.Price,
            r.Amenities,
            r.Extendable,
            r.Availability,
            h.Num_Rooms
        FROM 
            Room r
        JOIN 
            Hotel h ON r.Hotel_Address = h.Hotel_Address
        WHERE 
            r.Availability = TRUE
            AND r.Price BETWEEN :min_price AND :max_price
            AND h.Num_Rooms BETWEEN :min_total_rooms AND :max_total_rooms
    ";
    
    $params = [
        ':min_price' => $min_price,
        ':max_price' => $max_price,
        ':min_total_rooms' => $min_total_rooms,
        ':max_total_rooms' => $max_total_rooms
    ];
    
    // Add filters based on parameters
    if (!empty($area)) {
        $query .= " AND h.Area = :area";
        $params[':area'] = $area;
    }
    
    if (!empty($chain)) {
        $query .= " AND h.Chain_Name = :chain";
        $params[':chain'] = $chain;
    }
    
    if ($stars > 0) {
        $query .= " AND h.Star_Rating = :stars";
        $params[':stars'] = $stars;
    }
    
    if ($capacity > 0) {
        $query .= " AND r.Capacity >= :capacity";
        $params[':capacity'] = $capacity;
    }
    
    if (!empty($hotel)) {
        $query .= " AND h.Hotel_Address = :hotel";
        $params[':hotel'] = $hotel;
    }
    
    if ($extendable) {
        $query .= " AND r.Extendable = TRUE";
    }
    
    // Check that the room is not booked for the selected dates
    $query .= " AND NOT EXISTS (
        SELECT 1
        FROM Booking b
        JOIN Reserved_By rb ON b.Booking_ID = rb.Booking_ID
        WHERE rb.Hotel_Address = r.Hotel_Address
            AND rb.Room_Num = r.Room_Num
            AND (
                (b.Start_Date <= :end_date AND b.End_Date >= :start_date)
            )
    )";
    
    $params[':start_date'] = $start_date;
    $params[':end_date'] = $end_date;
    
    // Check that the room is not rented for the selected dates
    $query .= " AND NOT EXISTS (
        SELECT 1
        FROM Renting rnt
        JOIN Rented_By rb ON rnt.Renting_ID = rb.Renting_ID
        WHERE rb.Hotel_Address = r.Hotel_Address
            AND rb.Room_Num = r.Room_Num
            AND (
                (rnt.Start_Date <= :end_date2 AND rnt.End_Date >= :start_date2)
            )
    )";
    
    $params[':start_date2'] = $start_date;
    $params[':end_date2'] = $end_date;
    
    $query .= " ORDER BY h.Star_Rating DESC, r.Price ASC";
    
    // Execute query
    $rooms = $db->query($query, $params);
    
    // Get areas for filter
    $areas = $db->query("SELECT DISTINCT Area FROM Hotel ORDER BY Area");
    
    // Get chains for filter
    $chains = $db->query("SELECT Chain_Name FROM Hotel_Chain ORDER BY Chain_Name");
    
    // Count results
    $total_results = count($rooms);
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error searching for rooms: " . $e->getMessage();
    $rooms = [];
    $areas = [];
    $chains = [];
    $total_results = 0;
}

// Check if query results are valid before using them
if ($areas === false) {
    error_log("Search.php: Failed to fetch areas.");
    $areas = []; // Set to empty array to prevent errors in foreach
}
if ($chains === false) {
    error_log("Search.php: Failed to fetch chains.");
    $chains = []; // Set to empty array to prevent errors in foreach
}
if ($rooms === false) {
    error_log("Search.php: Failed to fetch rooms.");
    $rooms = []; // Set to empty array to prevent errors in foreach
    $total_results = 0;
} else {
     // Count results only if query succeeded
     $total_results = count($rooms); 
}

// Calculate nights for price calculation
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$interval = $date1->diff($date2);
$nights = $interval->days;
?>

<!-- Search Results Hero -->
<div class="bg-primary text-white py-4">
    <div class="container">
        <h1 class="h3">Search Results</h1>
        <p class="mb-0">
            <?= $total_results ?> rooms available
            <?= !empty($area) ? "in $area" : "" ?>
            <?= !empty($chain) ? "with $chain" : "" ?>
            <?= ($stars > 0) ? "in $stars-star hotels" : "" ?>
            from <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?>
            (<?= $nights ?> nights)
        </p>
    </div>
</div>

<!-- Search Form and Results -->
<div class="container mt-4">
    <div class="row">
        <!-- Search Filter Sidebar -->
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filter Results</h5>
                </div>
                <div class="card-body">
                    <form action="search.php" method="GET" id="filterForm">
                        <div class="mb-3">
                            <label for="area" class="form-label">Location</label>
                            <select name="area" id="area" class="form-select">
                                <option value="">All Areas</option>
                                <?php if (is_array($areas) || $areas instanceof Traversable): // Check before looping ?>
                                    <?php foreach ($areas as $area_option): ?>
                                        <option value="<?= htmlspecialchars($area_option['Area']) ?>" 
                                                <?= (isset($area) && $area == $area_option['Area']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($area_option['Area']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="chain" class="form-label">Hotel Chain</label>
                            <select name="chain" id="chain" class="form-select">
                                <option value="">All Chains</option>
                                <?php if (is_array($chains) || $chains instanceof Traversable): // Check before looping ?>
                                    <?php foreach ($chains as $chain_option): ?>
                                        <option value="<?= htmlspecialchars($chain_option['Chain_Name']) ?>" 
                                                <?= (isset($chain) && $chain == $chain_option['Chain_Name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($chain_option['Chain_Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stars" class="form-label">Star Rating</label>
                            <select name="stars" id="stars" class="form-select">
                                <option value="">Any</option>
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?= $i ?>" <?= ($stars == $i) ? 'selected' : '' ?>>
                                        <?= $i ?> Stars<?= ($i == 1) ? '' : '' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Guests</label>
                            <select name="capacity" id="capacity" class="form-select">
                                <option value="">Any</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($capacity == $i) ? 'selected' : '' ?>>
                                        <?= $i ?> <?= ($i == 1) ? 'Person' : 'People' ?>
                                    </option>
                                <?php endfor; ?>
                                <option value="6" <?= ($capacity >= 6) ? 'selected' : '' ?>>6+ People</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price_range" class="form-label">Price Range (per night)</label>
                            <select name="price_range" id="price_range" class="form-select">
                                <option value="">Any</option>
                                <option value="0-100" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '0-100') ? 'selected' : '' ?>>Under $100</option>
                                <option value="100-200" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '100-200') ? 'selected' : '' ?>>$100 - $200</option>
                                <option value="200-300" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '200-300') ? 'selected' : '' ?>>$200 - $300</option>
                                <option value="300-400" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '300-400') ? 'selected' : '' ?>>$300 - $400</option>
                                <option value="400-10000" <?= (isset($_GET['price_range']) && $_GET['price_range'] == '400-10000') ? 'selected' : '' ?>>$400+</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_rooms" class="form-label">Hotel Size (Total Rooms)</label>
                            <select name="total_rooms" id="total_rooms" class="form-select">
                                <option value="">Any Size</option>
                                <option value="1-50" <?= (isset($_GET['total_rooms']) && $_GET['total_rooms'] == '1-50') ? 'selected' : '' ?>>Small (1-50 Rooms)</option>
                                <option value="51-100" <?= (isset($_GET['total_rooms']) && $_GET['total_rooms'] == '51-100') ? 'selected' : '' ?>>Medium (51-100 Rooms)</option>
                                <option value="101-200" <?= (isset($_GET['total_rooms']) && $_GET['total_rooms'] == '101-200') ? 'selected' : '' ?>>Large (101-200 Rooms)</option>
                                <option value="201-10000" <?= (isset($_GET['total_rooms']) && $_GET['total_rooms'] == '201-10000') ? 'selected' : '' ?>>Very Large (201+ Rooms)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Check-in</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   min="<?= date('Y-m-d') ?>" 
                                   value="<?= $start_date ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Check-out</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" 
                                   value="<?= $end_date ?>">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="extendable" name="extendable" value="1" <?= $extendable ? 'checked' : '' ?>>
                                <label class="form-check-label" for="extendable">
                                    Extendable rooms only
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- View Available Rooms by Area -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Available Rooms by Area</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        try {
                            $availableRooms = $db->query("SELECT Area, Total_Available_Rooms FROM available_rooms_per_area WHERE Total_Available_Rooms > 0 ORDER BY Total_Available_Rooms DESC LIMIT 10");
                            
                            if ($availableRooms !== false && (is_array($availableRooms) || $availableRooms instanceof Traversable)): // Check before looping
                                foreach ($availableRooms as $areaData):
                        ?>
                            <a href="search.php?area=<?= urlencode($areaData['Area']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($areaData['Area']) ?>
                                <span class="badge bg-primary rounded-pill"><?= $areaData['Total_Available_Rooms'] ?></span>
                            </a>
                        <?php 
                                endforeach;
                            else: // Handle query failure or empty results gracefully
                                if ($availableRooms === false) {
                                    error_log("Search.php: Error fetching available rooms per area.");
                                }
                                echo '<div class="list-group-item">No area data available currently.</div>';
                            endif;
                        } catch (Exception $e) {
                            error_log("Error fetching sidebar area data: " . $e->getMessage());
                            echo '<div class="list-group-item">Could not load area data.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Results -->
        <div class="col-lg-9">
            <!-- Results Count -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="mb-0"><?= $total_results ?> rooms found</p>
                <div>
                    <small class="text-muted">Showing all available rooms for selected dates</small>
                </div>
            </div>
            
            <!-- Room Listings -->
            <?php if ($rooms !== false && count($rooms) > 0): // Also check $rooms itself ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="card mb-4 room-card shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <h5 class="text-primary"><?= htmlspecialchars($room['Chain_Name']) ?></h5>
                                    <div class="mb-2 star-rating">
                                        <?= str_repeat('<i class="fas fa-star text-warning"></i>', $room['Star_Rating']) ?>
                                    </div>
                                    <p class="mb-1">
                                        <strong>Hotel:</strong> <small><?= htmlspecialchars($room['Hotel_Address']) ?></small>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Area:</strong> <?= htmlspecialchars($room['Area']) ?>
                                    </p>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0 border-start border-end">
                                    <h6>Room #<?= htmlspecialchars($room['Room_Num']) ?></h6>
                                    <ul class="list-unstyled mb-0 small">
                                        <li><strong>Capacity:</strong> <?= htmlspecialchars($room['Capacity']) ?> <?= ($room['Capacity'] == 1) ? 'Person' : 'People' ?></li>
                                        <li><strong>View:</strong> <?= htmlspecialchars($room['View_Type']) ?></li>
                                        <li><strong>Extendable:</strong> <?= $room['Extendable'] ? 'Yes' : 'No' ?></li>
                                    </ul>
                                    <p class="mt-2 mb-0 small text-muted">
                                         <strong>Amenities:</strong> <?= htmlspecialchars($room['Amenities']) ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="room-price mb-2">
                                        <span class="fs-4 fw-bold">$<?= number_format($room['Price'], 2) ?></span> <small class="text-muted">/ night</small>
                                    </div>
                                    <div class="total-price mb-3">
                                        <small>$<?= number_format($room['Price'] * $nights, 2) ?> total (<?= $nights ?> nights)</small>
                                    </div>
                                    <?php 
                                    // Construct booking/login URL (assuming header.php sets $loggedIn etc.)
                                    $loggedIn = isset($_SESSION['user_id']); // Simple check
                                    $isCustomer = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
                                    $isEmployee = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
                                    $bookingParams = http_build_query([
                                        'hotel' => $room['Hotel_Address'], // Corrected case
                                        'room' => $room['Room_Num'], // Corrected case
                                        'start' => $start_date,
                                        'end' => $end_date
                                    ]);
                                    $loginRedirect = 'login.php?redirect=' . urlencode("search.php?" . $_SERVER['QUERY_STRING']);
                                    
                                    if ($loggedIn && $isCustomer): ?>
                                        <a href="booking.php?<?= $bookingParams ?>" class="btn btn-primary w-100">
                                           <i class="fas fa-calendar-check me-1"></i> Book Now
                                        </a>
                                    <?php elseif ($loggedIn && $isEmployee): ?>
                                        <a href="employee/direct_rental.php?<?= $bookingParams ?>" class="btn btn-success w-100">
                                           <i class="fas fa-key me-1"></i> Rent Room (Employee)
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $loginRedirect ?>" class="btn btn-secondary w-100">
                                           <i class="fas fa-sign-in-alt me-1"></i> Login to Book
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No rooms available!</h4>
                    <p>No rooms match your search criteria for the selected dates. Please try adjusting your filters or dates.</p>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Search Tips</h5>
                        <ul>
                            <li>Try different dates</li>
                            <li>Expand your search area</li>
                            <li>Consider hotels with different star ratings</li>
                            <li>Adjust your price range</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Ensure end date is after start date
    startDateInput.addEventListener('change', function() {
        if (endDateInput.value && new Date(endDateInput.value) <= new Date(startDateInput.value)) {
            const newEndDate = new Date(startDateInput.value);
            newEndDate.setDate(newEndDate.getDate() + 1);
            endDateInput.value = newEndDate.toISOString().split('T')[0];
        }
        
        // Update min date for end date input
        const minEndDate = new Date(startDateInput.value);
        minEndDate.setDate(minEndDate.getDate() + 1);
        endDateInput.min = minEndDate.toISOString().split('T')[0];
    });
    
    // Apply the same logic on page load
    if (startDateInput.value) {
        const minEndDate = new Date(startDateInput.value);
        minEndDate.setDate(minEndDate.getDate() + 1);
        endDateInput.min = minEndDate.toISOString().split('T')[0];
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 