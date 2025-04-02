<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied. Please log in as an employee.";
    header("Location: ../login.php");
    exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$message = '';
$messageType = 'danger';
$employeeSSN = $_GET['ssn'] ?? null;
$employeeData = null;
$hotels = [];
$errors = [];
$employee = null;
$full_name = $address = $position = '';

if (!$employeeSSN) {
    $_SESSION['error_message'] = "No employee SSN provided for editing.";
    header("Location: manage_employees.php");
    exit;
}

// Fetch positions for dropdown
$positions = ['Manager', 'Receptionist', 'Cleaner', 'Security', 'Maintenance'];

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch existing employee data
    $stmt_get = $db->prepare("SELECT SSN, Full_Name, Address, Email_Address, Position, Hotel_Address FROM Employee WHERE SSN = :ssn");
    $stmt_get->bindParam(':ssn', $employeeSSN);
    $stmt_get->execute();
    $employeeData = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$employeeData) {
        throw new Exception("Employee not found.");
    }

    // Fetch hotels for the dropdown
    $stmt = $db->query("SELECT Hotel_Address FROM Hotel ORDER BY Hotel_Address");
    $hotels = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Pre-fill form fields
    $full_name = $employeeData['Full_Name'];
    $address = $employeeData['Address'];
    $position = $employeeData['Position'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $position = $_POST['position'] ?? 'Receptionist';
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        // Basic Validation
        if (empty($full_name)) $errors[] = "Full Name is required.";
        if (empty($address)) $errors[] = "Address is required.";
        if (empty($position)) $errors[] = "Position is required.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } elseif (!in_array($position, $positions)) {
            $errors[] = "Invalid position selected.";
        } elseif (!empty($new_password) && strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        } elseif (!empty($new_password) && $new_password !== $new_password_confirm) {
            $errors[] = "New passwords do not match.";
        } else {
            // Check if email is being changed and if it already exists for another employee
            if ($email !== $employeeData['Email_Address']) {
                $stmt_check_email = $db->prepare("SELECT SSN FROM Employee WHERE Email_Address = :email AND SSN != :ssn");
                $stmt_check_email->bindParam(':email', $email);
                $stmt_check_email->bindParam(':ssn', $employeeSSN);
                $stmt_check_email->execute();
                if ($stmt_check_email->fetch()) {
                    $errors[] = "Another employee with this email address already exists.";
                    $messageType = 'warning';
                    goto end_of_post_handling_emp; // Skip update if email conflict
                }
            }

            // Check if the employee being updated is currently a manager of any hotel
            $is_current_manager = false;
            $check_manager_stmt = $db->prepare("SELECT COUNT(*) FROM Hotel WHERE Manager_SSN = :ssn");
            $check_manager_stmt->bindParam(':ssn', $employeeSSN);
            $check_manager_stmt->execute();
            if ($check_manager_stmt->fetchColumn() > 0) {
                $is_current_manager = true;
            }

            // If the employee IS a manager and their position is changed FROM Manager
            if ($is_current_manager && $position !== 'Manager') {
                // Set Manager_SSN to NULL for the hotels they manage
                $update_hotel_stmt = $db->prepare("UPDATE Hotel SET Manager_SSN = NULL WHERE Manager_SSN = :ssn");
                $update_hotel_stmt->bindParam(':ssn', $employeeSSN);
                $update_hotel_stmt->execute();
            }

            // Prepare update statement
            $updateFields = [
                'Full_Name = :name',
                'Address = :addr',
                'Email_Address = :email',
                'Position = :position'
            ];
            $params = [
                ':name' => $full_name,
                ':addr' => $address,
                ':email' => $email,
                ':position' => $position,
                ':ssn' => $employeeSSN
            ];

            // Add password update if provided
            if (!empty($new_password)) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updateFields[] = 'Password_Hash = :hash';
                $params[':hash'] = $new_password_hash;
            }

            $sql_update = "UPDATE Employee SET " . implode(', ', $updateFields) . " WHERE SSN = :ssn";
            $stmt_update = $db->prepare($sql_update);
            
            if ($stmt_update->execute($params)) {
                $_SESSION['success_message'] = "Employee '" . htmlspecialchars($full_name) . "' updated successfully.";
                header("Location: manage_employees.php");
                exit;
            } else {
                throw new Exception("Failed to update employee record.");
            }
        }
        end_of_post_handling_emp: // Label for goto
    }

} catch (PDOException $e) {
    error_log("Edit Employee DB Error: " . $e->getMessage());
    $message = "Database error occurred.";
} catch (Exception $e) {
    error_log("Edit Employee Error: " . $e->getMessage());
    $message = "An unexpected error occurred: " . $e->getMessage();
     if ($e->getMessage() === "Employee not found.") {
         $_SESSION['error_message'] = $e->getMessage();
         header("Location: manage_employees.php");
         exit;
     }
}

// Use POST data if validation failed, otherwise use fetched data
$formData = [
    'full_name' => $_POST['full_name'] ?? $employeeData['Full_Name'] ?? '',
    'email' => $_POST['email'] ?? $employeeData['Email_Address'] ?? '',
    'address' => $_POST['address'] ?? $employeeData['Address'] ?? '',
    'position' => $_POST['position'] ?? $employeeData['Position'] ?? 'Receptionist'
];

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_employees.php">Manage Employees</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Employee</li>
        </ol>
    </nav>

    <h1 class="mb-4">Edit Employee: <?= htmlspecialchars($employeeData['Full_Name'] ?? 'Unknown') ?> (SSN: <?= htmlspecialchars($employeeSSN) ?>)</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit_employee.php?ssn=<?= htmlspecialchars($employeeSSN) ?>" class="needs-validation" novalidate>
                <!-- SSN is read-only -->
                <div class="mb-3">
                    <label for="ssn" class="form-label">SSN</label>
                    <input type="text" class="form-control" id="ssn" name="ssn_display" value="<?= htmlspecialchars($employeeSSN) ?>" readonly disabled>
                </div>

                 <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                    <div class="invalid-feedback">Please enter the employee's full name.</div>
                </div>
                
                 <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" required>
                    <div class="invalid-feedback">Please enter the employee's address.</div>
                </div>
                
                <div class="mb-3">
                    <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="position" name="position" value="<?= htmlspecialchars($formData['position']) ?>" required maxlength="50" placeholder="e.g., Receptionist, Manager, Cleaner">
                    <div class="invalid-feedback">Position is required.</div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Change Password (Optional)</h5>
                <div class="alert alert-info small">Only fill these fields if you want to change the employee's password.</div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                        <div class="form-text">Leave blank to keep current password. (Min 6 chars).</div>
                    </div>
                    <div class="col-md-6">
                        <label for="new_password_confirm" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm">
                        <div class="invalid-feedback">Passwords must match if changing.</div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="update_employee" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="manage_employees.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 