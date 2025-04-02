<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; header("Location: ../login.php"); exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$customers = [];
$searchTerm = $_GET['search'] ?? '';
$message = '';
$messageType = '';

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Base query
    $query = "SELECT Customer_ID, Full_Name, Address, Email_Address, ID_Type, Date_of_Registration FROM Customer";
    $params = [];

    // Apply search filter if provided
    if (!empty($searchTerm)) {
        $query .= " WHERE Full_Name LIKE :term OR Email_Address LIKE :term OR Customer_ID LIKE :term";
        $params[':term'] = '%' . $searchTerm . '%';
    }

    $query .= " ORDER BY Full_Name LIMIT 100"; // Limit results for performance

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Error fetching customer data: " . $e->getMessage();
    $messageType = 'danger';
}

?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Customers</h1>
        <a href="add_customer.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add New Customer</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="GET" action="manage_customers.php" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search by Name, Email, or Customer ID..." name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Search</button>
            <?php if (!empty($searchTerm)): ?>
                 <a href="manage_customers.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Customer Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>ID Type</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No customers found<?= !empty($searchTerm) ? ' matching "' . htmlspecialchars($searchTerm) . '"' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= htmlspecialchars($customer['Full_Name']) ?></td>
                            <td><?= htmlspecialchars($customer['Email_Address']) ?></td>
                            <td><?= htmlspecialchars($customer['Address']) ?></td>
                            <td><?= htmlspecialchars($customer['ID_Type']) ?></td>
                            <td><?= date("M j, Y", strtotime($customer['Date_of_Registration'])) ?></td>
                            <td>
                                <a href="edit_customer.php?id=<?= htmlspecialchars($customer['Customer_ID']) ?>" class="btn btn-sm btn-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="view_customer_bookings.php?id=<?= htmlspecialchars($customer['Customer_ID']) ?>" class="btn btn-sm btn-info me-1" title="View Bookings"><i class="fas fa-book"></i></a>
                                <!-- Delete Form -->
                                <form action="delete_customer.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete customer <?= htmlspecialchars(addslashes($customer['Full_Name'])) ?>? This action cannot be undone.');">
                                    <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer['Customer_ID']) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
</div>
<?php require_once '../includes/footer.php'; ?> 