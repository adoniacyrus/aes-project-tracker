<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AES Project Tracker</title>
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
        .word-break-all {
            word-break: break-all;
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
                <p class="text-secondary fs-6">Recover your password</p>
            </div>

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

            <!-- Developer Simulated Link Notice -->
            <?php 
            if (isset($_SESSION['simulated_reset_link'])): 
                $simulatedLink = $_SESSION['simulated_reset_link'];
                unset($_SESSION['simulated_reset_link']);
            ?>
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center mb-1">
                        <i class="ti ti-mail-forward fs-4 me-2 text-info"></i>
                        <strong class="fs-7">Simulated Email Inbox</strong>
                    </div>
                    <p class="fs-8 text-secondary mb-2">A reset mail request was logged to <code>storage/logs/mail.log</code>. For local testing, follow the link below:</p>
                    <a href="<?php echo $simulatedLink; ?>" class="btn btn-outline-info btn-sm w-100 word-break-all fs-8">Reset Password Now</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="?page=forgot-password">
                <!-- CSRF Token hidden field -->
                <?php echo csrf_field(); ?>

                <p class="text-secondary fs-7 mb-3">
                    Enter your email address and we will generate a link to reset your password.
                </p>

                <div class="mb-3">
                    <label class="form-label font-weight-medium">Email Address</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-mail"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-1" placeholder="Enter email" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary py-2 font-weight-semibold">
                        Send Reset Link
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="?page=login" class="fs-7 text-decoration-none text-primary"><i class="ti ti-arrow-left"></i> Back to Login</a>
                </div>
            </form>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
