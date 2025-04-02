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

// Verify hotel_address is provided
if (!isset($_GET['hotel_address']) || empty($_GET['hotel_address'])) {
    $_SESSION['error_message'] = "No hotel specified.";
    header("Location: manage_hotels.php");
    exit;
}

$hotel_address = trim($_GET['hotel_address']);
$db = getDatabase();
$errors = [];
$hotel_data = null;

// Retrieve hotel info
try {
    $stmt = $db->getConnection()->prepare("SELECT * FROM Hotel WHERE Hotel_Address = ?");
    $stmt->execute([$hotel_address]);
    $hotel_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hotel_data) {
        $_SESSION['error_message'] = "Hotel not found.";
        header("Location: manage_hotels.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error retrieving hotel data: " . $e->getMessage();
    header("Location: manage_hotels.php");
    exit;
}

// Get existing rooms for this hotel
$rooms = [];
try {
    $stmt = $db->getConnection()->prepare("
        SELECT r.*, 
               CASE 
                   WHEN rb.Room_Number IS NOT NULL THEN 'Booked' 
                   WHEN re.Room_Number IS NOT NULL THEN 'Rented' 
                   ELSE 'Available' 
               END AS Status
        FROM Room r
        LEFT JOIN (
            SELECT DISTINCT Room_Number, Hotel_Address FROM Room_Booking 
            WHERE Check_Out_Date >= CURDATE() AND Check_In_Date <= CURDATE()
        ) rb ON r.Room_Number = rb.Room_Number AND r.Hotel_Address = rb.Hotel_Address
        LEFT JOIN (
            SELECT DISTINCT Room_Number, Hotel_Address FROM Renting 
            WHERE Check_Out_Date >= CURDATE()
        ) re ON r.Room_Number = re.Room_Number AND r.Hotel_Address = re.Hotel_Address
        WHERE r.Hotel_Address = ?
        ORDER BY r.Room_Number
    ");
    $stmt->execute([$hotel_address]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Error retrieving rooms: " . $e->getMessage();
}

// Process room addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    // Validate room number
    if (empty($_POST['room_number']) || !is_numeric($_POST['room_number'])) {
        $errors[] = "Room number is required and must be numeric";
    } else {
        $room_number = (int)$_POST['room_number'];
        
        // Check if room already exists
        try {
            $check_stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM Room WHERE Room_Number = ? AND Hotel_Address = ?");
            $check_stmt->execute([$room_number, $hotel_address]);
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = "Room {$room_number} already exists in this hotel";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking room existence: " . $e->getMessage();
        }
    }
    
    // Validate price
    if (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
        $errors[] = "Price must be a positive number";
    } else {
        $price = (float)$_POST['price'];
    }
    
    // Validate capacity
    if (empty($_POST['capacity']) || !is_numeric($_POST['capacity']) || $_POST['capacity'] <= 0) {
        $errors[] = "Capacity must be a positive number";
    } else {
        $capacity = (int)$_POST['capacity'];
    }
    
    // Validate view (optional)
    $view = !empty($_POST['view']) ? trim($_POST['view']) : null;
    
    // Validate amenities (optional)
    $amenities = !empty($_POST['amenities']) ? trim($_POST['amenities']) : null;
    
    // Validate extendable
    $extendable = isset($_POST['extendable']) ? 1 : 0;
    
    // Validate damage status
    $damage = isset($_POST['damages']) ? trim($_POST['damages']) : null;
    
    // If no errors, insert the room
    if (empty($errors)) {
        try {
            $stmt = $db->getConnection()->prepare("
                INSERT INTO Room (Room_Number, Hotel_Address, Price, Capacity, View, Amenities, Extendable, Damages) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $room_number,
                $hotel_address,
                $price,
                $capacity,
                $view,
                $amenities,
                $extendable,
                $damage
            ]);
            
            if ($result) {
                $_SESSION['success_message'] = "Room {$room_number} added successfully";
                // Redirect to reload the page
                header("Location: manage_rooms.php?hotel_address=" . urlencode($hotel_address));
                exit;
            } else {
                $errors[] = "Failed to add room";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Process room deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_room') {
    if (!isset($_POST['room_number']) || !is_numeric($_POST['room_number'])) {
        $errors[] = "Invalid room number specified";
    } else {
        $room_number = (int)$_POST['room_number'];
        $conn = $db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // First, delete bookings for this room
            $delete_bookings_stmt = $conn->prepare("
                DELETE FROM Room_Booking 
                WHERE Room_Number = ? AND Hotel_Address = ?
            ");
            $delete_bookings_stmt->execute([$room_number, $hotel_address]);
            
            // Delete rentings for this room
            $delete_rentings_stmt = $conn->prepare("
                DELETE FROM Renting 
                WHERE Room_Number = ? AND Hotel_Address = ?
            ");
            $delete_rentings_stmt->execute([$room_number, $hotel_address]);
            
            // Finally, delete the room
            $delete_room_stmt = $conn->prepare("
                DELETE FROM Room 
                WHERE Room_Number = ? AND Hotel_Address = ?
            ");
            $delete_room_stmt->execute([$room_number, $hotel_address]);
            
            // Commit the transaction
            $conn->commit();
            
            $_SESSION['success_message'] = "Room {$room_number} and all related records have been deleted successfully";
            // Redirect to reload the page
            header("Location: manage_rooms.php?hotel_address=" . urlencode($hotel_address));
            exit;
            
        } catch (Exception $e) {
            // Rollback the transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $errors[] = "Error deleting room: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="manage_hotels.php">Hotels</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rooms at <?= htmlspecialchars($hotel_data['Hotel_Address']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Hotel Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Hotel Address:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($hotel_data['Hotel_Address']) ?></dd>
                                
                                <dt class="col-sm-4">Chain Name:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($hotel_data['Chain_Name']) ?></dd>
                                
                                <dt class="col-sm-4">Star Rating:</dt>
                                <dd class="col-sm-8">
                                    <?php for ($i = 1; $i <= $hotel_data['Star_Rating']; $i++): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Total Rooms:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($hotel_data['Num_Rooms']) ?></dd>
                                
                                <dt class="col-sm-4">Area:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($hotel_data['Area']) ?></dd>
                                
                                <dt class="col-sm-4">Phone:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($hotel_data['Phone_Number'] ?? 'N/A') ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Display messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Add New Room</h4>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseAddRoom">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>
                <div class="collapse" id="collapseAddRoom">
                    <div class="card-body">
                        <form action="manage_rooms.php?hotel_address=<?= urlencode($hotel_address) ?>" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="add_room">
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="room_number" class="form-label">Room Number</label>
                                    <input type="number" class="form-control" id="room_number" name="room_number" required min="1">
                                    <div class="invalid-feedback">Please enter a valid room number</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="price" class="form-label">Price per Night</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" required>
                                        <div class="invalid-feedback">Please enter a valid price</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="capacity" class="form-label">Capacity</label>
                                    <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                                    <div class="invalid-feedback">Please enter room capacity</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="view" class="form-label">View</label>
                                    <input type="text" class="form-control" id="view" name="view" placeholder="e.g., Ocean, Mountain, City">
                                    <div class="form-text">Optional</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="amenities" class="form-label">Amenities</label>
                                    <input type="text" class="form-control" id="amenities" name="amenities" placeholder="e.g., TV, Mini-bar, Jacuzzi">
                                    <div class="form-text">Optional</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="extendable" name="extendable">
                                        <label class="form-check-label" for="extendable">Extendable (can add extra beds)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="damages" class="form-label">Damages</label>
                                    <textarea class="form-control" id="damages" name="damages" rows="2" placeholder="Document any existing damages"></textarea>
                                    <div class="form-text">Optional</div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Add Room</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Rooms (<?= count($rooms) ?>)</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($rooms)): ?>
                        <div class="alert alert-info">No rooms have been added to this hotel yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Room #</th>
                                        <th>Price</th>
                                        <th>Capacity</th>
                                        <th>View</th>
                                        <th>Amenities</th>
                                        <th>Extendable</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rooms as $room): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($room['Room_Number']) ?></td>
                                            <td>$<?= number_format($room['Price'], 2) ?></td>
                                            <td><?= htmlspecialchars($room['Capacity']) ?></td>
                                            <td><?= htmlspecialchars($room['View'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($room['Amenities'] ?? 'N/A') ?></td>
                                            <td><?= $room['Extendable'] ? 'Yes' : 'No' ?></td>
                                            <td>
                                                <span class="badge <?= 
                                                    $room['Status'] === 'Available' ? 'bg-success' : 
                                                    ($room['Status'] === 'Booked' ? 'bg-warning' : 'bg-danger') 
                                                ?>">
                                                    <?= htmlspecialchars($room['Status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editRoomModal<?= $room['Room_Number'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteRoomModal<?= $room['Room_Number'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                
                                                <!-- Edit Room Modal -->
                                                <div class="modal fade" id="editRoomModal<?= $room['Room_Number'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title">Edit Room <?= htmlspecialchars($room['Room_Number']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form action="edit_room.php" method="POST" class="needs-validation" novalidate>
                                                                    <input type="hidden" name="room_number" value="<?= htmlspecialchars($room['Room_Number']) ?>">
                                                                    <input type="hidden" name="hotel_address" value="<?= htmlspecialchars($hotel_address) ?>">
                                                                    
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <label for="edit_price<?= $room['Room_Number'] ?>" class="form-label">Price per Night</label>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">$</span>
                                                                                <input type="number" class="form-control" id="edit_price<?= $room['Room_Number'] ?>" name="price" step="0.01" min="0.01" value="<?= htmlspecialchars($room['Price']) ?>" required>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="edit_capacity<?= $room['Room_Number'] ?>" class="form-label">Capacity</label>
                                                                            <input type="number" class="form-control" id="edit_capacity<?= $room['Room_Number'] ?>" name="capacity" min="1" value="<?= htmlspecialchars($room['Capacity']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <label for="edit_view<?= $room['Room_Number'] ?>" class="form-label">View</label>
                                                                            <input type="text" class="form-control" id="edit_view<?= $room['Room_Number'] ?>" name="view" value="<?= htmlspecialchars($room['View'] ?? '') ?>">
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="edit_amenities<?= $room['Room_Number'] ?>" class="form-label">Amenities</label>
                                                                            <input type="text" class="form-control" id="edit_amenities<?= $room['Room_Number'] ?>" name="amenities" value="<?= htmlspecialchars($room['Amenities'] ?? '') ?>">
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <div class="form-check mt-4">
                                                                                <input class="form-check-input" type="checkbox" id="edit_extendable<?= $room['Room_Number'] ?>" name="extendable" <?= $room['Extendable'] ? 'checked' : '' ?>>
                                                                                <label class="form-check-label" for="edit_extendable<?= $room['Room_Number'] ?>">Extendable (can add extra beds)</label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="edit_damages<?= $room['Room_Number'] ?>" class="form-label">Damages</label>
                                                                            <textarea class="form-control" id="edit_damages<?= $room['Room_Number'] ?>" name="damages" rows="2"><?= htmlspecialchars($room['Damages'] ?? '') ?></textarea>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Delete Room Modal -->
                                                <div class="modal fade" id="deleteRoomModal<?= $room['Room_Number'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Delete Room <?= htmlspecialchars($room['Room_Number']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete Room <?= htmlspecialchars($room['Room_Number']) ?>?</p>
                                                                <p><strong>This will also delete all bookings and rentings associated with this room.</strong></p>
                                                                <?php if ($room['Status'] !== 'Available'): ?>
                                                                    <div class="alert alert-warning">
                                                                        <i class="fas fa-exclamation-triangle"></i> Warning: This room is currently <?= strtolower($room['Status']) ?>. Deleting it will affect existing reservations.
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <form action="manage_rooms.php?hotel_address=<?= urlencode($hotel_address) ?>" method="POST">
                                                                    <input type="hidden" name="action" value="delete_room">
                                                                    <input type="hidden" name="room_number" value="<?= htmlspecialchars($room['Room_Number']) ?>">
                                                                    <button type="submit" class="btn btn-danger">Delete Room</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
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
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 