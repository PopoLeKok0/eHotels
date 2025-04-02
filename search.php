<?php
/**
 * e-Hotels Room Search Page
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 */

// --- Detect AJAX Request ---
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Start session if not already started (needed for login status)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/database.php';

// --- Check Login Status --- Needed for Booking button
$isCustomerLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
$customer_ssn = $isCustomerLoggedIn ? $_SESSION['user_id'] : null; // Assuming user_id is customer SSN

// Only include full header if not an AJAX request
if (!$isAjax) {
    include 'includes/header.php';
}

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
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : '';

// Process price range
$min_price = 0;
$max_price = 10000; // Default max price
if (!empty($price_range)) {
    $price_parts = explode('-', $price_range);
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

// Initialize variables for results and filters
$rooms = [];
$areas = [];
$chains = [];
$total_results = 0;
$errorMessage = '';

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
    
    $query .= " ORDER BY h.Star_Rating DESC, r.Price ASC LIMIT 200";
    
    // Execute query
    $stmt = $db->getConnection()->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_results = count($rooms);

    // Get areas for filter
    $stmtAreas = $db->getConnection()->query("SELECT DISTINCT Area FROM Hotel ORDER BY Area");
    $areas = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);
    
    // Get chains for filter
    $stmtChains = $db->getConnection()->query("SELECT Chain_Name FROM Hotel_Chain ORDER BY Chain_Name");
    $chains = $stmtChains->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errorMessage = "Error searching for rooms: " . $e->getMessage();
    error_log($errorMessage);
    if ($isAjax) {
        http_response_code(500); // Send server error status for AJAX
        echo "<div class=\"alert alert-danger\">Server error fetching results. Please try again.</div>";
        exit; // Stop processing for AJAX error
    } else {
        $_SESSION['error_message'] = $errorMessage; // Show error on full page load
    }
    $rooms = [];
    $total_results = 0;
}

// Calculate nights for price calculation
$nights = 0;
try {
    if (!empty($start_date) && !empty($end_date)) {
        $date1 = new DateTime($start_date);
        $date2 = new DateTime($end_date);
        if ($date2 > $date1) { // Ensure end date is after start date
            $interval = $date1->diff($date2);
            $nights = $interval->days;
        } else {
             if (!$isAjax) $_SESSION['error_message'] = "End date must be after start date.";
        }
    }
} catch (Exception $e) {
     if (!$isAjax) $_SESSION['error_message'] = "Invalid date format entered.";
     error_log("Date calculation error: " . $e->getMessage());
}

// --- Start Output --- 

