        </main>
        
        <!-- Admin Footer -->
        <footer class="py-3 px-4 bg-white border-top mt-auto">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-between">
                    <div class="col-md-6 text-center text-md-start text-muted small">
                        &copy; <?php echo date('Y'); ?> <strong>PawPal Admin Panel</strong>. All rights reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end text-muted small mt-2 mt-md-0">
                        System Version 1.1.0 &bull; Built with PHP &amp; MySQL
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- End Admin Wrapper -->

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery for AJAX calls -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <!-- Chart.js (Only loaded on reports page or if reports page is active, but loading globally here ensures standard chart analytics capability) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom Admin JavaScript -->
    <script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
