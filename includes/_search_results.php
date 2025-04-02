<?php
/**
 * Search Results Partial
 * 
 * This file is included by search.php to render the room results.
 * It expects the following variables to be set:
 * - $rooms: Array of room data from the database query
 * - $total_results: Integer count of rooms
 * - $nights: Integer number of nights for the stay
 * - $start_date: String start date
 * - $end_date: String end date
 * - $loggedIn: Boolean indicating user login status
 * - $isCustomer: Boolean indicating if logged in user is a customer
 * - $isEmployee: Boolean indicating if logged in user is an employee
 */
?>

<!-- Update search summary -->
<script>
    const summaryElement = document.getElementById('searchSummary');
    if (summaryElement) {
        const nightsText = '<?= $nights ?>' + (<?= $nights ?> === 1 ? ' night' : ' nights');
        summaryElement.innerHTML = `<p class="mb-0 text-muted">
            Found <strong><?= $total_results ?></strong> room(s) available 
            from <?= date('M d, Y', strtotime($start_date)) ?> 
            to <?= date('M d, Y', strtotime($end_date)) ?> 
            (${nightsText}).
        </p>`;
    }
</script>

<?php if (empty($rooms)): ?>
    <div class="alert alert-info mt-3">
        <h4 class="alert-heading">No Rooms Available</h4>
        <p>Unfortunately, no rooms matched your search criteria for the selected dates and filters. Please try:</p>
        <ul>
            <li>Adjusting your check-in/check-out dates.</li>
            <li>Selecting a different area or hotel chain.</li>
            <li>Changing the required star rating or capacity.</li>
            <li>Widening your price range.</li>
        </ul>
    </div>
<?php else: ?>
    <?php foreach ($rooms as $room): ?>
        <div class="card mb-4 search-result-card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <h5 class="text-primary mb-1"><?= htmlspecialchars($room['Chain_Name']) ?></h5>
                        <div class="mb-2">
                            <?= str_repeat('<i class="fas fa-star text-warning"></i>', $room['Star_Rating']) ?>
                        </div>
                        <p class="mb-1"><small><strong>Hotel:</strong> <?= htmlspecialchars($room['Hotel_Address']) ?></small></p>
                        <p class="mb-0"><small><strong>Area:</strong> <?= htmlspecialchars($room['Area']) ?></small></p>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0 border-start border-end px-md-4">
                        <h6>Room #<?= htmlspecialchars($room['Room_Num']) ?></h6>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>Capacity:</strong> <?= htmlspecialchars($room['Capacity']) ?> <?= ($room['Capacity'] == 1) ? 'Person' : 'People' ?></li>
                            <li><strong>View:</strong> <?= htmlspecialchars($room['View_Type']) ?></li>
                            <li><strong>Extendable:</strong> <span class="badge <?= $room['Extendable'] ? 'bg-info text-dark' : 'bg-light text-muted' ?>"><?= $room['Extendable'] ? 'Yes' : 'No' ?></span></li>
                        </ul>
                         <p class="mt-2 mb-0 small text-muted">
                             <strong>Amenities:</strong> <?= htmlspecialchars($room['Amenities']) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="room-price mb-2">
                            <span class="fs-4 fw-bold">$<?= number_format($room['Price'], 2) ?></span>
                            <small class="text-muted">/ night</small>
                        </div>
                        <?php if ($nights > 0): ?>
                            <div class="total-price mb-3">
                                <small><strong>$<?= number_format($room['Price'] * $nights, 2) ?></strong> total (<?= $nights ?> nights)</small>
                            </div>
                        <?php endif; ?>
                        <?php 
                        // Construct booking/login URL
                        $bookingParams = http_build_query([
                            'hotel' => $room['Hotel_Address'],
                            'room' => $room['Room_Num'],
                            'start' => $start_date,
                            'end' => $end_date
                        ]);
                        $loginRedirect = 'login.php?redirect=' . urlencode("search.php?" . http_build_query($_GET)); // Preserve current filters on login
                        
                        if ($loggedIn && $isCustomer): ?>
                            <a href="booking.php?<?= $bookingParams ?>" class="btn btn-primary w-100">
                               <i class="fas fa-calendar-check me-1"></i> Book Now
                            </a>
                        <?php elseif ($loggedIn && $isEmployee): ?>
                            <a href="employee/direct_rental.php?<?= $bookingParams ?>" class="btn btn-success w-100 disabled" title="Employees rent via Employee Portal">
                               <i class="fas fa-key me-1"></i> Book (Employee)
                            </a>
                             <small class="d-block text-muted mt-1">Use Employee Portal for rentals.</small>
                        <?php else: ?>
                            <a href="<?= $loginRedirect ?>" class="btn btn-secondary w-100">
                               <i class="fas fa-sign-in-alt me-1"></i> Login to Book
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?> 