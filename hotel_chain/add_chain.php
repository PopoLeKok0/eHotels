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
$chain_name = '';
$central_office_address = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate chain name
    if (empty($_POST['chain_name'])) {
        $errors[] = "Chain name is required";
    } else {
        $chain_name = trim($_POST['chain_name']);
        // Check if chain name already exists
        try {
            $check_stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM Hotel_Chain WHERE Chain_Name = ?");
            $check_stmt->execute([$chain_name]);
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = "A hotel chain with this name already exists";
            }
        } catch (Exception $e) {
            $errors[] = "Error checking chain name: " . $e->getMessage();
        }
    }

    // Validate central office address
    if (empty($_POST['central_office_address'])) {
        $errors[] = "Central office address is required";
    } else {
        $central_office_address = trim($_POST['central_office_address']);
    }

    // If no errors, insert the new hotel chain
    if (empty($errors)) {
        try {
            $stmt = $db->getConnection()->prepare(
                "INSERT INTO Hotel_Chain (Chain_Name, Central_Office_Address, Num_Hotels) VALUES (?, ?, 0)"
            );
            $result = $stmt->execute([$chain_name, $central_office_address]);

            if ($result) {
                $_SESSION['success_message'] = "Hotel chain '{$chain_name}' was added successfully";
                header("Location: manage_chains.php");
                exit;
            } else {
                $errors[] = "Failed to add hotel chain";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Add New Hotel Chain</h4>
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

                    <form action="add_chain.php" method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="chain_name" class="form-label">Chain Name</label>
                            <input type="text" class="form-control" id="chain_name" name="chain_name" 
                                   value="<?= htmlspecialchars($chain_name) ?>" required>
                            <div class="invalid-feedback">Please enter a chain name</div>
                        </div>

                        <div class="mb-3">
                            <label for="central_office_address" class="form-label">Central Office Address</label>
                            <textarea class="form-control" id="central_office_address" name="central_office_address" 
                                     required rows="3"><?= htmlspecialchars($central_office_address) ?></textarea>
                            <div class="invalid-feedback">Please enter the central office address</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="manage_chains.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Hotel Chain</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 