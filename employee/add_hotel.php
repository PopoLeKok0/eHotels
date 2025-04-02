<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; header("Location: ../login.php"); exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$message = '';
$messageType = 'danger';
$hotelChains = [];
$managers = []; // For dropdown
$formData = [
    'hotel_chain_id' => '',
    'hotel_name' => '',
    'street_address' => '',
    'city' => '',
    'province_state' => '',
    'postal_code_zip' => '',
    'phone_number' => '',
    'email_address' => '',
    'star_category' => '3', // Default to 3 stars
    'manager_ssn' => ''
];

// Fetch Hotel Chains and potential Managers (Employees with 'Manager' position)
try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch Chains
    $stmt_chains = $db->query("SELECT Hotel_Chain_ID, Chain_Name FROM HotelChain ORDER BY Chain_Name");
    $hotelChains = $stmt_chains->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Managers
    $stmt_managers = $db->query("SELECT SSN, Full_Name FROM Employee WHERE Position = 'Manager' ORDER BY Full_Name");
    $managers = $stmt_managers->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Error fetching initial data: " . $e->getMessage();
    error_log("Add Hotel Init Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hotel'])) {
    // Sanitize and retrieve form data
    $formData['hotel_chain_id'] = $_POST['hotel_chain_id'] ?? '';
    $formData['hotel_name'] = trim($_POST['hotel_name'] ?? '');
    $formData['street_address'] = trim($_POST['street_address'] ?? '');
    $formData['city'] = trim($_POST['city'] ?? '');
    $formData['province_state'] = trim($_POST['province_state'] ?? '');
    $formData['postal_code_zip'] = trim($_POST['postal_code_zip'] ?? '');
    $formData['phone_number'] = trim($_POST['phone_number'] ?? '');
    $formData['email_address'] = trim($_POST['email_address'] ?? '');
    $formData['star_category'] = $_POST['star_category'] ?? '3';
    $formData['manager_ssn'] = $_POST['manager_ssn'] ?? '';

    // Basic Validation
    if (empty($formData['hotel_chain_id']) || empty($formData['hotel_name']) || empty($formData['street_address']) || 
        empty($formData['city']) || empty($formData['province_state']) || empty($formData['postal_code_zip']) || 
        empty($formData['phone_number']) || empty($formData['email_address']) || empty($formData['star_category'])) {
        $message = "All fields marked with * are required.";
    } elseif (!filter_var($formData['email_address'], FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address for the hotel.";
    } elseif (!ctype_digit($formData['star_category']) || $formData['star_category'] < 1 || $formData['star_category'] > 5) {
        $message = "Star category must be between 1 and 5.";
    // Basic phone number format check (allows digits, spaces, dashes, parentheses)
    } elseif (!preg_match('/^[0-9\s\-\(\)]+$/', $formData['phone_number'])) { 
        $message = "Please enter a valid phone number format.";
    } elseif (!empty($formData['manager_ssn']) && !preg_match('/^[0-9]{9}$/', $formData['manager_ssn'])) {
         $message = "Invalid Manager SSN selected."; // Should not happen with dropdown, but good practice
    } else {
        try {
            if (!isset($db)) {
                 $dbInstance = getDatabase();
                 $db = $dbInstance->getConnection();
            }
            $db->beginTransaction();

            // Check if hotel with same name and chain already exists (optional, adjust as needed)
            $stmt_check = $db->prepare("SELECT Hotel_ID FROM Hotel WHERE Hotel_Name = :name AND Hotel_Chain_ID = :chain_id");
            $stmt_check->bindParam(':name', $formData['hotel_name']);
            $stmt_check->bindParam(':chain_id', $formData['hotel_chain_id']);
            $stmt_check->execute();
            if ($stmt_check->fetch()) {
                 $message = "A hotel with this name already exists within the selected chain.";
                 $messageType = 'warning';
                 $db->rollBack(); // Rollback before exiting
                 goto end_add_hotel; // Use goto to skip insertion
            }

            // Insert new hotel
            $stmt_insert = $db->prepare("
                INSERT INTO Hotel (Hotel_Chain_ID, Hotel_Name, Street_Address, City, Province_State, Postal_Code_Zip, 
                                Phone_Number, Email_Address, Star_Category, Number_of_Rooms, Manager_SSN)
                VALUES (:chain_id, :name, :street, :city, :prov, :postal, :phone, :email, :stars, 0, :manager_ssn)
            ");
            
            $params = [
                ':chain_id' => $formData['hotel_chain_id'],
                ':name' => $formData['hotel_name'],
                ':street' => $formData['street_address'],
                ':city' => $formData['city'],
                ':prov' => $formData['province_state'],
                ':postal' => $formData['postal_code_zip'],
                ':phone' => $formData['phone_number'],
                ':email' => $formData['email_address'],
                ':stars' => $formData['star_category'],
                // Set manager SSN, handle empty string as NULL if column allows
                ':manager_ssn' => !empty($formData['manager_ssn']) ? $formData['manager_ssn'] : null 
            ];

            if ($stmt_insert->execute($params)) {
                $db->commit();
                $_SESSION['success_message'] = "Hotel '" . htmlspecialchars($formData['hotel_name']) . "' added successfully.";
                header("Location: manage_hotels.php");
                exit;
            } else {
                 throw new Exception("Failed to create hotel record in database.");
            }

        } catch (PDOException $e) {
             if ($db->inTransaction()) { $db->rollBack(); }
            error_log("Add Hotel DB Error: " . $e->getMessage());
            // Check for specific constraint violations if needed
            if ($e->getCode() == '23000') { // Integrity constraint violation
                 $message = "Failed to add hotel. Possible reasons: Invalid Hotel Chain ID, invalid Manager SSN, or duplicate data violation.";
            } else {
                $message = "Database error occurred while adding hotel.";
            }
        } catch (Exception $e) {
             if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
            error_log("Add Hotel Error: " . $e->getMessage());
            $message = "An unexpected error occurred: " . $e->getMessage();
        }
    }
    end_add_hotel: // Label for goto
}

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_hotels.php">Manage Hotels</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add New Hotel</li>
        </ol>
    </nav>

    <h1 class="mb-4">Add New Hotel</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="add_hotel.php" class="needs-validation" novalidate>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hotel_chain_id" class="form-label">Hotel Chain <span class="text-danger">*</span></label>
                        <select class="form-select" id="hotel_chain_id" name="hotel_chain_id" required>
                            <option value="">Select Hotel Chain...</option>
                            <?php foreach ($hotelChains as $chain): ?>
                                <option value="<?= htmlspecialchars($chain['Hotel_Chain_ID']) ?>" <?= ($formData['hotel_chain_id'] == $chain['Hotel_Chain_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($chain['Chain_Name']) ?> (ID: <?= htmlspecialchars($chain['Hotel_Chain_ID']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select the hotel chain.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="hotel_name" class="form-label">Hotel Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="<?= htmlspecialchars($formData['hotel_name']) ?>" required>
                        <div class="invalid-feedback">Please enter the hotel name.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="street_address" class="form-label">Street Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="street_address" name="street_address" value="<?= htmlspecialchars($formData['street_address']) ?>" required>
                    <div class="invalid-feedback">Please enter the street address.</div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($formData['city']) ?>" required>
                        <div class="invalid-feedback">Please enter the city.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="province_state" class="form-label">Province/State <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="province_state" name="province_state" value="<?= htmlspecialchars($formData['province_state']) ?>" required>
                        <div class="invalid-feedback">Please enter the province or state.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="postal_code_zip" class="form-label">Postal Code/Zip <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="postal_code_zip" name="postal_code_zip" value="<?= htmlspecialchars($formData['postal_code_zip']) ?>" required>
                        <div class="invalid-feedback">Please enter the postal code or zip.</div>
                    </div>
                </div>

                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($formData['phone_number']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid phone number.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email_address" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email_address" name="email_address" value="<?= htmlspecialchars($formData['email_address']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>

                 <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="star_category" class="form-label">Star Category (1-5) <span class="text-danger">*</span></label>
                        <select class="form-select" id="star_category" name="star_category" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= ($formData['star_category'] == $i) ? 'selected' : '' ?>><?= $i ?> Star<?= ($i > 1) ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="invalid-feedback">Please select a star rating.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="manager_ssn" class="form-label">Manager (Optional)</label>
                        <select class="form-select" id="manager_ssn" name="manager_ssn">
                            <option value="">Assign Manager Later...</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?= htmlspecialchars($manager['SSN']) ?>" <?= ($formData['manager_ssn'] == $manager['SSN']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($manager['Full_Name']) ?> (SSN: ...<?= substr(htmlspecialchars($manager['SSN']), -4) ?>)
                                </option>
                            <?php endforeach; ?>
                             <?php if (empty($managers)): ?>
                                <option value="" disabled>No employees with 'Manager' position found.</option>
                            <?php endif; ?>
                        </select>
                         <div class="form-text">Select an employee with the 'Manager' position to manage this hotel.</div>
                    </div>
                 </div>
                
                <div class="mt-4">
                    <button type="submit" name="add_hotel" class="btn btn-success btn-lg">
                        <i class="fas fa-plus-circle me-2"></i>Add Hotel
                    </button>
                    <a href="manage_hotels.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 