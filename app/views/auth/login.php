<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AES Project Tracker</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tabler Icons Webfont -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.36.0/tabler-icons.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f8fb;
            color: #1d273b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(101, 109, 119, 0.16);
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
        .logo-title {
            font-weight: 700;
            color: #206bc4;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="card login-card mx-auto">
        <div class="card-body p-4">
            
            <div class="text-center mb-4">
                <h2 class="logo-title mb-1">
                    <i class="ti ti-activity"></i> AES Tracker
                </h2>
                <p class="text-secondary fs-6">Sign in to your account</p>
            </div>

            <!-- Inactive or timeout notice -->
            <?php if (isset($_GET['inactive'])): ?>
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-alert-circle fs-4 me-2"></i>
                    <div class="fs-7">Your account was deactivated or not found.</div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-clock-hour-4 fs-4 me-2"></i>
                    <div class="fs-7">Your session has expired due to inactivity. Please sign in again.</div>
                </div>
            <?php endif; ?>

            <!-- Global Alert Messages -->
            <?php 
            require_once __DIR__ . '/../../helpers/helpers.php';
            $alertTypes = ['success', 'danger', 'warning'];
            foreach ($alertTypes as $type): 
                if (has_flash_message($type)): 
                    $bootstrapAlert = ($type === 'danger') ? 'danger' : (($type === 'success') ? 'success' : 'warning');
                    $alertIcon = ($type === 'danger') ? 'alert-triangle' : (($type === 'success') ? 'circle-check' : 'info-circle');
            ?>
                <div class="alert alert-<?php echo $bootstrapAlert; ?> alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-3" role="alert">
                    <i class="ti ti-<?php echo $alertIcon; ?> fs-4 me-2"></i>
                    <div class="fs-7"><?php echo get_flash_message($type); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php 
                endif; 
            endforeach; 
            ?>

            <form method="POST" action="<?php echo route('login'); ?>">
                <!-- CSRF Token hidden field -->
                <?php echo csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label font-weight-medium">Email Address</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-mail"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-1" placeholder="Enter your email address" required autocomplete="username">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0 font-weight-medium">Password</label>
                        <button type="button" class="btn btn-link fs-7 text-decoration-none text-primary p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</button>
                    </div>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-1" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary py-2 font-weight-semibold">
                        Sign In
                    </button>
                </div>
            </form>
            
            <div class="mt-4 pt-3 border-top text-center text-secondary fs-7">
                AES Project Tracker & Management System
            </div>
            
        </div>
    </div>
</div>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="forgotPasswordForm" method="POST" action="<?php echo route('forgot-password'); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title font-weight-semibold" id="forgotPasswordModalLabel">Forgot Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div id="forgotPasswordAlert" class="alert d-none mb-3" role="alert"></div>
                    <p class="text-secondary fs-7 mb-3">Enter your account email address. A temporary password will be emailed to you.</p>
                    <label class="form-label font-weight-medium">Email Address</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-mail"></i></span>
                        <input type="email" name="email" id="forgotPasswordEmail" class="form-control border-start-0 ps-1" placeholder="Enter your email address" required autocomplete="email">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="forgotPasswordSubmit">Send Temporary Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const form = document.getElementById('forgotPasswordForm');
    const alertBox = document.getElementById('forgotPasswordAlert');
    const submitBtn = document.getElementById('forgotPasswordSubmit');
    const modalEl = document.getElementById('forgotPasswordModal');

    function showForgotAlert(message, type) {
        alertBox.className = 'alert alert-' + type + ' mb-3';
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    function hideForgotAlert() {
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            hideForgotAlert();
            form.reset();
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Temporary Password';
        });
        modalEl.addEventListener('show.bs.modal', hideForgotAlert);
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            hideForgotAlert();
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Temporary Password';

                if (!data) {
                    showForgotAlert('An unexpected error occurred. Please try again.', 'danger');
                    return;
                }

                showForgotAlert(data.message || 'Request processed.', data.success ? 'success' : 'danger');

                if (data.success) {
                    setTimeout(function() {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    }, 2500);
                }
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Temporary Password';
                showForgotAlert('An error occurred while sending your request. Please try again.', 'danger');
            });
        });
    }
})();
</script>
</body>
</html>