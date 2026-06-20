        </div> <!-- Page Body Container End -->
        
        <!-- Footer -->
        <footer class="footer bg-white border-top py-3 mt-auto">
            <div class="container-fluid px-4">
                <div class="row align-items-center justify-content-between">
                    <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                        <span class="text-secondary fs-7">&copy; <?php echo date('Y'); ?> <a href="<?php echo route('dashboard'); ?>" class="text-decoration-none text-primary font-weight-medium">AES Project Tracker</a>. All rights reserved.</span>
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

<!-- Mobile Sidebar Actions Toggle & Global AJAX Helpers -->
<script>
    // Global Toast helper
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        let iconClass = 'ti-circle-check';
        if (type === 'danger') iconClass = 'ti-alert-triangle';
        if (type === 'warning') iconClass = 'ti-info-circle';
        if (type === 'info') iconClass = 'ti-info-circle';

        const toast = document.createElement('div');
        toast.className = `toast-custom toast-${type}`;
        toast.innerHTML = `
            <i class="ti ${iconClass} toast-icon"></i>
            <div class="toast-content">${message}</div>
            <button type="button" class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    }

    // Show / Hide loading overlay
    function showLoader() {
        const loader = document.getElementById('loading-overlay');
        if (loader) loader.classList.add('show');
    }

    function hideLoader() {
        const loader = document.getElementById('loading-overlay');
        if (loader) loader.classList.remove('show');
    }

    // Intercept ajax forms
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        
        // HTML5 Validation check
        if (this.checkValidity() === false) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const $form = $(this);
        const url = $form.attr('action') || window.location.href;
        const method = $form.attr('method') || 'POST';
        const formData = new FormData(this);

        // Disable submit button and add loading spinner
        const $submitBtn = $form.find('[type="submit"]');
        const originalBtnHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...');

        showLoader();
        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                hideLoader();
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                if (response && response.success) {
                    showToast(response.message || 'Operation completed successfully!', 'success');
                    $form.closest('.modal').modal('hide');
                    
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showToast((response && response.message) ? response.message : 'An error occurred.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                hideLoader();
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                let errorMessage = 'An error occurred while processing your request.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) errorMessage = response.message;
                } catch(e) {}
                showToast(errorMessage, 'danger');
            }
        });
    });

    // Intercept ajax links
    $(document).on('click', '.ajax-link', function(e) {
        e.preventDefault();
        const $link = $(this);
        const confirmMessage = $link.attr('data-confirm');
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        showLoader();
        $.ajax({
            url: $link.attr('href'),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoader();
                if (response && response.success) {
                    showToast(response.message || 'Operation completed successfully!', 'success');
                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    showToast((response && response.message) ? response.message : 'An error occurred.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                hideLoader();
                let errorMessage = 'An error occurred while processing your request.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) errorMessage = response.message;
                } catch(e) {}
                showToast(errorMessage, 'danger');
            }
        });
    });

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
