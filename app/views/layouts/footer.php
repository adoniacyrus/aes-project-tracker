        </div> <!-- Page Body Container End -->
        
        <!-- Footer -->
        <footer class="footer bg-white border-top py-3 mt-auto">
            <div class="container-fluid px-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                        <span class="text-secondary fs-7">&copy; <?php echo date('Y'); ?> <a href="?page=dashboard" class="text-decoration-none text-primary font-weight-medium">AES Project Tracker</a>. All rights reserved.</span>
                    </div>
                    <div class="col-12 col-md-6 text-center text-md-end">
                        <span class="text-secondary fs-7">Version 1.0.0 (Core PHP MVC)</span>
                    </div>
                </div>
            </div>
        </footer>
    </div> <!-- Page Wrapper End -->
</div> <!-- Main Wrapper End -->

<!-- Bootstrap Bundle with Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Mobile Sidebar Actions Toggle -->
<script>
    function toggleSidebarMobile() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('mobile-open');
    }
    
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 6000);
        });
    });
</script>
</body>
</html>
