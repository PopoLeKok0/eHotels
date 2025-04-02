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
$errors = [];

// Initialize form fields
$hotel_address = '';
$original_hotel_address = '';
$chain_name = '';
$star_rating = 3;
$num_rooms = '';
$manager_ssn = '';
$area = '';
$phone_number = '';

// Check if hotel_address is provided in GET or POST
if (isset($_GET['hotel_address'])) {
    $original_hotel_address = trim($_GET['hotel_address']);
} elseif (isset($_POST['original_hotel_address'])) {
    $original_hotel_address = trim($_POST['original_hotel_address']);
} else {
    $_SESSION['error_message'] = "No hotel specified for editing.";
    header("Location: manage_hotels.php");
    exit;
}

// Get all hotel chains for the dropdown
$chains = [];
try {
    $chains_stmt = $db->getConnection()->query("SELECT Chain_Name FROM Hotel_Chain ORDER BY Chain_Name");
    $chains = $chains_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $errors[] = "Error loading hotel chains: " . $e->getMessage();
}

// Get employees with 'Manager' position for the dropdown
$managers = [];
try {
    $managers_stmt = $db->getConnection()->prepare("SELECT SSN, Full_Name FROM Employee WHERE Position = 'Manager' ORDER BY Full_Name");
    $managers_stmt->execute();
    $managers = $managers_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Error loading managers: " . $e->getMessage();
}

