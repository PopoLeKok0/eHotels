<?php
/**
 * e-Hotels Header
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine if user is logged in and their role
$loggedIn = isset($_SESSION['user_id']);
$isEmployee = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
$isCustomer = $loggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

// Function to check if the current page matches a given path
function isActive($path) {
    // Construct the expected request URI base path, ensuring it starts with /eHotels/
    $expectedUri = '/eHotels/' . ltrim($path, '/');
    // Get the current request URI path part (without query string)
    $currentUriPath = strtok($_SERVER['REQUEST_URI'], '?');
    // Check if the current URI path exactly matches the expected path
    return $currentUriPath === $expectedUri ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Hotels - Find and Book Rooms in Top Hotel Chains</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/eHotels/css/styles.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/eHotels/index.php">
                <i class="fas fa-hotel me-2"></i>e-Hotels
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('index.php') ?>" href="/eHotels/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('search.php') ?>" href="/eHotels/search.php">Search Rooms</a>
                    </li>
                    <?php if ($isCustomer): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('my_bookings.php') ?>" href="/eHotels/my_bookings.php">My Bookings</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($isEmployee): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="employeeDropdown" role="button" data-bs-toggle="dropdown">
                                Employee Portal
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item <?= isActive('employee/index.php') ?>" href="/eHotels/employee/index.php">Dashboard</a></li>
                                <li><a class="dropdown-item <?= isActive('employee/check_in.php') ?>" href="/eHotels/employee/check_in.php">Check-in Guests</a></li>
                                <li><a class="dropdown-item <?= isActive('employee/direct_rental.php') ?>" href="/eHotels/employee/direct_rental.php">Direct Rental</a></li>
                                <li><a class="dropdown-item <?= isActive('employee/manage_customers.php') ?>" href="/eHotels/employee/manage_customers.php">Manage Customers</a></li>
                                <li><a class="dropdown-item <?= isActive('employee/manage_employees.php') ?>" href="/eHotels/employee/manage_employees.php">Manage Employees</a></li>
                                <li><a class="dropdown-item <?= isActive('hotel_chain/manage_chains.php') ?>" href="/eHotels/hotel_chain/manage_chains.php">Manage Hotel Chains</a></li>
                                <li><a class="dropdown-item <?= isActive('hotel/manage_hotels.php') ?>" href="/eHotels/hotel/manage_hotels.php">Manage Hotels</a></li>
                                <li><a class="dropdown-item <?= isActive('employee/reports.php') ?>" href="/eHotels/employee/reports.php">View Reports</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($loggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/eHotels/profile.php">My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/eHotels/logout.php">Log Out</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('login.php') ?>" href="/eHotels/login.php">Log In</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= isActive('register.php') ?>" href="/eHotels/register.php">Register</a>
                        </li>
                        <!-- Demo Link - Add First Employee -->
                        <li class="nav-item">
                             <a class="nav-link <?= isActive('employee/add_employee.php') ?>" href="/eHotels/employee/add_employee.php" style="color: yellow; font-weight: bold;">Setup Employee (Demo)</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-4">
        <!-- Display any success messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Display any error messages -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>
</body>
</html> 