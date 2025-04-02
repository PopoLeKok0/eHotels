<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied. Please log in as an employee."; header("Location: ../login.php"); exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$hotels = [];
$searchTerm = $_GET['search'] ?? '';
$message = $_SESSION['success_message'] ?? $_SESSION['error_message'] ?? '';
$messageType = isset($_SESSION['success_message']) ? 'success' : (isset($_SESSION['error_message']) ? 'danger' : '');
unset($_SESSION['success_message'], $_SESSION['error_message']); // Clear message after displaying

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Base query - Join with HotelChain to get chain name
    $query = "SELECT h.Hotel_ID, hc.Chain_Name AS Hotel_Chain_Name, h.Hotel_Name, 
                     h.Street_Address, h.City, h.Province_State, h.Postal_Code_Zip, 
                     h.Phone_Number, h.Email_Address, h.Star_Category, h.Number_of_Rooms
              FROM Hotel h
              JOIN HotelChain hc ON h.Hotel_Chain_ID = hc.Hotel_Chain_ID";
    $params = [];

    // Apply search filter if provided
    if (!empty($searchTerm)) {
        $query .= " WHERE h.Hotel_Name LIKE :term 
                      OR hc.Chain_Name LIKE :term 
                      OR h.Street_Address LIKE :term 
                      OR h.City LIKE :term 
                      OR h.Province_State LIKE :term 
                      OR h.Email_Address LIKE :term";
        $params[':term'] = '%' . $searchTerm . '%';
    }

    $query .= " ORDER BY hc.Chain_Name, h.Hotel_Name LIMIT 100"; // Limit results for performance

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine address parts for display
    foreach ($hotels as &$hotel) {
        $hotel['Full_Address'] = implode(', ', array_filter([
            $hotel['Street_Address'], 
            $hotel['City'], 
            $hotel['Province_State'], 
            $hotel['Postal_Code_Zip']
        ]));
    }
    unset($hotel); // Unset reference

} catch (Exception $e) {
    $message = "Error fetching hotel data: " . $e->getMessage();
    $messageType = 'danger';
    error_log("Manage Hotels DB Error: " . $e->getMessage());
}

?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Hotels</h1>
        <a href="add_hotel.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add New Hotel</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="GET" action="manage_hotels.php" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search by Hotel Name, Chain, Address, City, Province, Email..." name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Search</button>
            <?php if (!empty($searchTerm)): ?>
                 <a href="manage_hotels.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Hotel Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Chain</th>
                    <th>Hotel Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Stars</th>
                    <th>Rooms</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hotels)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No hotels found<?= !empty($searchTerm) ? ' matching "' . htmlspecialchars($searchTerm) . '"' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hotels as $hotel): ?>
                        <tr>
                            <td><?= htmlspecialchars($hotel['Hotel_Chain_Name']) ?></td>
                            <td><?= htmlspecialchars($hotel['Hotel_Name']) ?></td>
                            <td><?= htmlspecialchars($hotel['Full_Address']) ?></td>
                            <td><?= htmlspecialchars($hotel['Phone_Number']) ?></td>
                            <td><?= htmlspecialchars($hotel['Email_Address']) ?></td>
                            <td><?= str_repeat('<i class="fas fa-star text-warning"></i>', $hotel['Star_Category']) ?> (<?= $hotel['Star_Category'] ?>)</td>
                            <td><?= htmlspecialchars($hotel['Number_of_Rooms']) ?></td>
                            <td>
                                <a href="manage_rooms.php?hotel_id=<?= htmlspecialchars($hotel['Hotel_ID']) ?>" class="btn btn-sm btn-secondary me-1" title="Manage Rooms"><i class="fas fa-door-open"></i></a>
                                <a href="edit_hotel.php?id=<?= htmlspecialchars($hotel['Hotel_ID']) ?>" class="btn btn-sm btn-warning me-1" title="Edit Hotel"><i class="fas fa-edit"></i></a>
                                <!-- Delete Form -->
                                <form action="delete_hotel.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete hotel '<?= htmlspecialchars(addslashes($hotel['Hotel_Name'])) ?>'? This will also attempt to delete associated rooms and might fail if there are bookings/rentings.');">
                                    <input type="hidden" name="hotel_id" value="<?= htmlspecialchars($hotel['Hotel_ID']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Hotel"><i class="fas fa-trash"></i></button>
                                </form>
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