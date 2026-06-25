<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - AES Project Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .change-password-card {
            width: 100%;
            max-width: 480px;
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
    <div class="card change-password-card mx-auto">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h2 class="logo-title mb-1">
                    <i class="ti ti-key"></i> Change Password
                </h2>
                <p class="text-secondary fs-6 mb-0">You must set a new password before continuing.</p>
            </div>

            <?php
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

            <form method="POST" action="<?php echo route('auth-change-password'); ?>">
                <?php echo csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label font-weight-medium">Current Temporary Password</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-lock"></i></span>
                        <input type="password" name="current_password" class="form-control border-start-0 ps-1" placeholder="Enter your temporary password" required autocomplete="current-password">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-medium">New Password</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-lock-open"></i></span>
                        <input type="password" name="new_password" class="form-control border-start-0 ps-1" placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="form-text">Must include uppercase, lowercase, number, and special character.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-medium">Confirm Password</label>
                    <div class="input-group input-group-flat">
                        <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-lock-check"></i></span>
                        <input type="password" name="confirm_password" class="form-control border-start-0 ps-1" placeholder="Confirm your new password" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="d-flex justify-content-between gap-2">
                    <a href="<?php echo route('logout'); ?>" class="btn btn-outline-secondary px-4">Cancel Logout</a>
                    <button type="submit" class="btn btn-primary px-4">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
