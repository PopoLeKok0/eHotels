<?php
/**
 * e-Hotels Homepage
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463)
 */

// Include database connection
require_once 'config/database.php';

// Include header
include 'includes/header.php';

// Get featured hotels (5-star hotels from different chains)
try {
    // Use the Database class instance
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection(); // Get the PDO connection object

    $featuredHotelsStmt = $db->query("
        SELECT h.Hotel_Address, h.Chain_Name, h.Star_Rating, h.Area, 
               COUNT(r.Room_Num) AS Room_Count,
               MIN(r.Price) AS Min_Price
        FROM Hotel h
        JOIN Room r ON h.Hotel_Address = r.Hotel_Address
        WHERE h.Star_Rating = 5
        GROUP BY h.Hotel_Address, h.Chain_Name, h.Star_Rating, h.Area
        ORDER BY h.Chain_Name
        LIMIT 5
    ");
    $featuredHotels = $featuredHotelsStmt->fetchAll(); // Use fetchAll directly on PDOStatement
    
    // Get areas for dropdown
    $areasStmt = $db->query("
        SELECT DISTINCT Area 
        FROM Hotel 
        ORDER BY Area
    ");
    $areas = $areasStmt->fetchAll();
    
    // Get hotel chains for dropdown
    $chainsStmt = $db->query("
        SELECT Chain_Name 
        FROM Hotel_Chain 
        ORDER BY Chain_Name
    ");
    $chains = $chainsStmt->fetchAll();

    // Get all hotel chains for the main display section
    $allChainsStmt = $db->query("
        SELECT Chain_Name, Number_of_Hotels, Central_Office_Address 
        FROM Hotel_Chain 
        ORDER BY Chain_Name
    ");
    $allHotelChains = $allChainsStmt->fetchAll();
    
    // **** NEW: Query the available_rooms_per_area view ****
    $stmtAvailableRooms = $db->query("SELECT Area, Total_Available_Rooms FROM available_rooms_per_area ORDER BY Total_Available_Rooms DESC LIMIT 6");
    if ($stmtAvailableRooms) {
        $availableRoomsPerArea = $stmtAvailableRooms->fetchAll();
    } else {
         error_log("Index.php: Failed to fetch from available_rooms_per_area view.");
         $availableRoomsPerArea = []; // Ensure it's an array
    }

} catch (Exception $e) {
    // Set error message
    $_SESSION['error_message'] = "Error loading featured hotels: " . $e->getMessage();
    $featuredHotels = [];
    $areas = [];
    $chains = [];
    $allHotelChains = []; // Initialize if error
    $availableRoomsPerArea = []; // Initialize variable for view data
}
?>

<!-- Hero Section -->
<div class="bg-primary text-white text-center py-5">
    <div class="container">
        <h1 class="display-4">Find Your Perfect Hotel Room</h1>
        <p class="lead">Search across 5 major hotel chains with real-time availability</p>
    </div>
</div>

<!-- Search Form -->
<div class="container mt-n4">
    <div class="card shadow-lg">
        <div class="card-body">
            <form action="search.php" method="GET" id="searchForm">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label for="area" class="form-label">Location</label>
                        <select name="area" id="area" class="form-select">
                            <option value="">All Areas</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= htmlspecialchars($area['Area']) ?>"><?= htmlspecialchars($area['Area']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <label for="chain" class="form-label">Hotel Chain</label>
                        <select name="chain" id="chain" class="form-select">
                            <option value="">All Chains</option>
                            <?php foreach ($chains as $chain): ?>
                                <option value="<?= htmlspecialchars($chain['Chain_Name']) ?>"><?= htmlspecialchars($chain['Chain_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <label for="capacity" class="form-label">Guests</label>
                        <select name="capacity" id="capacity" class="form-select">
                            <option value="">Any</option>
                            <option value="1">1 Person</option>
                            <option value="2">2 People</option>
                            <option value="3">3 People</option>
                            <option value="4">4 People</option>
                            <option value="5">5+ People</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <label for="start_date" class="form-label">Check-in</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               min="<?= date('Y-m-d') ?>" 
                               value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <label for="end_date" class="form-label">Check-out</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               min="<?= date('Y-m-d', strtotime('+2 days')) ?>" 
                               value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6 col-lg-3">
                        <label for="stars" class="form-label">Star Rating</label>
                        <select name="stars" id="stars" class="form-select">
                            <option value="">Any</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <label for="price_range" class="form-label">Price Range (per night)</label>
                        <select name="price_range" id="price_range" class="form-select">
                            <option value="">Any</option>
                            <option value="0-100">Under $100</option>
                            <option value="100-200">$100 - $200</option>
                            <option value="200-300">$200 - $300</option>
                            <option value="300-400">$300 - $400</option>
                            <option value="400-10000">$400+</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="extendable" name="extendable" value="1">
                            <label class="form-check-label" for="extendable">
                                Extendable rooms only
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Search Rooms
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Available Rooms by Area View -->
<div class="container mt-5">
    <h2 class="mb-4">Available Rooms by Area</h2>
    <div class="row">
        <?php
        // **** USE THE NEW VIEW DATA ****
        if (empty($availableRoomsPerArea)) {
             echo '<div class="col"><div class="alert alert-info">No area availability data found currently.</div></div>';
        } else {
             foreach ($availableRoomsPerArea as $areaInfo):
                 // Ensure the key exists and is not null
                 $totalRooms = $areaInfo['Total_Available_Rooms'] ?? 0;
                 if ($totalRooms > 0): // Only show areas with available rooms
        ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($areaInfo['Area']) ?></h5>
                        <p class="card-text mt-auto"> 
                            <span class="badge bg-success fs-6"><?= $totalRooms ?></span> rooms currently available.
                        </p>
                        <a href="search.php?area=<?= urlencode($areaInfo['Area']) ?>" class="btn btn-outline-primary btn-sm mt-2">
                            <i class="fas fa-search me-1"></i> View Rooms in <?= htmlspecialchars($areaInfo['Area']) ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php 
                 endif; // End if totalRooms > 0
             endforeach;
         } // End else
        ?>
    </div>
</div>

<!-- Featured Hotels -->
<div class="container mt-5">
    <h2 class="mb-4">Featured 5-Star Hotels</h2>
    <div class="row">
        <?php 
        if (empty($featuredHotels)) {
             echo '<div class="col"><div class="alert alert-info">No featured hotels available at this time.</div></div>';
        } else {
            foreach ($featuredHotels as $hotel): 
        ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($hotel['Chain_Name']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <?= str_repeat('<i class="fas fa-star text-warning"></i>', $hotel['Star_Rating']) ?>
                        </h6>
                        <p class="card-text">
                            <strong>Location:</strong> <?= htmlspecialchars($hotel['Area']) ?><br>
                            <strong>Address:</strong> <?= htmlspecialchars($hotel['Hotel_Address']) ?><br>
                            <strong>Rooms:</strong> <?= $hotel['Room_Count'] ?><br>
                            <strong>Starting at:</strong> $<?= number_format($hotel['Min_Price'], 2) ?> per night
                        </p>
                        <a href="search.php?hotel=<?= urlencode($hotel['Hotel_Address']) ?>" class="btn btn-primary mt-auto">
                           <i class="fas fa-door-open me-1"></i> View Rooms
                        </a>
                    </div>
                </div>
            </div>
        <?php 
            endforeach; 
        } // End else
        ?>
    </div>
</div>

<!-- Hotel Chains Section -->
<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Our Hotel Chains</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 justify-content-center">
        <?php 
        if (empty($allHotelChains)) {
             echo '<div class="col"><div class="alert alert-info">Hotel chain information is currently unavailable.</div></div>';
        } else {
            foreach ($allHotelChains as $chainData): 
                // Basic description mapping (can be improved or moved to DB)
                $descriptions = [
                    'AccorHotels' => 'Accor is a French multinational hospitality company that owns, manages and franchises hotels, resorts and vacation properties.',
                    'Hilton Worldwide' => 'Hilton Worldwide Holdings Inc. is a global hospitality company with a portfolio of 18 brands comprising more than 6,500 properties.',
                    'InterContinental Hotels Group' => 'IHG Hotels & Resorts is a British multinational hospitality company headquartered in Denham, Buckinghamshire, England.',
                    'Marriott International' => 'Marriott International is a leading global lodging company with more than 7,600 properties across 133 countries and territories.',
                    'Wyndham Hotels & Resorts' => 'Wyndham Hotels & Resorts, Inc. is an American hotel company based in Parsippany, New Jersey.'
                ];
                $description = $descriptions[$chainData['Chain_Name']] ?? 'A leading global hotel chain offering quality accommodations.'; // Default description
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($chainData['Chain_Name']) ?></h5>
                        <p class="card-text">
                             <span class="badge bg-info"><?= htmlspecialchars($chainData['Number_of_Hotels']) ?></span> Hotels Worldwide
                        </p>
                        <p class="card-text small text-muted flex-grow-1">
                             <?= htmlspecialchars($description) ?>
                        </p>
                        <a href="search.php?chain=<?= urlencode($chainData['Chain_Name']) ?>" class="btn btn-outline-primary mt-auto">
                           <i class="fas fa-building me-1"></i> View <?= htmlspecialchars($chainData['Chain_Name']) ?> Hotels
                        </a>
                    </div>
                </div>
            </div>
        <?php 
            endforeach; 
        } // End else
        ?>
    </div>
</div>

<!-- jQuery validation for dates -->
<!--
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
    
    // Form validation before submit
    document.getElementById('searchForm').addEventListener('submit', function(event) {
        if (!startDateInput.value || !endDateInput.value) {
            alert('Please select both check-in and check-out dates.');
            event.preventDefault();
            return;
        }
        
        if (new Date(endDateInput.value) <= new Date(startDateInput.value)) {
            alert('Check-out date must be after check-in date.');
            event.preventDefault();
            return;
        }
    });
});
</script>
-->

<?php
// Include footer
include 'includes/footer.php';
?> 