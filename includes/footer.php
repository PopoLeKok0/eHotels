<?php
/**
 * e-Hotels Footer
 * Based on Deliverable 1 by Mouad Ben lahbib (300259705) and Xinyuan Zhou (300233463)
 */
?>

    </div> <!-- End of main container -->

    <!-- Footer -->
    <footer class="footer mt-auto py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5>e-Hotels</h5>
                    <p class="text-muted">Your trusted platform for hotel bookings across multiple hotel chains.</p>
                    <p class="small text-muted">
                        &copy; <?php echo date('Y'); ?> e-Hotels. All rights reserved.
                    </p>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <h5>For Guests</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50">Find Hotels</a></li>
                        <li><a href="search.php" class="text-white-50">Search Rooms</a></li>
                        <li><a href="account.php" class="text-white-50">My Account</a></li>
                        <li><a href="#" class="text-white-50">Help Center</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <h5>For Hotels</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50">Partner Program</a></li>
                        <li><a href="#" class="text-white-50">Advertise</a></li>
                        <li><a href="employee-login.php" class="text-white-50">Employee Portal</a></li>
                        <li><a href="#" class="text-white-50">Resources</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="d-flex gap-2 mb-3">
                        <a href="#" class="text-white-50"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-white-50"><i class="bi bi-twitter fs-5"></i></a>
                        <a href="#" class="text-white-50"><i class="bi bi-instagram fs-5"></i></a>
                        <a href="#" class="text-white-50"><i class="bi bi-linkedin fs-5"></i></a>
                    </div>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email" aria-label="Subscribe to newsletter">
                        <button class="btn btn-primary" type="button">Subscribe</button>
                    </div>
                    <p class="small text-muted mt-2">Subscribe to our newsletter for updates.</p>
                </div>
            </div>
            
            <hr class="border-secondary">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <ul class="list-inline mb-2 mb-md-0">
                    <li class="list-inline-item"><a href="#" class="text-white-50 small">Privacy Policy</a></li>
                    <li class="list-inline-item"><a href="#" class="text-white-50 small">Terms of Service</a></li>
                    <li class="list-inline-item"><a href="#" class="text-white-50 small">Cookie Policy</a></li>
                </ul>
                <p class="text-muted small mb-0">Based on Database Systems course project</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Icons CSS should ideally be in the <head> but keep here for now if needed -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript (load AFTER Bootstrap) -->
    <script src="js/script.js"></script>

</body>
</html> 