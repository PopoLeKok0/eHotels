<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$message = '';
$messageType = 'danger';
$formData = [
    'full_name' => '',
    'email' => '',
    'address' => '',
    'id_type' => 'Passport' // Default
];

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
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
                $message = "An account with this email address already exists. Please <a href='login.php'>login</a>.";
                $messageType = 'warning';
            } else {
                // Hash the password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate Customer ID
                $customer_id = uniqid('cust-', true);
                
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
                    // Registration successful - Optionally log them in directly or redirect to login
                     $_SESSION['success_message'] = "Registration successful! Please log in with your new account.";
                     header("Location: login.php");
                     exit;
                } else {
                    throw new Exception("Failed to create customer record.");
                }
            }

        } catch (PDOException $e) {
            error_log("Registration DB Error: " . $e->getMessage());
            $message = "An error occurred during registration. Please try again later.";
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            $message = "An unexpected error occurred. Please try again later.";
        }
    }
}

// Include header AFTER potential redirects
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                 <div class="card-header bg-secondary text-white">
                    <h1 class="h4 mb-0">Register New Account</h1>
                </div>
                <div class="card-body p-4">
                     <?php if (!empty($message)): ?>
                        <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                            <?= $message; // Allow HTML in message for login link ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="register.php" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>
                        
                         <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($formData['address']) ?>" required>
                             <div class="invalid-feedback">Please enter your address.</div>
                       </div>
                       
                         <div class="mb-3">
                            <label for="id_type" class="form-label">ID Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_type" name="id_type" required>
                                <option value="Passport" <?= ($formData['id_type'] == 'Passport') ? 'selected' : '' ?>>Passport</option>
                                <option value="Driver License" <?= ($formData['id_type'] == 'Driver License') ? 'selected' : '' ?>>Driver License</option>
                                <option value="National ID" <?= ($formData['id_type'] == 'National ID') ? 'selected' : '' ?>>National ID</option>
                            </select>
                             <div class="invalid-feedback">Please select your ID type.</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <div class="form-text">Must be at least 6 characters long.</div>
                            <div class="invalid-feedback">Please enter a password (min. 6 characters).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                             <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="register" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </button>
                        </div>
                        <p class="text-center mb-0">
                           Already have an account? <a href="login.php">Login here</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?> 