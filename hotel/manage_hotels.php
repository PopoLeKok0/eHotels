<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Access denied. Please log in.";
    header("Location: ../login.php");
    exit;
}

$db = getDatabase();
$hotels = [];
$page_error_message = '';
$chains = [];
$selected_chain = '';

// Handle optional chain filter
if (isset($_GET['chain']) && !empty($_GET['chain'])) {
    $selected_chain = trim($_GET['chain']);
}

try {
    // Fetch all chains for the filter dropdown
    $chains_stmt = $db->getConnection()->query("SELECT Chain_Name FROM Hotel_Chain ORDER BY Chain_Name");
    $chains = $chains_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch hotels with chain filter if provided
    $sql = "SELECT h.Hotel_Address, h.Chain_Name, h.Star_Rating, h.Num_Rooms, h.Manager_SSN, e.Full_Name AS Manager_Name, h.Area, h.Phone_Number 
            FROM Hotel h
            LEFT JOIN Employee e ON h.Manager_SSN = e.SSN";
            
    if (!empty($selected_chain)) {
        $sql .= " WHERE h.Chain_Name = :chain";
        $params = [':chain' => $selected_chain];
    } else {
        $params = [];
    }
    
    $sql .= " ORDER BY h.Chain_Name, h.Hotel_Address";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $page_error_message = "Error fetching hotels: " . $e->getMessage();
    // If you have a logging utility: log_error($page_error_message);
}

?>

<div class="container mt-4">
    <h1 class="mb-4">Manage Hotels</h1>
    
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (!empty($page_error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($page_error_message) ?></div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <a href="add_hotel.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add New Hotel</a>
        </div>
        <div class="col-md-6">
            <form class="d-flex" method="GET" action="manage_hotels.php">
                <select name="chain" class="form-select me-2">
                    <option value="">All Hotel Chains</option>
                    <?php foreach ($chains as $chain): ?>
                        <option value="<?= htmlspecialchars($chain) ?>" <?= ($selected_chain === $chain) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($chain) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if (!empty($selected_chain)): ?>
                    <a href="manage_hotels.php" class="btn btn-outline-secondary ms-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <?= empty($selected_chain) ? 'All Hotels' : 'Hotels in ' . htmlspecialchars($selected_chain) ?>
                <span class="badge bg-secondary"><?= count($hotels) ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($hotels) && empty($page_error_message)): ?>
                <p class="text-center text-muted">No hotels found. <?= empty($selected_chain) ? '' : 'Try removing the filter or ' ?><a href="add_hotel.php" class="text-primary">add a new hotel</a>.</p>
            <?php elseif (!empty($hotels)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Hotel Address</th>
                                <th>Chain</th>
                                <th>Area</th>
                                <th class="text-center">Rating</th>
                                <th class="text-end">Rooms</th>
                                <th>Manager</th>
                                <th>Phone</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotels as $hotel): ?>
                                <tr>
                                    <td><?= htmlspecialchars($hotel['Hotel_Address']) ?></td>
                                    <td><?= htmlspecialchars($hotel['Chain_Name']) ?></td>
                                    <td><?= htmlspecialchars($hotel['Area']) ?></td>
                                    <td class="text-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?= $i <= $hotel['Star_Rating'] ? 'fas' : 'far' ?> fa-star text-warning"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td class="text-end"><?= htmlspecialchars($hotel['Num_Rooms']) ?></td>
                                    <td><?= htmlspecialchars($hotel['Manager_Name'] ?? 'Not Assigned') ?></td>
                                    <td><?= htmlspecialchars($hotel['Phone_Number'] ?? 'N/A') ?></td>
                                    <td class="text-end">
                                        <a href="manage_hotel_contacts.php?hotel_address=<?= urlencode($hotel['Hotel_Address']) ?>" class="btn btn-sm btn-secondary me-1" title="Manage Contacts">
                                            <i class="fas fa-address-book"></i>
                                        </a>
                                        <a href="manage_rooms.php?hotel_address=<?= urlencode($hotel['Hotel_Address']) ?>" class="btn btn-sm btn-info me-1" title="Manage Rooms">
                                            <i class="fas fa-door-open"></i>
                                        </a>
                                        <a href="edit_hotel.php?hotel_address=<?= urlencode($hotel['Hotel_Address']) ?>" class="btn btn-sm btn-primary me-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="delete_hotel.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this hotel? This will also delete all rooms and bookings associated with this hotel.');">
                                            <input type="hidden" name="hotel_address" value="<?= htmlspecialchars($hotel['Hotel_Address']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 