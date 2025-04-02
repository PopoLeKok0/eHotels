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
$customerId = $_GET['id'] ?? null;
$customerData = null;

if (!$customerId) {
    $_SESSION['error_message'] = "No customer ID provided for editing.";
    header("Location: manage_customers.php");
    exit;
}

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch existing customer data
    $stmt_get = $db->prepare("SELECT Customer_ID, Full_Name, Address, Email_Address, ID_Type FROM Customer WHERE Customer_ID = :id");
    $stmt_get->bindParam(':id', $customerId);
    $stmt_get->execute();
    $customerData = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$customerData) {
        throw new Exception("Customer not found.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
        $newName = trim($_POST['full_name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newAddress = trim($_POST['address'] ?? '');
        $newIdType = $_POST['id_type'] ?? 'Passport';
        $newPassword = $_POST['password'] ?? ''; // Optional password change
        $newPasswordConfirm = $_POST['password_confirm'] ?? '';

        // Basic Validation
        if (empty($newName) || empty($newEmail) || empty($newAddress)) {
            $message = "Full Name, Email, and Address are required.";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
        } elseif (!empty($newPassword) && strlen($newPassword) < 6) {
            $message = "New password must be at least 6 characters long.";
        } elseif (!empty($newPassword) && $newPassword !== $newPasswordConfirm) {
            $message = "New passwords do not match.";
        } else {
            // Check if email is being changed and if it already exists for another customer
            if ($newEmail !== $customerData['Email_Address']) {
                $stmt_check_email = $db->prepare("SELECT Customer_ID FROM Customer WHERE Email_Address = :email AND Customer_ID != :id");
                $stmt_check_email->bindParam(':email', $newEmail);
                $stmt_check_email->bindParam(':id', $customerId);
                $stmt_check_email->execute();
                if ($stmt_check_email->fetch()) {
                    $message = "Another customer with this email address already exists.";
                    $messageType = 'warning';
                    goto end_of_post_handling; // Skip update if email conflict
                }
            }

            // Prepare update statement
            $updateFields = [
                'Full_Name = :name',
                'Address = :addr',
                'Email_Address = :email',
                'ID_Type = :id_type'
            ];
            $params = [
                ':name' => $newName,
                ':addr' => $newAddress,
                ':email' => $newEmail,
                ':id_type' => $newIdType,
                ':id' => $customerId
            ];

            // Add password update if provided
            if (!empty($newPassword)) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateFields[] = 'Password_Hash = :hash';
                $params[':hash'] = $newPasswordHash;
            }

            $sql_update = "UPDATE Customer SET " . implode(', ', $updateFields) . " WHERE Customer_ID = :id";
            $stmt_update = $db->prepare($sql_update);
            
            if ($stmt_update->execute($params)) {
                $_SESSION['success_message'] = "Customer '" . htmlspecialchars($newName) . "' updated successfully.";
                header("Location: manage_customers.php");
                exit;
            } else {
                throw new Exception("Failed to update customer record.");
            }
        }
        end_of_post_handling: // Label for goto
    }

} catch (PDOException $e) {
    error_log("Edit Customer DB Error: " . $e->getMessage());
    $message = "Database error occurred.";
    // On critical error, maybe redirect
    // header("Location: manage_customers.php"); exit;
} catch (Exception $e) {
    error_log("Edit Customer Error: " . $e->getMessage());
    $message = "An unexpected error occurred: " . $e->getMessage();
    // On critical error like customer not found, redirect
     if ($e->getMessage() === "Customer not found.") {
         $_SESSION['error_message'] = $e->getMessage();
         header("Location: manage_customers.php");
         exit;
     }
}

// Use POST data if validation failed, otherwise use fetched data
$formData = [
    'full_name' => $_POST['full_name'] ?? $customerData['Full_Name'] ?? '',
    'email' => $_POST['email'] ?? $customerData['Email_Address'] ?? '',
    'address' => $_POST['address'] ?? $customerData['Address'] ?? '',
    'id_type' => $_POST['id_type'] ?? $customerData['ID_Type'] ?? 'Passport'
];

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_customers.php">Manage Customers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Customer</li>
        </ol>
    </nav>

    <h1 class="mb-4">Edit Customer: <?= htmlspecialchars($customerData['Full_Name'] ?? 'Unknown') ?></h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit_customer.php?id=<?= htmlspecialchars($customerId) ?>" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                        <div class="invalid-feedback">Please enter the customer's full name.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" required>
                    <div class="invalid-feedback">Please enter the customer's address.</div>
                </div>
                
                <div class="mb-3">
                    <label for="id_type" class="form-label">ID Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_type" name="id_type" required>
                        <option value="Passport" <?= ($formData['id_type'] == 'Passport') ? 'selected' : '' ?>>Passport</option>
                        <option value="Driver License" <?= ($formData['id_type'] == 'Driver License') ? 'selected' : '' ?>>Driver License</option>
                        <option value="National ID" <?= ($formData['id_type'] == 'National ID') ? 'selected' : '' ?>>National ID</option>
                    </select>
                    <div class="invalid-feedback">Please select the customer's ID type.</div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Update Password (Optional)</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6">
                        <div class="form-text">Leave blank to keep the current password. Must be at least 6 characters long if changing.</div>
                        <div class="invalid-feedback">Password must be at least 6 characters.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password_confirm" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                        <div class="invalid-feedback">Please confirm the new password.</div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="update_customer" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                    <a href="manage_customers.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 