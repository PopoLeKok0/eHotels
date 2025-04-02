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
$formData = [
    'full_name' => '',
    'email' => '',
    'address' => '',
    'id_type' => 'Passport' // Default
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $formData['full_name'] = trim($_POST['full_name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['id_type'] = $_POST['id_type'] ?? 'Passport';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Basic Validation
    if (empty($formData['full_name']) || empty($formData['email']) || empty($formData['address']) || empty($password) || empty($passwordConfirm)) {
        $message = "All fields marked with * are required.";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) { // Basic password length check
        $message = "Password must be at least 6 characters long.";
    } elseif ($password !== $passwordConfirm) {
        $message = "Passwords do not match.";
    } else {
        try {
            $dbInstance = getDatabase();
            $db = $dbInstance->getConnection();

            // Check if email already exists
            $stmt_check = $db->prepare("SELECT Customer_ID FROM Customer WHERE Email_Address = :email");
            $stmt_check->bindParam(':email', $formData['email']);
            $stmt_check->execute();
            
            if ($stmt_check->fetch()) {
                $message = "A customer with this email address already exists.";
                $messageType = 'warning';
            } else {
                // Hash the password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate Customer ID (using simpler approach for now)
                $customer_id = 'cust-' . substr(md5(uniqid(rand(), true)), 0, 8);
                
                // Insert new customer
                $stmt_insert = $db->prepare("
                    INSERT INTO Customer (Customer_ID, Full_Name, Address, Email_Address, Password_Hash, ID_Type, Date_of_Registration)
                    VALUES (:id, :name, :addr, :email, :hash, :id_type, CURDATE())
                ");
                $stmt_insert->bindParam(':id', $customer_id);
                $stmt_insert->bindParam(':name', $formData['full_name']);
                $stmt_insert->bindParam(':addr', $formData['address']);
                $stmt_insert->bindParam(':email', $formData['email']);
                $stmt_insert->bindParam(':hash', $passwordHash);
                $stmt_insert->bindParam(':id_type', $formData['id_type']);
                
                if ($stmt_insert->execute()) {
                    $_SESSION['success_message'] = "Customer '" . htmlspecialchars($formData['full_name']) . "' added successfully.";
                    header("Location: manage_customers.php");
                    exit;
                } else {
                    throw new Exception("Failed to create customer record in database.");
                }
            }

        } catch (PDOException $e) {
            error_log("Add Customer DB Error: " . $e->getMessage());
            $message = "Database error occurred while adding customer.";
        } catch (Exception $e) {
            error_log("Add Customer Error: " . $e->getMessage());
            $message = "An unexpected error occurred: " . $e->getMessage();
        }
    }
}

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_customers.php">Manage Customers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add New Customer</li>
        </ol>
    </nav>

    <h1 class="mb-4">Add New Customer</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="add_customer.php" class="needs-validation" novalidate>
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
                <h5 class="mb-3">Set Initial Password</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">Must be at least 6 characters long.</div>
                        <div class="invalid-feedback">Please enter a password (min. 6 characters).</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        <div class="invalid-feedback">Please confirm the password.</div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="add_customer" class="btn btn-success btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Add Customer
                    </button>
                    <a href="manage_customers.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 