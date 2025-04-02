<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; header("Location: ../login.php"); exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$rooms = [];
$hotels = []; // For filter dropdown
$filterHotelAddress = $_GET['hotel_address'] ?? '';
$filterAvailability = $_GET['availability'] ?? '';
$message = '';
$messageType = '';

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch Hotels for filter dropdown
    $stmtHotels = $db->query("SELECT Hotel_Address, Chain_Name FROM Hotel ORDER BY Chain_Name, Hotel_Address");
    $hotels = $stmtHotels->fetchAll(PDO::FETCH_ASSOC);

    // Base query for Rooms
    $query = "SELECT r.Hotel_Address, r.Room_Num, r.Capacity, r.Price, r.View_Type, r.Extendable, r.Availability, r.Damages, h.Chain_Name
              FROM Room r
              JOIN Hotel h ON r.Hotel_Address = h.Hotel_Address";
    $params = [];
    $conditions = [];

    // Apply filters
    if (!empty($filterHotelAddress)) {
        $conditions[] = "r.Hotel_Address = :hotel_address";
        $params[':hotel_address'] = $filterHotelAddress;
    }
    if ($filterAvailability !== '') {
        $conditions[] = "r.Availability = :availability";
        $params[':availability'] = ($filterAvailability == '1'); // Convert to boolean
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(' AND ', $conditions);
    }

    $query .= " ORDER BY h.Chain_Name, r.Hotel_Address, r.Room_Num LIMIT 100";

    $stmtRooms = $db->prepare($query);
    $stmtRooms->execute($params);
    $rooms = $stmtRooms->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Error fetching room data: " . $e->getMessage();
    $messageType = 'danger';
}

?>
<div class="container-fluid my-5"> <!-- Use fluid container for wider tables -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Rooms</h1>
        <a href="add_room.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add New Room</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="GET" action="manage_rooms.php" class="mb-4 p-3 border rounded bg-light">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="hotel_address" class="form-label">Filter by Hotel</label>
                <select name="hotel_address" id="hotel_address" class="form-select">
                    <option value="">All Hotels</option>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?= htmlspecialchars($hotel['Hotel_Address']) ?>" <?= ($filterHotelAddress == $hotel['Hotel_Address']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($hotel['Chain_Name'] . ' - ' . $hotel['Hotel_Address']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="availability" class="form-label">Availability</label>
                <select name="availability" id="availability" class="form-select">
                    <option value="">All</option>
                    <option value="1" <?= ($filterAvailability === '1') ? 'selected' : '' ?>>Available</option>
                    <option value="0" <?= ($filterAvailability === '0') ? 'selected' : '' ?>>Unavailable</option>
                </select>
            </div>
            <div class="col-md-3">
                 <button class="btn btn-primary w-100 mb-1" type="submit"><i class="fas fa-filter me-1"></i> Filter</button>
                 <?php if (!empty($filterHotelAddress) || $filterAvailability !== ''): ?>
                    <a href="manage_rooms.php" class="btn btn-secondary btn-sm w-100">Clear Filters</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Room Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm"> <!-- Added table-sm for compactness -->
            <thead class="table-dark">
                <tr>
                    <th>Hotel</th>
                    <th>Room #</th>
                    <th>Capacity</th>
                    <th>Price</th>
                    <th>View</th>
                    <th>Extendable</th>
                    <th>Available</th>
                    <th>Damages</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="9" class="text-center">No rooms found<?= (!empty($filterHotelAddress) || $filterAvailability !== '') ? ' matching the filters' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <tr class="<?= $room['Availability'] ? '' : 'table-secondary' ?>">
                            <td><?= htmlspecialchars($room['Chain_Name'] . ' - ' . $room['Hotel_Address']) ?></td>
                            <td><?= htmlspecialchars($room['Room_Num']) ?></td>
                            <td><?= htmlspecialchars($room['Capacity']) ?></td>
                            <td>$<?= number_format($room['Price'], 2) ?></td>
                            <td><?= htmlspecialchars($room['View_Type']) ?></td>
                            <td><?= $room['Extendable'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <span class="badge <?= $room['Availability'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $room['Availability'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($room['Damages'] ?? 'None') ?></td>
                            <td>
                                <a href="edit_room.php?address=<?= urlencode($room['Hotel_Address']) ?>&num=<?= $room['Room_Num'] ?>" class="btn btn-sm btn-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                <!-- Add Delete button/form here later -->
                                <button class="btn btn-sm btn-danger" title="Delete" onclick="/* Add delete confirmation later */ alert('Delete function not yet implemented.');"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

     <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
</div>
<?php require_once '../includes/footer.php'; ?> 