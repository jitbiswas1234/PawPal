<?php if (isset($is_user_page) && $is_user_page): ?>
            </main> <!-- End user-content-area -->
            
            <!-- Compact User Footer -->
            <footer class="py-3 px-4 bg-white border-top mt-auto text-center text-muted small">
                &copy; <?php echo date('Y'); ?> PawPal. All rights reserved. &bull; Final Portfolio Project
            </footer>
        </div> <!-- End user-main-wrapper -->
    </div> <!-- End user-layout-wrapper -->
<?php else: ?>
        </div>
    </main>

    <!-- Footer Section -->
    <footer class="footer bg-dark text-white pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase mb-3 d-flex align-items-center">
                        <i class="fa-solid fa-paw text-accent me-2"></i>
                        <span class="brand-text">Paw<span class="text-accent">Pal</span></span>
                    </h5>
                    <p class="text-muted small">
                        PawPal is a virtual pet adoption and simulation platform. Adopt a cute pet, feed, groom, play, rest, and keep them healthy. Earn virtual coins and level up your pet care skills.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-3 hover-color-accent"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3 hover-color-accent"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="text-white me-3 hover-color-accent"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="text-white hover-color-accent"><i class="fa-brands fa-github"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase mb-3 small fw-bold tracking-wider text-accent">Quick Links</h5>
                    <ul class="list-unstyled mb-0 footer-links">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php" class="text-muted text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>/user/pets.php" class="text-muted text-decoration-none">Adopt Pet</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="text-muted text-decoration-none">Dashboard</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/user/shop.php" class="text-muted text-decoration-none">Pet Shop</a></li>
                        <?php else: ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/login.php" class="text-muted text-decoration-none">Login</a></li>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>/register.php" class="text-muted text-decoration-none">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase mb-3 small fw-bold tracking-wider text-accent">Caring Resources</h5>
                    <ul class="list-unstyled mb-0 footer-links">
                        <li class="mb-2"><span class="text-muted"><i class="fa-solid fa-bone me-2 text-accent"></i>Feed regularly</span></li>
                        <li class="mb-2"><span class="text-muted"><i class="fa-solid fa-circle-play me-2 text-accent"></i>Play daily</span></li>
                        <li class="mb-2"><span class="text-muted"><i class="fa-solid fa-soap me-2 text-accent"></i>Groom often</span></li>
                        <li class="mb-2"><span class="text-muted"><i class="fa-solid fa-kit-medical me-2 text-accent"></i>Check health</span></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-uppercase mb-3 small fw-bold tracking-wider text-accent">Subscribe</h5>
                    <p class="text-muted small">Subscribe to get daily reminders and pet care tips.</p>
                    <div class="input-group mb-3 rounded-pill overflow-hidden border-0">
                        <input type="email" class="form-control border-0 px-3 bg-custom-dark text-white placeholder-muted" placeholder="Email Address" aria-label="Email Address">
                        <button class="btn btn-accent border-0 px-3" type="button"><i class="fa-regular fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
            
            <hr class="border-secondary my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start small text-muted">
                    &copy; <?php echo date('Y'); ?> PawPal. All rights reserved.
                </div>
                <div class="col-md-6 text-center text-md-end small text-muted mt-2 mt-md-0">
                    Built for final-year portfolio &bull; Designed with <i class="fa-solid fa-heart text-danger"></i> &bull; PHP + MySQL + AJAX
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery for AJAX calls -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Canvas Confetti for special events (adoptions, level ups) -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/pet.js?v=<?php echo time(); ?>"></script>
</body>
</html>
