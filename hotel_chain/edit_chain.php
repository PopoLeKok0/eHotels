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
$original_chain_name = '';

// Check if chain_name is provided in GET or POST
if (isset($_GET['chain_name'])) {
    $original_chain_name = trim($_GET['chain_name']);
} elseif (isset($_POST['original_chain_name'])) {
    $original_chain_name = trim($_POST['original_chain_name']);
} else {
    $_SESSION['error_message'] = "No hotel chain specified for editing.";
    header("Location: manage_chains.php");
    exit;
}

// Fetch the chain data from the database
try {
    $stmt = $db->getConnection()->prepare("SELECT * FROM Hotel_Chain WHERE Chain_Name = ?");
    $stmt->execute([$original_chain_name]);
    $chain = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chain) {
        $_SESSION['error_message'] = "Hotel chain not found.";
        header("Location: manage_chains.php");
        exit;
    }
    
    // Pre-fill form with current values
    $chain_name = $chain['Chain_Name'];
    $central_office_address = $chain['Central_Office_Address'];
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching hotel chain data: " . $e->getMessage();
    header("Location: manage_chains.php");
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate chain name
    if (empty($_POST['chain_name'])) {
        $errors[] = "Chain name is required";
    } else {
        $chain_name = trim($_POST['chain_name']);
        // Check if the new chain name already exists (if it's different from the original)
        if ($chain_name !== $original_chain_name) {
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
    }

    // Validate central office address
    if (empty($_POST['central_office_address'])) {
        $errors[] = "Central office address is required";
    } else {
        $central_office_address = trim($_POST['central_office_address']);
    }

    // If no errors, update the hotel chain
    if (empty($errors)) {
        try {
            // Begin transaction for potential multi-table update
            $db_connection = $db->getConnection();
            $db_connection->beginTransaction();
            
            // If chain name is changing, we need to update all related records
            if ($chain_name !== $original_chain_name) {
                // Update Hotel records that reference this chain
                $update_hotels_stmt = $db_connection->prepare(
                    "UPDATE Hotel SET Chain_Name = ? WHERE Chain_Name = ?"
                );
                $update_hotels_stmt->execute([$chain_name, $original_chain_name]);
                
                // Update the chain record itself
                $update_chain_stmt = $db_connection->prepare(
                    "UPDATE Hotel_Chain SET Chain_Name = ?, Central_Office_Address = ? WHERE Chain_Name = ?"
                );
                $update_chain_stmt->execute([$chain_name, $central_office_address, $original_chain_name]);
            } else {
                // Just update the address if name isn't changing
                $update_chain_stmt = $db_connection->prepare(
                    "UPDATE Hotel_Chain SET Central_Office_Address = ? WHERE Chain_Name = ?"
                );
                $update_chain_stmt->execute([$central_office_address, $original_chain_name]);
            }
            
            // Commit transaction
            $db_connection->commit();
            
            $_SESSION['success_message'] = "Hotel chain updated successfully";
            header("Location: manage_chains.php");
            exit;
        } catch (Exception $e) {
            // Roll back transaction on error
            if ($db_connection->inTransaction()) {
                $db_connection->rollBack();
            }
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
                    <h4 class="mb-0">Edit Hotel Chain</h4>
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

                    <form action="edit_chain.php" method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="original_chain_name" value="<?= htmlspecialchars($original_chain_name) ?>">
                        
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
                            <button type="submit" class="btn btn-primary">Update Hotel Chain</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 