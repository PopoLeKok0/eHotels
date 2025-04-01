<?php
// Start session and check employee login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    $_SESSION['error_message'] = "Access denied."; header("Location: ../login.php"); exit;
}
require_once '../config/database.php'; 
require_once '../includes/header.php'; 
?>
<div class="container my-5">
    <h1 class="mb-4">Manage Hotels & Chains</h1>
    <div class="alert alert-info">Hotel and Hotel Chain management functionality (view, add, edit details) will be implemented here.</div>
    <!-- Add forms/tables for managing chains and hotels -->
    <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
</div>
<?php require_once '../includes/footer.php'; ?> 