// If NOT an AJAX request, display the full page structure (header, filters, etc.)
if (!$isAjax): 
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
            <div class="card mb-4 sticky-top" style="top: 80px;"> <!-- Make sidebar sticky -->
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filter Options</h5>
                </div>
                <div class="card-body">
                    <!-- IMPORTANT: Give the form the ID 'ajaxSearchForm' -->
                    <form action="search.php" method="GET" id="ajaxSearchForm">
                        <div class="mb-3">
                            <label for="area" class="form-label">Location</label>
                            <select name="area" id="area" class="form-select">
                                <option value="">All Areas</option>
                                <?php foreach ($areas as $area_option): ?>
                                    <option value="<?= htmlspecialchars($area_option['Area']) ?>" <?= ($area == $area_option['Area']) ? 'selected' : '' ?>><?= htmlspecialchars($area_option['Area']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="chain" class="form-label">Hotel Chain</label>
                            <select name="chain" id="chain" class="form-select">
                                <option value="">All Chains</option>
                                <?php foreach ($chains as $chain_option): ?>
                                    <option value="<?= htmlspecialchars($chain_option['Chain_Name']) ?>" <?= ($chain == $chain_option['Chain_Name']) ? 'selected' : '' ?>><?= htmlspecialchars($chain_option['Chain_Name']) ?></option>
                                <?php endforeach; ?>
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
                            $view_area_sql = "SELECT * FROM AvailableRoomsPerAreaView ORDER BY Area, Hotel_Name";
                            $view_area_stmt = $db->getConnection()->query($view_area_sql);
                            $available_rooms_by_area = $view_area_stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($available_rooms_by_area) {
                                foreach ($available_rooms_by_area as $area_view) {
                                    echo '<a href="search.php?area=' . urlencode($area_view['Area']) . '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">';
                                    echo htmlspecialchars($area_view['Area']) . ' - ' . htmlspecialchars($area_view['Hotel_Name']);
                                    echo '<span class="badge bg-primary rounded-pill">' . $area_view['Total_Available_Rooms'] . '</span>';
                                    echo '</a>';
                                }
                            } else {
                                echo '<div class="list-group-item">No area data available currently.</div>';
                            }
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
            <!-- Loading Indicator -->
            <div id="searchLoadingIndicator" style="display: none;" class="text-center my-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Searching for rooms...</p>
            </div>
            
            <!-- Error Message Container -->
            <div id="searchErrorContainer"></div>
            
            <!-- IMPORTANT: Container for AJAX results -->
            <div id="searchResultsContainer">
                <?php // Results will be loaded here by AJAX, but render initial state if not AJAX ?>
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php elseif (empty($rooms)): ?>
                    <div class="alert alert-info">No rooms found matching your criteria. Try adjusting your filters.</div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="images/room_placeholder.jpg" class="img-fluid rounded-start" alt="Room image placeholder">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($room['Chain_Name']) ?> - <?= htmlspecialchars($room['Area']) ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">Room <?= htmlspecialchars($room['Room_Num']) ?> at <?= htmlspecialchars($room['Hotel_Address']) ?></h6>
                                        <p class="card-text mb-1">
                                            <?php for ($i = 1; $i <= $room['Star_Rating']; $i++): ?><i class="fas fa-star text-warning"></i><?php endfor; ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-user-friends me-1"></i> Capacity: <?= htmlspecialchars($room['Capacity']) ?> 
                                            <?php if ($room['Extendable']): ?>(<i class="fas fa-bed text-success"></i> Extendable)<?php endif; ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <?php if ($room['View_Type']): ?><i class="fas fa-eye me-1"></i> View: <?= htmlspecialchars($room['View_Type']) ?><br><?php endif; ?>
                                            <?php if ($room['Amenities']): ?><i class="fas fa-concierge-bell me-1"></i> Amenities: <?= htmlspecialchars($room['Amenities']) ?><?php endif; ?>
                                        </p>
                                        <p class="card-text fw-bold fs-5 mb-2">$<?= number_format($room['Price'] * $nights, 2) ?> <span class="fs-6 fw-normal">(<?= $nights ?> nights at $<?= number_format($room['Price'], 2) ?>/night)</span></p>
                                        
                                        <?php if ($isCustomerLoggedIn && $nights > 0): ?>
                                            <!-- Booking Form for Logged-in Customers -->
                                            <form action="process_booking.php" method="POST">
                                                <input type="hidden" name="hotel_address" value="<?= htmlspecialchars($room['Hotel_Address']) ?>">
                                                <input type="hidden" name="room_number" value="<?= htmlspecialchars($room['Room_Num']) ?>">
                                                <input type="hidden" name="chain_name" value="<?= htmlspecialchars($room['Chain_Name']) ?>">
                                                <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                                <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                                <input type="hidden" name="price_per_night" value="<?= htmlspecialchars($room['Price']) ?>">
                                                <input type="hidden" name="total_price" value="<?= number_format($room['Price'] * $nights, 2) ?>">
                                                <input type="hidden" name="nights" value="<?= $nights ?>">
                                                <!-- Customer SSN is taken from session in process_booking.php -->
                                                <button type="submit" class="btn btn-success"><i class="fas fa-calendar-plus me-1"></i> Book Now</button>
                                            </form>
                                        <?php elseif (!$isCustomerLoggedIn && $nights > 0): ?>
                                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-outline-success">Log in to Book</a>
                                        <?php elseif ($nights <= 0): ?>
                                             <p class="text-danger"><small>Please select valid dates to enable booking.</small></p>
                                        <?php endif; ?>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
// Only include footer if not an AJAX request
endif; // end if (!$isAjax)

// If it IS an AJAX request, just output the results partial
if ($isAjax) {
    // Make variables needed by the partial available
    $loggedIn = isset($_SESSION['user_id']); 
    $isCustomer = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
    $isEmployee = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
    
    // Include the results partial directly
    include 'includes/_search_results.php';
}

// Include footer only on full page loads
if (!$isAjax) {
    include 'includes/footer.php';
}
?> 