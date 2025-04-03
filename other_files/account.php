<?php
/**
 * e-Hotels Account Management (Placeholder)
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705)
 */

// Start session if not already started

// Include database connection and header
require_once 'includes/db.php';
require_once 'includes/header.php';

// Initialize variables
$message = '';
$messageType = '';
$customer = null;
$bookings = [];
$action = $_GET['action'] ?? 'view';
$booking_id = $_GET['booking_id'] ?? null;

// Check if user is logged in (assuming session variable customer_id for customers)
$customer_id = $_SESSION['customer_id'] ?? null;

// If not logged in, show login form
if (!$customer_id) {
    // Process login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $email = $_POST['email'] ?? '';
        $sin = $_POST['sin'] ?? '';
        
        if (empty($email) || empty($sin)) {
            $message = "Please enter both email and SIN.";
            $messageType = "danger";
        } else {
            try {
                $db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Find customer by email and SIN
                $stmt = $db->prepare("
                    SELECT customer_id, name, email, address, sin
                    FROM Customer
                    WHERE email = :email AND sin = :sin
                ");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':sin', $sin);
                $stmt->execute();
                
                if ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Set session
                    $_SESSION['customer_id'] = $customer['customer_id'];
                    $_SESSION['customer_name'] = $customer['name'];
                    $_SESSION['customer_email'] = $customer['email'];
                    $_SESSION['user_role'] = 'customer'; // For navigation purposes
                    
                    // Redirect to account page
                    header("Location: account.php");
                    exit;
                } else {
                    $message = "Invalid email or SIN.";
                    $messageType = "danger";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
        // Process registration form
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $sin = $_POST['sin'] ?? '';
        
        if (empty($name) || empty($email) || empty($address) || empty($sin)) {
            $message = "All fields are required.";
            $messageType = "danger";
        } else {
            try {
                $db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if email or SIN already exists
                $checkStmt = $db->prepare("
                    SELECT customer_id FROM Customer
                    WHERE email = :email OR sin = :sin
                ");
                $checkStmt->bindParam(':email', $email);
                $checkStmt->bindParam(':sin', $sin);
                $checkStmt->execute();
                
                if ($checkStmt->fetch()) {
                    $message = "A user with this email or SIN already exists.";
                    $messageType = "danger";
                } else {
                    // Create new customer
                    $insertStmt = $db->prepare("
                        INSERT INTO Customer (name, email, address, sin)
                        VALUES (:name, :email, :address, :sin)
                    ");
                    $insertStmt->bindParam(':name', $name);
                    $insertStmt->bindParam(':email', $email);
                    $insertStmt->bindParam(':address', $address);
                    $insertStmt->bindParam(':sin', $sin);
                    $insertStmt->execute();
                    
                    $new_customer_id = $db->lastInsertId();
                    
                    // Set session
                    $_SESSION['customer_id'] = $new_customer_id;
                    $_SESSION['customer_name'] = $name;
                    $_SESSION['customer_email'] = $email;
                    $_SESSION['user_role'] = 'customer'; // For navigation purposes
                    
                    // Redirect to account page
                    header("Location: account.php");
                    exit;
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
} else {
    // User is logged in, fetch customer data and bookings
    try {
        $db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Fetch customer details
        $customerStmt = $db->prepare("
            SELECT * FROM Customer WHERE customer_id = :customer_id
        ");
        $customerStmt->bindParam(':customer_id', $customer_id);
        $customerStmt->execute();
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        // Handle profile update
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            
            if (empty($name) || empty($email) || empty($address)) {
                $message = "All fields are required.";
                $messageType = "danger";
            } else {
                // Check if email is already used by another customer
                $emailCheckStmt = $db->prepare("
                    SELECT customer_id FROM Customer
                    WHERE email = :email AND customer_id != :customer_id
                ");
                $emailCheckStmt->bindParam(':email', $email);
                $emailCheckStmt->bindParam(':customer_id', $customer_id);
                $emailCheckStmt->execute();
                
                if ($emailCheckStmt->fetch()) {
                    $message = "This email is already used by another account.";
                    $messageType = "danger";
                } else {
                    // Update customer profile
                    $updateStmt = $db->prepare("
                        UPDATE Customer
                        SET name = :name, email = :email, address = :address
                        WHERE customer_id = :customer_id
                    ");
                    $updateStmt->bindParam(':name', $name);
                    $updateStmt->bindParam(':email', $email);
                    $updateStmt->bindParam(':address', $address);
                    $updateStmt->bindParam(':customer_id', $customer_id);
                    $updateStmt->execute();
                    
                    // Update session data
                    $_SESSION['customer_name'] = $name;
                    $_SESSION['customer_email'] = $email;
                    
                    $message = "Profile updated successfully.";
                    $messageType = "success";
                    
                    // Refresh customer data
                    $customerStmt->execute();
                    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        }
        
        // Fetch bookings
        $bookingsStmt = $db->prepare("
            SELECT b.*, r.room_number, r.room_type, r.price, 
                   h.name as hotel_name, h.address as hotel_address, h.star_rating
            FROM Booking b
            JOIN Room r ON b.room_id = r.room_id
            JOIN Hotel h ON r.hotel_id = h.hotel_id
            WHERE b.customer_id = :customer_id
            ORDER BY b.start_date DESC
        ");
        $bookingsStmt->bindParam(':customer_id', $customer_id);
        $bookingsStmt->execute();
        $bookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Handle booking cancellation
        if ($action === 'cancel' && $booking_id) {
            // Check if booking exists and belongs to the customer
            $checkBookingStmt = $db->prepare("
                SELECT * FROM Booking
                WHERE booking_id = :booking_id AND customer_id = :customer_id
            ");
            $checkBookingStmt->bindParam(':booking_id', $booking_id);
            $checkBookingStmt->bindParam(':customer_id', $customer_id);
            $checkBookingStmt->execute();
            
            if ($booking = $checkBookingStmt->fetch(PDO::FETCH_ASSOC)) {
                // Check if check-in date is at least 48 hours away
                $startDate = new DateTime($booking['start_date']);
                $now = new DateTime();
                $interval = $now->diff($startDate);
                $hoursUntilCheckIn = ($interval->days * 24) + $interval->h;
                
                if ($hoursUntilCheckIn >= 48) {
                    // Cancel booking
                    $cancelStmt = $db->prepare("
                        DELETE FROM Booking
                        WHERE booking_id = :booking_id
                    ");
                    $cancelStmt->bindParam(':booking_id', $booking_id);
                    $cancelStmt->execute();
                    
                    $message = "Booking cancelled successfully.";
                    $messageType = "success";
                    
                    // Refresh bookings list
                    $bookingsStmt->execute();
                    $bookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $message = "Cancellation failed. Bookings can only be cancelled at least 48 hours before check-in.";
                    $messageType = "danger";
                }
            } else {
                $message = "Invalid booking selection.";
                $messageType = "danger";
            }
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Process logout
if ($action === 'logout') {
    // Clear session variables
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['user_role']);
    
    // Redirect to login page
    header("Location: account.php");
    exit;
}
?>

<div class="container my-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$customer_id): ?>
        <!-- Login/Register Forms -->
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h4 mb-0">Login</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="sin" class="form-label">SIN (9 digits)</label>
                                <input type="text" class="form-control" id="sin" name="sin" pattern="[0-9]{9}" required>
                                <div class="form-text">We use SIN for identity verification.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h2 class="h4 mb-0">Register</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="reg_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="reg_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="mb-3">
                                <label for="reg_sin" class="form-label">SIN (9 digits)</label>
                                <input type="text" class="form-control" id="reg_sin" name="sin" pattern="[0-9]{9}" required>
                                <div class="form-text">We'll never share your SIN with anyone else.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-secondary">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- User Account Management -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0">Account Menu</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="account.php" class="list-group-item list-group-item-action <?php echo $action === 'view' ? 'active' : ''; ?>">
                            <i class="bi bi-house-door me-2"></i> Dashboard
                        </a>
                        <a href="account.php?action=profile" class="list-group-item list-group-item-action <?php echo $action === 'profile' ? 'active' : ''; ?>">
                            <i class="bi bi-person me-2"></i> Profile
                        </a>
                        <a href="account.php?action=bookings" class="list-group-item list-group-item-action <?php echo $action === 'bookings' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-check me-2"></i> My Bookings
                        </a>
                        <a href="account.php?action=logout" class="list-group-item list-group-item-action text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <?php if ($action === 'profile'): ?>
                    <!-- Profile Edit Form -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0">Edit Profile</h2>
                        </div>
                        <div class="card-body">
                            <form method="post" action="account.php?action=profile">
                                <div class="mb-3">
                                    <label for="profile_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="profile_name" name="name" 
                                           value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="profile_email" name="email" 
                                           value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="profile_address" name="address" 
                                           value="<?php echo htmlspecialchars($customer['address']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_sin" class="form-label">SIN</label>
                                    <input type="text" class="form-control" id="profile_sin" 
                                           value="<?php echo htmlspecialchars($customer['sin']); ?>" disabled>
                                    <div class="form-text">SIN cannot be changed.</div>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                
                <?php elseif ($action === 'bookings'): ?>
                    <!-- Bookings List -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0">My Bookings</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($bookings)): ?>
                                <div class="alert alert-info">
                                    You don't have any bookings yet. <a href="index.php" class="alert-link">Start searching</a> for hotels now!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Hotel</th>
                                                <th>Room</th>
                                                <th>Dates</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <?php 
                                                    $startDate = new DateTime($booking['start_date']);
                                                    $endDate = new DateTime($booking['end_date']);
                                                    $now = new DateTime();
                                                    
                                                    if ($now > $endDate) {
                                                        $status = 'Completed';
                                                        $statusClass = 'success';
                                                    } elseif ($now >= $startDate) {
                                                        $status = 'Active';
                                                        $statusClass = 'primary';
                                                    } else {
                                                        $status = 'Upcoming';
                                                        $statusClass = 'info';
                                                    }
                                                    
                                                    $canCancel = ($status === 'Upcoming' && $startDate->diff($now)->days >= 2);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($booking['hotel_name']); ?>
                                                        <span class="text-warning">
                                                            <?php for($i = 0; $i < $booking['star_rating']; $i++): ?>★<?php endfor; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        Room #<?php echo htmlspecialchars($booking['room_number']); ?><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['room_type']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - 
                                                        <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                                    </td>
                                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                                    <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                                    <td>
                                                        <?php if ($canCancel): ?>
                                                            <a href="account.php?action=cancel&booking_id=<?php echo $booking['booking_id']; ?>" 
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                                Cancel
                                                            </a>
                                                        <?php else: ?>
                                                            <?php if ($status === 'Upcoming'): ?>
                                                                <small class="text-muted">Cannot cancel within 48h of check-in</small>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                
                <?php else: ?>
                    <!-- Dashboard -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h2 class="h4 mb-0">Welcome, <?php echo htmlspecialchars($customer['name']); ?>!</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="h5">Account Type</h3>
                                            <p class="display-6 mb-0">Customer</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="h5">Total Bookings</h3>
                                            <p class="display-6 mb-0"><?php echo count($bookings); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="h5">Active Bookings</h3>
                                            <p class="display-6 mb-0">
                                                <?php 
                                                    $activeCount = 0;
                                                    $now = new DateTime();
                                                    foreach ($bookings as $booking) {
                                                        $startDate = new DateTime($booking['start_date']);
                                                        $endDate = new DateTime($booking['end_date']);
                                                        if ($now >= $startDate && $now <= $endDate) {
                                                            $activeCount++;
                                                        }
                                                    }
                                                    echo $activeCount;
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="h5 mt-4">Quick Actions</h3>
                            <div class="row">
                                <div class="col-md-4">
                                    <a href="index.php" class="btn btn-outline-primary d-block mb-3">
                                        <i class="bi bi-search me-2"></i> Search Hotels
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="account.php?action=bookings" class="btn btn-outline-primary d-block mb-3">
                                        <i class="bi bi-calendar-check me-2"></i> View My Bookings
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="account.php?action=profile" class="btn btn-outline-primary d-block mb-3">
                                        <i class="bi bi-pencil me-2"></i> Edit My Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Bookings -->
                    <?php if (!empty($bookings)): ?>
                        <div class="card shadow-sm">
                            <div class="card-header bg-secondary text-white">
                                <h3 class="h5 mb-0">Recent Bookings</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Hotel</th>
                                                <th>Room</th>
                                                <th>Dates</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $recentBookings = array_slice($bookings, 0, 3); // Show only 3 recent bookings
                                            foreach ($recentBookings as $booking): 
                                            ?>
                                                <?php 
                                                    $startDate = new DateTime($booking['start_date']);
                                                    $endDate = new DateTime($booking['end_date']);
                                                    $now = new DateTime();
                                                    
                                                    if ($now > $endDate) {
                                                        $status = 'Completed';
                                                        $statusClass = 'success';
                                                    } elseif ($now >= $startDate) {
                                                        $status = 'Active';
                                                        $statusClass = 'primary';
                                                    } else {
                                                        $status = 'Upcoming';
                                                        $statusClass = 'info';
                                                    }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($booking['hotel_name']); ?>
                                                        <span class="text-warning">
                                                            <?php for($i = 0; $i < $booking['star_rating']; $i++): ?>★<?php endfor; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        Room #<?php echo htmlspecialchars($booking['room_number']); ?><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['room_type']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - 
                                                        <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                                                    </td>
                                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                                    <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="account.php?action=bookings" class="btn btn-sm btn-outline-secondary">View All</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?> 