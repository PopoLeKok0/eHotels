<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; header("Location: ../login.php"); exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$rentingId = $_GET['renting_id'] ?? null;
$rentingData = null;
$customerName = '';
$hotelName = '';
$roomNumber = '';
$message = '';
$messageType = 'danger';

if (!$rentingId) { // Basic check, UUID validation could be added
    $_SESSION['error_message'] = "Invalid or missing Renting ID.";
    header("Location: index.php"); // Redirect to dashboard or a rentings list page
    exit;
}

// Fetch Renting details and related info
try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Fetch renting, customer, room, and hotel details
    // Assuming Renting table has Customer_ID, Room_ID, Hotel_ID (denormalized or via Room)
    // Adjust query based on your final schema for Renting/Room links
    $stmt = $db->prepare("
        SELECT 
            r.Renting_ID, r.Start_Date, r.End_Date, r.Payment_Amount, r.Payment_Date,
            c.Full_Name AS Customer_Name,
            rm.Room_Number,
            h.Hotel_Name
        FROM Renting r
        JOIN Customer c ON r.Customer_ID = c.Customer_ID
        JOIN Room rm ON r.Room_ID = rm.Room_ID -- Assumes Room_ID FK in Renting
        JOIN Hotel h ON rm.Hotel_ID = h.Hotel_ID -- Assumes Room table has Hotel_ID FK
        WHERE r.Renting_ID = :renting_id
    ");
    // If Renting references Room via Rented_By, adjust joins accordingly.
    
    $stmt->bindParam(':renting_id', $rentingId);
    $stmt->execute();
    $rentingData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rentingData) {
        throw new Exception("Renting record not found.");
    }
    
    // Extract details for display
    $customerName = $rentingData['Customer_Name'];
    $hotelName = $rentingData['Hotel_Name'];
    $roomNumber = $rentingData['Room_Number'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
        $paymentAmount = trim($_POST['payment_amount'] ?? '');

        // Basic Validation
        if (!is_numeric($paymentAmount) || $paymentAmount < 0) {
            $message = "Please enter a valid, non-negative payment amount.";
        } else {
            // Update Renting record
            $stmt_update = $db->prepare("
                UPDATE Renting 
                SET Payment_Amount = :amount, Payment_Date = CURDATE() 
                WHERE Renting_ID = :renting_id
            ");
            $stmt_update->bindParam(':amount', $paymentAmount);
            $stmt_update->bindParam(':renting_id', $rentingId);

            if ($stmt_update->execute()) {
                 $_SESSION['success_message'] = "Payment of $" . number_format($paymentAmount, 2) . " recorded successfully for Renting ID: " . htmlspecialchars($rentingId) . ".";
                 // Redirect to a relevant page, maybe dashboard or a rentings list
                 header("Location: index.php"); 
                 exit;
            } else {
                 throw new Exception("Failed to update payment information.");
            }
        }
    }

} catch (PDOException $e) {
    error_log("Record Payment DB Error: " . $e->getMessage());
    $message = "Database error occurred.";
} catch (Exception $e) {
    error_log("Record Payment Error: " . $e->getMessage());
    $message = "An unexpected error occurred: " . $e->getMessage();
    if ($e->getMessage() === "Renting record not found.") {
         $_SESSION['error_message'] = $e->getMessage();
         header("Location: index.php"); 
         exit;
     }
}

?>
<div class="container my-5">
    <nav aria-label="breadcrumb mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <!-- Add link to Rentings list if it exists -->
            <li class="breadcrumb-item active" aria-current="page">Record Payment</li>
        </ol>
    </nav>

    <h1 class="mb-4">Record Payment for Renting</h1>

    <div class="card shadow-sm">
        <div class="card-header">
             Details for Renting ID: <?= htmlspecialchars($rentingId) ?>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType); ?>" role="alert">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($rentingData): ?>
                <dl class="row mb-4">
                    <dt class="col-sm-3">Customer:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($customerName) ?></dd>

                    <dt class="col-sm-3">Hotel:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($hotelName) ?></dd>

                    <dt class="col-sm-3">Room Number:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($roomNumber) ?></dd>

                    <dt class="col-sm-3">Stay Dates:</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($rentingData['Start_Date']) ?> to <?= htmlspecialchars($rentingData['End_Date']) ?></dd>
                    
                     <dt class="col-sm-3">Current Payment Status:</dt>
                    <dd class="col-sm-9">
                        <?php if ($rentingData['Payment_Amount'] !== null): ?>
                            <span class="badge bg-success">Paid: $<?= number_format($rentingData['Payment_Amount'], 2) ?> on <?= htmlspecialchars($rentingData['Payment_Date'] ?: 'N/A') ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Payment Not Recorded</span>
                        <?php endif; ?>
                    </dd>
                </dl>
                
                <hr>
                
                <form method="post" action="record_payment.php?renting_id=<?= htmlspecialchars($rentingId) ?>">
                     <div class="mb-3">
                         <label for="payment_amount" class="form-label"><h5>Enter Payment Amount Received:</h5></label>
                         <div class="input-group">
                             <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control form-control-lg" id="payment_amount" name="payment_amount" 
                                   value="<?= htmlspecialchars($_POST['payment_amount'] ?? $rentingData['Payment_Amount'] ?? '') ?>" required min="0">
                        </div>
                         <div class="form-text">Enter the total amount paid by the customer for this rental.</div>
                     </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="record_payment" class="btn btn-success btn-lg">
                             <i class="fas fa-check-circle me-2"></i>Record Payment
                        </button>
                        <a href="index.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                 <div class="alert alert-danger">Could not load renting details.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 