// Fetch existing hotel data
try {
    $stmt = $db->getConnection()->prepare("SELECT * FROM Hotel WHERE Hotel_Address = ?");
    $stmt->execute([$original_hotel_address]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hotel) {
        $_SESSION['error_message'] = "Hotel not found.";
        header("Location: manage_hotels.php");
        exit;
    }
    
    // Pre-fill form with current values
    $hotel_address = $hotel['Hotel_Address'];
    $chain_name = $hotel['Chain_Name'];
    $star_rating = $hotel['Star_Rating'];
    $num_rooms = $hotel['Num_Rooms'];
    $manager_ssn = $hotel['Manager_SSN'];
    $area = $hotel['Area'];
    $phone_number = $hotel['Phone_Number'];
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching hotel data: " . $e->getMessage();
    header("Location: manage_hotels.php");
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate hotel address
    if (empty($_POST['hotel_address'])) {
        $errors[] = "Hotel address is required";
    } else {
        $hotel_address = trim($_POST['hotel_address']);
        // Check if address already exists (if changing address)
        if ($hotel_address !== $original_hotel_address) {
            try {
                $check_stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM Hotel WHERE Hotel_Address = ?");
                $check_stmt->execute([$hotel_address]);
                if ($check_stmt->fetchColumn() > 0) {
                    $errors[] = "A hotel at this address already exists";
                }
            } catch (Exception $e) {
                $errors[] = "Error checking hotel address: " . $e->getMessage();
            }
        }
    }

    // Validate chain name
    if (empty($_POST['chain_name'])) {
        $errors[] = "Hotel chain is required";
    } else {
        $chain_name = trim($_POST['chain_name']);
    }

    // Validate star rating
    if (!isset($_POST['star_rating']) || !is_numeric($_POST['star_rating']) || $_POST['star_rating'] < 1 || $_POST['star_rating'] > 5) {
        $errors[] = "Star rating must be between 1 and 5";
    } else {
        $star_rating = (int)$_POST['star_rating'];
    }

    // Validate number of rooms
    if (empty($_POST['num_rooms']) || !is_numeric($_POST['num_rooms']) || $_POST['num_rooms'] < 1) {
        $errors[] = "Number of rooms must be a positive number";
    } else {
        $num_rooms = (int)$_POST['num_rooms'];
    }

    // Validate manager SSN
    if (!empty($_POST['manager_ssn'])) {
        $manager_ssn = trim($_POST['manager_ssn']);
        
        // Check if manager exists and has 'Manager' position
        try {
            $check_stmt = $db->getConnection()->prepare("SELECT COUNT(*), Position FROM Employee WHERE SSN = ? GROUP BY Position");
            $check_stmt->execute([$manager_ssn]);
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                $errors[] = "Selected manager does not exist";
            } elseif ($result['Position'] !== 'Manager') {
                $errors[] = "Selected employee must have the 'Manager' position to be assigned as a hotel manager";
            }
        } catch (Exception $e) {
            $errors[] = "Error validating manager: " . $e->getMessage();
        }
    } else {
        $manager_ssn = null; // Allow setting to NULL if no manager selected
    }

    // Validate area
    if (empty($_POST['area'])) {
        $errors[] = "Area is required";
    } else {
        $area = trim($_POST['area']);
    }

    // Validate phone number (optional)
    if (!empty($_POST['phone_number'])) {
        $phone_number = trim($_POST['phone_number']);
        if (!preg_match('/^\+?[0-9()\-\s]{7,20}$/', $phone_number)) {
            $errors[] = "Invalid phone number format";
        }
    } else {
        $phone_number = null;
    }

    // If no errors, update the hotel
    if (empty($errors)) {
        try {
            $db_connection = $db->getConnection();
            $db_connection->beginTransaction();

            // If hotel address is changing, we need to update all related records
            if ($hotel_address !== $original_hotel_address) {
                // Update Room records
                $update_rooms_stmt = $db_connection->prepare(
                    "UPDATE Room SET Hotel_Address = ? WHERE Hotel_Address = ?"
                );
                $update_rooms_stmt->execute([$hotel_address, $original_hotel_address]);
                
                // Update Room_Booking records
                $update_bookings_stmt = $db_connection->prepare(
                    "UPDATE Room_Booking SET Hotel_Address = ? WHERE Hotel_Address = ?"
                );
                $update_bookings_stmt->execute([$hotel_address, $original_hotel_address]);
                
                // Update Renting records
                $update_rentings_stmt = $db_connection->prepare(
                    "UPDATE Renting SET Hotel_Address = ? WHERE Hotel_Address = ?"
                );
                $update_rentings_stmt->execute([$hotel_address, $original_hotel_address]);
            }

            // Update the hotel record
            $update_hotel_stmt = $db_connection->prepare(
                "UPDATE Hotel SET 
                 Hotel_Address = ?,
                 Chain_Name = ?, 
                 Star_Rating = ?, 
                 Num_Rooms = ?, 
                 Manager_SSN = ?, 
                 Area = ?, 
                 Phone_Number = ?
                 WHERE Hotel_Address = ?"
            );
            $params = [
                $hotel_address,
                $chain_name,
                $star_rating,
                $num_rooms,
                $manager_ssn, // Can be null
                $area,
                $phone_number, // Can be null
                $original_hotel_address
            ];
            $result = $update_hotel_stmt->execute($params);

            if ($result) {
                // Commit the transaction
                $db_connection->commit();
                
                $_SESSION['success_message'] = "Hotel updated successfully";
                header("Location: manage_hotels.php");
                exit;
            } else {
                $db_connection->rollBack();
                $errors[] = "Failed to update hotel";
            }
        } catch (Exception $e) {
            if ($db_connection && $db_connection->inTransaction()) {
                $db_connection->rollBack();
            }
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit Hotel</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="edit_hotel.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="original_hotel_address" value="<?= htmlspecialchars($original_hotel_address) ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="hotel_address" class="form-label">Hotel Address</label>
                                <input type="text" class="form-control" id="hotel_address" name="hotel_address" 
                                       value="<?= htmlspecialchars($hotel_address) ?>" required>
                                <div class="invalid-feedback">Please enter the hotel address</div>
                            </div>
                            <div class="col-md-6">
                                <label for="chain_name" class="form-label">Hotel Chain</label>
                                <select class="form-select" id="chain_name" name="chain_name" required>
                                    <option value="" disabled>Select a hotel chain</option>
                                    <?php foreach ($chains as $chain): ?>
                                        <option value="<?= htmlspecialchars($chain) ?>" <?= ($chain_name === $chain) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($chain) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a hotel chain</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="star_rating" class="form-label">Star Rating</label>
                                <select class="form-select" id="star_rating" name="star_rating" required>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?= $i ?>" <?= (intval($star_rating) === $i) ? 'selected' : '' ?>>
                                            <?= $i ?> Star<?= ($i > 1) ? 's' : '' ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div class="invalid-feedback">Please select a star rating</div>
                            </div>
                            <div class="col-md-4">
                                <label for="num_rooms" class="form-label">Number of Rooms</label>
                                <input type="number" class="form-control" id="num_rooms" name="num_rooms" 
                                       value="<?= htmlspecialchars($num_rooms) ?>" min="1" required>
                                <div class="invalid-feedback">Please enter the number of rooms</div>
                            </div>
                            <div class="col-md-4">
                                <label for="manager_ssn" class="form-label">Manager</label>
                                <select class="form-select" id="manager_ssn" name="manager_ssn">
                                    <option value="">-- None (Assign Later) --</option>
                                    <?php foreach ($managers as $manager): ?>
                                        <option value="<?= htmlspecialchars($manager['SSN']) ?>" <?= ($manager_ssn === $manager['SSN']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($manager['Full_Name']) ?> (<?= htmlspecialchars($manager['SSN']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Only employees with 'Manager' position are available</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="area" class="form-label">Area/City</label>
                                <input type="text" class="form-control" id="area" name="area" 
                                       value="<?= htmlspecialchars($area) ?>" required>
                                <div class="invalid-feedback">Please enter the area or city</div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                       value="<?= htmlspecialchars($phone_number ?? '') ?>" 
                                       placeholder="e.g., +1 (555) 123-4567">
                                <div class="form-text">Optional</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="manage_hotels.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Hotel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 