<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
/* // Temporarily disabled to add first employee
// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied. Please log in as an employee.";
    header("Location: ../login.php");
    exit;
}
*/ // End temporary disable
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$db = getDatabase();
$conn = $db->getConnection();
$errors = [];
$ssn = $full_name = $address = $position = '';
$password = ''; // Added for password

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ssn = trim($_POST['ssn']);
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $position = trim($_POST['position']);
    $password = $_POST['password'] ?? ''; // Get password
    $password_confirm = $_POST['password_confirm'] ?? ''; // Get confirmation

    // Basic Validation
    if (empty($ssn)) $errors[] = "SSN is required.";
    // Add more specific SSN validation if needed (e.g., format)
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($position)) $errors[] = "Position is required.";
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) { // Example minimum length
        $errors[] = "Password must be at least 6 characters long.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check if SSN already exists
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Employee WHERE SSN = ?");
            $stmt->execute([$ssn]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "An employee with this SSN already exists.";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking SSN: " . $e->getMessage();
        }
    }

    // Insert into database if no errors
    if (empty($errors)) {
        // --- Hash Password --- 
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // --- End Hash Password ---
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO Employee (SSN, Full_Name, Address, Position, Password_Hash) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$ssn, $full_name, $address, $position, $password_hash]);
            
            if ($result) {
                $_SESSION['success_message'] = "Employee '" . htmlspecialchars($full_name) . "' added successfully.";
                header("Location: manage_employees.php");
                exit;
            } else {
                // Add specific error if execute fails without exception
                $errorInfo = $stmt->errorInfo();
                $errors[] = "Failed to add employee. Database execute failed: (" . ($errorInfo[1] ?? 'N/A') . ") " . ($errorInfo[2] ?? 'Unknown error');
            }
        } catch (PDOException $e) { // Catch PDOException specifically for DB errors
            // Catch potential duplicate primary key errors more gracefully
             if ($e->getCode() == '23000') { // SQLSTATE for integrity constraint violation
                 $errors[] = "Database error: Likely duplicate SSN.";
             } else {
                 $errors[] = "Database PDO error: [" . $e->getCode() . "] " . $e->getMessage();
             }
        } catch (Exception $e) { // Catch other general exceptions
             $errors[] = "General error: " . $e->getMessage();
        }
    }
}

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_employees.php">Manage Employees</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add New Employee</li>
        </ol>
    </nav>

    <h1 class="mb-4">Add New Employee</h1>

    <div class="card shadow-sm">
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

            <form method="post" action="add_employee.php" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="ssn" class="form-label">SSN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ssn" name="ssn" value="<?= htmlspecialchars($ssn) ?>" required maxlength="11">
                        <div class="invalid-feedback">SSN is required.</div>
                        <div class="form-text">Social Security Number (or SIN/Driving Licence). Must be unique.</div>
                    </div>
                     <div class="col-md-8 mb-3">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required maxlength="100">
                        <div class="invalid-feedback">Full name is required.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($address) ?>" required maxlength="255">
                    <div class="invalid-feedback">Address is required.</div>
                </div>
                
                <div class="mb-3">
                    <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="position" name="position" value="<?= htmlspecialchars($position) ?>" required maxlength="50" placeholder="e.g., Receptionist, Manager, Cleaner">
                    <div class="invalid-feedback">Position is required.</div>
                </div>
                
                <hr class="my-4">
                <h5 class="mb-3">Set Initial Password</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="invalid-feedback">Password is required (min 6 characters).</div>
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        <div class="invalid-feedback">Please confirm the password.</div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Add Employee
                    </button>
                    <a href="manage_employees.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 