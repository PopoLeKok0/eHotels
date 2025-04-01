<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

$message = '';
$messageType = 'danger';
$loginIdentifier = ''; // Changed variable name for clarity

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Determine redirect based on role
    $redirectTarget = ($_SESSION['role'] === 'employee') ? 'employee/index.php' : 'index.php';
    header("Location: " . $redirectTarget);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $loginIdentifier = trim($_POST['loginIdentifier'] ?? ''); // Use new name from form
    $passwordInput = $_POST['password'] ?? '';

    if (empty($loginIdentifier) || empty($passwordInput)) {
        $message = "Email or SIN/SSN and password are required.";
    } else {
        try {
            $dbInstance = getDatabase();
            $db = $dbInstance->getConnection();

            $user = null;
            $role = null;

            // --- Try finding Customer by Email ---
            $stmt_cust_email = $db->prepare("SELECT Customer_ID, Full_Name, Address, Email_Address, Password_Hash FROM Customer WHERE Email_Address = :email");
            $stmt_cust_email->bindParam(':email', $loginIdentifier);
            $stmt_cust_email->execute();
            $customer = $stmt_cust_email->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($passwordInput, $customer['Password_Hash'])) {
                $user = $customer;
                $role = 'customer';
                $_SESSION['user_id'] = $user['Customer_ID']; 
                $_SESSION['customer_name'] = $user['Full_Name'];
                $_SESSION['username'] = $user['Full_Name']; 
                $_SESSION['customer_email'] = $user['Email_Address']; 
                $_SESSION['customer_address'] = $user['Address'];
                // Note: SIN/ID is not directly stored in Customer table anymore by default
                // $_SESSION['customer_sin'] = null; // Clear or retrieve if needed from elsewhere
            } else {
                // --- If not customer by email, try Employee by Email ---
                $stmt_emp_email = $db->prepare("SELECT SSN, Full_Name, Address, Email_Address, Password_Hash, Position FROM Employee WHERE Email_Address = :email");
                $stmt_emp_email->bindParam(':email', $loginIdentifier);
                $stmt_emp_email->execute();
                $employee = $stmt_emp_email->fetch(PDO::FETCH_ASSOC);

                if ($employee && password_verify($passwordInput, $employee['Password_Hash'])) {
                    $user = $employee;
                    $role = 'employee';
                    $_SESSION['user_id'] = $user['SSN']; // Using SSN as employee ID
                    $_SESSION['employee_name'] = $user['Full_Name']; 
                    $_SESSION['username'] = $user['Full_Name']; 
                    $_SESSION['employee_position'] = $user['Position']; 
                    // Might need to store Hotel Address if they only work at one
                } else {
                    // --- If not found by email, try Employee by SIN/SSN ---
                    // (Assuming SIN/SSN format is distinct enough from email)
                    if (!filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL)) {
                         $stmt_emp_ssn = $db->prepare("SELECT SSN, Full_Name, Address, Email_Address, Password_Hash, Position FROM Employee WHERE SSN = :ssn");
                         $stmt_emp_ssn->bindParam(':ssn', $loginIdentifier);
                         $stmt_emp_ssn->execute();
                         $employee_ssn = $stmt_emp_ssn->fetch(PDO::FETCH_ASSOC);

                         if ($employee_ssn && password_verify($passwordInput, $employee_ssn['Password_Hash'])) {
                             $user = $employee_ssn;
                             $role = 'employee';
                             $_SESSION['user_id'] = $user['SSN'];
                             $_SESSION['employee_name'] = $user['Full_Name']; 
                             $_SESSION['username'] = $user['Full_Name']; 
                             $_SESSION['employee_position'] = $user['Position'];
                         }
                    }
                }
            }
            
            // --- Check login result ---
            if ($user && $role) {
                $_SESSION['role'] = $role;

                // Redirect after successful login
                $redirectTarget = ($role === 'employee') ? 'employee/index.php' : 'index.php';
                $redirectUrl = $_SESSION['redirect_after_login'] ?? $redirectTarget;
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirectUrl);
                exit;
            } else {
                $message = "Invalid login credentials or user not found.";
            }

        } catch (PDOException $e) {
            error_log("Login DB Error: " . $e->getMessage());
            $message = "An error occurred during login. Please try again later.";
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            $message = "An unexpected error occurred. Please try again later.";
        }
    }
}

// Include header AFTER potential redirects
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Login to eHotels</h1>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="login.php">
                        <div class="mb-3">
                            <label for="loginIdentifier" class="form-label">Email or SIN/SSN</label>
                            <input type="text" class="form-control" id="loginIdentifier" name="loginIdentifier" value="<?= htmlspecialchars($loginIdentifier) ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <!-- Keep redirect URL if set -->
                        <?php if (isset($_SESSION['redirect_after_login'])): ?>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SESSION['redirect_after_login']) ?>">
                        <?php endif; ?>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                        <p class="text-center mb-0">
                            Don't have an account? <a href="register.php">Register here</a>
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