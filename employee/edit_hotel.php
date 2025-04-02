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
$hotelId = $_GET['id'] ?? null;
$hotelData = null;
$hotelChains = [];
$managers = [];

if (!$hotelId || !ctype_digit($hotelId)) {
    $_SESSION['error_message'] = "Invalid or missing hotel ID for editing.";
    header("Location: manage_hotels.php");
    exit;
}

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch existing hotel data
    $stmt_get = $db->prepare("SELECT * FROM Hotel WHERE Hotel_ID = :id");
    $stmt_get->bindParam(':id', $hotelId, PDO::PARAM_INT);
    $stmt_get->execute();
    $hotelData = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$hotelData) {
        throw new Exception("Hotel not found.");
    }

    // Fetch Hotel Chains
    $stmt_chains = $db->query("SELECT Hotel_Chain_ID, Chain_Name FROM HotelChain ORDER BY Chain_Name");
    $hotelChains = $stmt_chains->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Managers
    $stmt_managers = $db->query("SELECT SSN, Full_Name FROM Employee WHERE Position = 'Manager' ORDER BY Full_Name");
    $managers = $stmt_managers->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hotel'])) {
        $formData = [
            'hotel_chain_id' => $_POST['hotel_chain_id'] ?? '',
            'hotel_name' => trim($_POST['hotel_name'] ?? ''),
            'street_address' => trim($_POST['street_address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'province_state' => trim($_POST['province_state'] ?? ''),
            'postal_code_zip' => trim($_POST['postal_code_zip'] ?? ''),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'email_address' => trim($_POST['email_address'] ?? ''),
            'star_category' => $_POST['star_category'] ?? '3',
            'manager_ssn' => $_POST['manager_ssn'] ?? ''
        ];

        // Basic Validation (similar to add_hotel)
        if (empty($formData['hotel_chain_id']) || empty($formData['hotel_name']) || empty($formData['street_address']) || 
            empty($formData['city']) || empty($formData['province_state']) || empty($formData['postal_code_zip']) || 
            empty($formData['phone_number']) || empty($formData['email_address']) || empty($formData['star_category'])) {
            $message = "All fields marked with * are required.";
        } elseif (!filter_var($formData['email_address'], FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif (!ctype_digit($formData['star_category']) || $formData['star_category'] < 1 || $formData['star_category'] > 5) {
            $message = "Star category must be between 1 and 5.";
        } elseif (!preg_match('/^[0-9\s\-\(\)]+$/', $formData['phone_number'])) { 
            $message = "Please enter a valid phone number format.";
        } elseif (!empty($formData['manager_ssn']) && !preg_match('/^[0-9]{9}$/', $formData['manager_ssn'])) {
             $message = "Invalid Manager SSN selected.";
        } else {
            // Check if hotel name is being changed and conflicts with another hotel in the same chain
            if (($formData['hotel_name'] !== $hotelData['Hotel_Name'] || $formData['hotel_chain_id'] != $hotelData['Hotel_Chain_ID'])) {
                $stmt_check = $db->prepare("SELECT Hotel_ID FROM Hotel WHERE Hotel_Name = :name AND Hotel_Chain_ID = :chain_id AND Hotel_ID != :current_id");
                $stmt_check->bindParam(':name', $formData['hotel_name']);
                $stmt_check->bindParam(':chain_id', $formData['hotel_chain_id']);
                $stmt_check->bindParam(':current_id', $hotelId, PDO::PARAM_INT);
                $stmt_check->execute();
                if ($stmt_check->fetch()) {
                    $message = "Another hotel with this name already exists within the selected chain.";
                    $messageType = 'warning';
                    goto end_update_hotel; // Skip update if name conflict
                }
            }

            // Prepare update statement
            $updateFields = [
                'Hotel_Chain_ID = :chain_id',
                'Hotel_Name = :name',
                'Street_Address = :street',
                'City = :city',
                'Province_State = :prov',
                'Postal_Code_Zip = :postal',
                'Phone_Number = :phone',
                'Email_Address = :email',
                'Star_Category = :stars',
                'Manager_SSN = :manager_ssn'
                // Number_of_Rooms is typically updated via room management, not directly here
            ];
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
                ':manager_ssn' => !empty($formData['manager_ssn']) ? $formData['manager_ssn'] : null,
                ':id' => $hotelId
            ];

            $sql_update = "UPDATE Hotel SET " . implode(', ', $updateFields) . " WHERE Hotel_ID = :id";
            $stmt_update = $db->prepare($sql_update);
            
            if ($stmt_update->execute($params)) {
                $_SESSION['success_message'] = "Hotel '" . htmlspecialchars($formData['hotel_name']) . "' updated successfully.";
                header("Location: manage_hotels.php");
                exit;
            } else {
                throw new Exception("Failed to update hotel record.");
            }
        }
        end_update_hotel: // Label for goto
    }

} catch (PDOException $e) {
    error_log("Edit Hotel DB Error: " . $e->getMessage());
    $message = "Database error occurred while loading or updating hotel data.";
} catch (Exception $e) {
    error_log("Edit Hotel Error: " . $e->getMessage());
    $message = "An unexpected error occurred: " . $e->getMessage();
    if ($e->getMessage() === "Hotel not found.") {
         $_SESSION['error_message'] = $e->getMessage();
         header("Location: manage_hotels.php");
         exit;
     }
}

// Use POST data if validation failed, otherwise use fetched data
$displayData = [
    'hotel_chain_id' => $_POST['hotel_chain_id'] ?? $hotelData['Hotel_Chain_ID'] ?? '',
    'hotel_name' => $_POST['hotel_name'] ?? $hotelData['Hotel_Name'] ?? '',
    'street_address' => $_POST['street_address'] ?? $hotelData['Street_Address'] ?? '',
    'city' => $_POST['city'] ?? $hotelData['City'] ?? '',
    'province_state' => $_POST['province_state'] ?? $hotelData['Province_State'] ?? '',
    'postal_code_zip' => $_POST['postal_code_zip'] ?? $hotelData['Postal_Code_Zip'] ?? '',
    'phone_number' => $_POST['phone_number'] ?? $hotelData['Phone_Number'] ?? '',
    'email_address' => $_POST['email_address'] ?? $hotelData['Email_Address'] ?? '',
    'star_category' => $_POST['star_category'] ?? $hotelData['Star_Category'] ?? '3',
    'manager_ssn' => $_POST['manager_ssn'] ?? $hotelData['Manager_SSN'] ?? ''
];

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_hotels.php">Manage Hotels</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Hotel</li>
        </ol>
    </nav>

    <h1 class="mb-4">Edit Hotel: <?= htmlspecialchars($hotelData['Hotel_Name'] ?? 'Unknown') ?></h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit_hotel.php?id=<?= htmlspecialchars($hotelId) ?>" class="needs-validation" novalidate>
                
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hotel_chain_id" class="form-label">Hotel Chain <span class="text-danger">*</span></label>
                        <select class="form-select" id="hotel_chain_id" name="hotel_chain_id" required>
                            <option value="">Select Hotel Chain...</option>
                             <?php foreach ($hotelChains as $chain): ?>
                                <option value="<?= htmlspecialchars($chain['Hotel_Chain_ID']) ?>" <?= ($displayData['hotel_chain_id'] == $chain['Hotel_Chain_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($chain['Chain_Name']) ?> (ID: <?= htmlspecialchars($chain['Hotel_Chain_ID']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select the hotel chain.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="hotel_name" class="form-label">Hotel Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="hotel_name" name="hotel_name" value="<?= htmlspecialchars($displayData['hotel_name']) ?>" required>
                        <div class="invalid-feedback">Please enter the hotel name.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="street_address" class="form-label">Street Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="street_address" name="street_address" value="<?= htmlspecialchars($displayData['street_address']) ?>" required>
                    <div class="invalid-feedback">Please enter the street address.</div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($displayData['city']) ?>" required>
                        <div class="invalid-feedback">Please enter the city.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="province_state" class="form-label">Province/State <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="province_state" name="province_state" value="<?= htmlspecialchars($displayData['province_state']) ?>" required>
                        <div class="invalid-feedback">Please enter the province or state.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="postal_code_zip" class="form-label">Postal Code/Zip <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="postal_code_zip" name="postal_code_zip" value="<?= htmlspecialchars($displayData['postal_code_zip']) ?>" required>
                        <div class="invalid-feedback">Please enter the postal code or zip.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?= htmlspecialchars($displayData['phone_number']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid phone number.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email_address" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email_address" name="email_address" value="<?= htmlspecialchars($displayData['email_address']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>

                 <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="star_category" class="form-label">Star Category (1-5) <span class="text-danger">*</span></label>
                        <select class="form-select" id="star_category" name="star_category" required>
                             <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= ($displayData['star_category'] == $i) ? 'selected' : '' ?>><?= $i ?> Star<?= ($i > 1) ? 's' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="invalid-feedback">Please select a star rating.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="manager_ssn" class="form-label">Manager (Optional)</label>
                        <select class="form-select" id="manager_ssn" name="manager_ssn">
                            <option value="">Assign Manager Later...</option>
                             <?php foreach ($managers as $manager): ?>
                                <option value="<?= htmlspecialchars($manager['SSN']) ?>" <?= ($displayData['manager_ssn'] == $manager['SSN']) ? 'selected' : '' ?>>
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
                    <button type="submit" name="update_hotel" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="manage_hotels.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 