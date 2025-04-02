<?php
session_start();
require_once '../config/database.php';
require_once '../includes/header.php'; // Assuming header includes necessary CSS/JS

// Check if user is logged in and potentially has a specific role (e.g., admin/employee)
// For now, just check login, adjust role check as needed later
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Access denied. Please log in.";
    header("Location: ../login.php");
    exit;
}

$db = getDatabase();
$chains = [];
$page_error_message = ''; // Use a different name to avoid conflict with session message

try {
    // Use getConnection() to get the PDO object
    $stmt = $db->getConnection()->query("SELECT Chain_Name, Central_Office_Address, Num_Hotels FROM Hotel_Chain ORDER BY Chain_Name");
    $chains = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $page_error_message = "Error fetching hotel chains: " . $e->getMessage();
    // Log error if you have a logging utility: log_error($page_error_message);
}

?>

<div class="container mt-4">
    <h1 class="mb-4">Manage Hotel Chains</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (!empty($page_error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($page_error_message) ?></div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="add_chain.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add New Hotel Chain</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
             <h5 class="mb-0">Existing Hotel Chains</h5>
         </div>
        <div class="card-body">
            <?php if (empty($chains) && empty($page_error_message)): ?>
                <p class="text-center text-muted">No hotel chains found in the database.</p>
            <?php elseif (!empty($chains)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Chain Name</th>
                                <th>Central Office Address</th>
                                <th class="text-center">Number of Hotels</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chains as $chain): ?>
                                <tr>
                                    <td><?= htmlspecialchars($chain['Chain_Name']) ?></td>
                                    <td><?= htmlspecialchars($chain['Central_Office_Address']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($chain['Num_Hotels']) ?></td>
                                    <td class="text-end">
                                        <a href="manage_chain_contacts.php?chain_name=<?= urlencode($chain['Chain_Name']) ?>" class="btn btn-sm btn-info me-1" title="Manage Contacts">
                                            <i class="fas fa-address-book"></i>
                                        </a>
                                        <a href="edit_chain.php?chain_name=<?= urlencode($chain['Chain_Name']) ?>" class="btn btn-sm btn-primary me-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="delete_chain.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this hotel chain? This will also attempt to delete associated hotels and rooms. This action cannot be undone.');">
                                            <input type="hidden" name="chain_name" value="<?= htmlspecialchars($chain['Chain_Name']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?> 