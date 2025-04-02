<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php';

// Check auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied.";
    header("Location: ../login.php");
    exit;
}

// Get Chain Name
if (!isset($_GET['chain_name']) || empty($_GET['chain_name'])) {
    $_SESSION['error_message'] = "Hotel chain not specified.";
    header("Location: manage_chains.php");
    exit;
}

$chain_name = trim($_GET['chain_name']);
$db = getDatabase();
$conn = $db->getConnection();
$errors = [];
$success_message = $_SESSION['success_message'] ?? null;
if ($success_message) unset($_SESSION['success_message']);

$emails = [];
$phones = [];

// Check if chain exists
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Hotel_Chain WHERE Chain_Name = ?");
    $stmt->execute([$chain_name]);
    if ($stmt->fetchColumn() == 0) {
        $_SESSION['error_message'] = "Hotel chain not found.";
        header("Location: manage_chains.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error checking hotel chain: " . $e->getMessage();
    header("Location: manage_chains.php");
    exit;
}

// Fetch existing contacts
try {
    $email_stmt = $conn->prepare("SELECT Email_Address FROM Hotel_Chain_Emails WHERE Chain_Name = ? ORDER BY Email_Address");
    $email_stmt->execute([$chain_name]);
    $emails = $email_stmt->fetchAll(PDO::FETCH_COLUMN);

    $phone_stmt = $conn->prepare("SELECT Phone_Number FROM Hotel_Chain_Phones WHERE Chain_Name = ? ORDER BY Phone_Number");
    $phone_stmt->execute([$chain_name]);
    $phones = $phone_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $errors[] = "Error fetching contacts: " . $e->getMessage();
}

// Handle adding email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_email') {
    $new_email = trim($_POST['email_address']);
    if (empty($new_email)) {
        $errors[] = "Email address cannot be empty.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Hotel_Chain_Emails (Chain_Name, Email_Address) VALUES (?, ?)");
            $stmt->execute([$chain_name, $new_email]);
            $_SESSION['success_message'] = "Email added successfully.";
            header("Location: manage_chain_contacts.php?chain_name=" . urlencode($chain_name));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Duplicate entry
                $errors[] = "Email address already exists for this chain.";
            } else {
                $errors[] = "Database error adding email: " . $e->getMessage();
            }
        }
    }
}

// Handle deleting email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_email') {
    $email_to_delete = trim($_POST['email_address']);
    try {
        $stmt = $conn->prepare("DELETE FROM Hotel_Chain_Emails WHERE Chain_Name = ? AND Email_Address = ?");
        $stmt->execute([$chain_name, $email_to_delete]);
        $_SESSION['success_message'] = "Email deleted successfully.";
        header("Location: manage_chain_contacts.php?chain_name=" . urlencode($chain_name));
        exit;
    } catch (Exception $e) {
        $errors[] = "Error deleting email: " . $e->getMessage();
    }
}

// Handle adding phone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_phone') {
    $new_phone = trim($_POST['phone_number']);
    if (empty($new_phone)) {
        $errors[] = "Phone number cannot be empty.";
    } elseif (!preg_match('/^\+?[0-9()\-\s]{7,20}$/', $new_phone)) { // Basic validation
        $errors[] = "Invalid phone number format.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Hotel_Chain_Phones (Chain_Name, Phone_Number) VALUES (?, ?)");
            $stmt->execute([$chain_name, $new_phone]);
            $_SESSION['success_message'] = "Phone number added successfully.";
            header("Location: manage_chain_contacts.php?chain_name=" . urlencode($chain_name));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Duplicate entry
                $errors[] = "Phone number already exists for this chain.";
            } else {
                $errors[] = "Database error adding phone: " . $e->getMessage();
            }
        }
    }
}

// Handle deleting phone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_phone') {
    $phone_to_delete = trim($_POST['phone_number']);
    try {
        $stmt = $conn->prepare("DELETE FROM Hotel_Chain_Phones WHERE Chain_Name = ? AND Phone_Number = ?");
        $stmt->execute([$chain_name, $phone_to_delete]);
        $_SESSION['success_message'] = "Phone number deleted successfully.";
        header("Location: manage_chain_contacts.php?chain_name=" . urlencode($chain_name));
        exit;
    } catch (Exception $e) {
        $errors[] = "Error deleting phone: " . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <h2>Manage Contacts for: <?= htmlspecialchars($chain_name) ?></h2>
    <p><a href="manage_chains.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Hotel Chains</a></p>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Emails Section -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Addresses</h5>
                </div>
                <div class="card-body">
                    <form action="manage_chain_contacts.php?chain_name=<?= urlencode($chain_name) ?>" method="POST" class="mb-3">
                        <input type="hidden" name="action" value="add_email">
                        <div class="input-group">
                            <input type="email" class="form-control" name="email_address" placeholder="Add new email" required>
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </form>
                    <?php if (empty($emails)): ?>
                        <p class="text-muted">No email addresses added yet.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($emails as $email): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($email) ?>
                                    <form action="manage_chain_contacts.php?chain_name=<?= urlencode($chain_name) ?>" method="POST" onsubmit="return confirm('Delete this email?');">
                                        <input type="hidden" name="action" value="delete_email">
                                        <input type="hidden" name="email_address" value="<?= htmlspecialchars($email) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Phone Numbers Section -->
        <div class="col-md-6">
             <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Phone Numbers</h5>
                </div>
                <div class="card-body">
                    <form action="manage_chain_contacts.php?chain_name=<?= urlencode($chain_name) ?>" method="POST" class="mb-3">
                        <input type="hidden" name="action" value="add_phone">
                        <div class="input-group">
                            <input type="tel" class="form-control" name="phone_number" placeholder="Add new phone number" required pattern="^\+?[0-9()\-\s]{7,20}$">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </form>
                     <?php if (empty($phones)): ?>
                        <p class="text-muted">No phone numbers added yet.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($phones as $phone): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($phone) ?>
                                    <form action="manage_chain_contacts.php?chain_name=<?= urlencode($chain_name) ?>" method="POST" onsubmit="return confirm('Delete this phone number?');">
                                        <input type="hidden" name="action" value="delete_phone">
                                        <input type="hidden" name="phone_number" value="<?= htmlspecialchars($phone) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 