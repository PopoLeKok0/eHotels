<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied. Please log in as an employee.";
    header("Location: ../login.php");
    exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 

$employees = [];
$searchTerm = $_GET['search'] ?? '';
$message = '';
$messageType = '';

try {
    $dbInstance = getDatabase();
    $db = $dbInstance->getConnection();

    // Base query
    $query = "SELECT SSN, Full_Name, Address, Email_Address, Position, Hotel_Address FROM Employee";
    $params = [];

    // Apply search filter if provided
    if (!empty($searchTerm)) {
        $query .= " WHERE Full_Name LIKE :term OR Email_Address LIKE :term OR SSN LIKE :term OR Position LIKE :term OR Hotel_Address LIKE :term";
        $params[':term'] = '%' . $searchTerm . '%';
    }

    $query .= " ORDER BY Full_Name LIMIT 100"; // Limit results for performance

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = "Error fetching employee data: " . $e->getMessage();
    $messageType = 'danger';
}

?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Manage Employees</h1>
        <a href="add_employee.php" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add New Employee</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="GET" action="manage_employees.php" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search by Name, Email, SSN, or Position..." name="search" value="<?= htmlspecialchars($searchTerm) ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i> Search</button>
            <?php if (!empty($searchTerm)): ?>
                 <a href="manage_employees.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Employee Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>SSN</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Position</th>
                    <th>Works At Hotel</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No employees found<?= !empty($searchTerm) ? ' matching "' . htmlspecialchars($searchTerm) . '"' : '' ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?= htmlspecialchars($employee['SSN']) ?></td>
                            <td><?= htmlspecialchars($employee['Full_Name']) ?></td>
                            <td><?= htmlspecialchars($employee['Email_Address']) ?></td>
                            <td><?= htmlspecialchars($employee['Address']) ?></td>
                            <td><?= htmlspecialchars($employee['Position']) ?></td>
                            <td><?= htmlspecialchars($employee['Hotel_Address'] ?? 'N/A') ?></td>
                            <td>
                                <a href="edit_employee.php?ssn=<?= htmlspecialchars($employee['SSN']) ?>" class="btn btn-sm btn-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                <!-- Delete Form -->
                                <button type="button" class="btn btn-sm btn-danger" title="Delete" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteEmployeeModal<?= $employee['SSN'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <!-- Delete Employee Modal -->
                                <div class="modal fade" id="deleteEmployeeModal<?= $employee['SSN'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $employee['SSN'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteModalLabel<?= $employee['SSN'] ?>">Confirm Deletion</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete employee <strong><?= htmlspecialchars($employee['Full_Name']) ?> (<?= htmlspecialchars($employee['SSN']) ?>)</strong>?
                                                <p class="text-danger mt-2"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="delete_employee.php" method="POST" style="display: inline;">
                                                    <input type="hidden" name="ssn" value="<?= htmlspecialchars($employee['SSN']) ?>">
                                                    <button type="submit" class="btn btn-danger">Delete Employee</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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