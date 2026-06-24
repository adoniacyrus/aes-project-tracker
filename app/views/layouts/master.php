<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? 'AES Project Tracker'); ?> - AES Project Tracker</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tabler Icons Webfont -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.36.0/tabler-icons.min.css">
    
    <!-- Custom Theme Styling -->
    <link href="<?php echo BASE_URL; ?>/public/assets/css/custom.css?v=<?php echo @filemtime(__DIR__ . '/../../../public/assets/css/custom.css') ?: time(); ?>" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<!-- Global Toast Container -->
<div id="toast-container"></div>

<!-- Global Loading Overlay -->
<div id="loading-overlay">
    <div class="spinner-modern"></div>
</div>

<!-- Global destructive-action confirmation modal -->
<div class="modal fade" id="aesConfirmModal" tabindex="-1" aria-labelledby="aesConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title font-weight-semibold" id="aesConfirmModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-0 text-secondary" id="aesConfirmModalMessage">Are you sure you want to continue?</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="aesConfirmModalConfirm">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="main-wrapper">
    <!-- Include Sidebar -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>

    <!-- Page Wrapper (holds topnav, body, footer) -->
    <div class="page-wrapper">
        <!-- Include Navbar -->
        <?php include_once __DIR__ . '/navbar.php'; ?>

        <!-- Page Body Content Area -->
        <div class="page-body container-fluid">
            
            <!-- Global Flash Alert Messages -->
            <?php 
            $alertTypes = ['success', 'danger', 'warning', 'info'];
            foreach ($alertTypes as $type): 
                if (has_flash_message($type)): 
                    $bootstrapAlert = ($type === 'danger') ? 'danger' : (($type === 'success') ? 'success' : (($type === 'warning') ? 'warning' : 'info'));
                    $alertIcon = ($type === 'danger') ? 'alert-triangle' : (($type === 'success') ? 'circle-check' : (($type === 'warning') ? 'info-circle' : 'info-circle'));
            ?>
                <div class="alert alert-<?php echo $bootstrapAlert; ?> alert-dismissible fade show d-flex align-items-center shadow-sm border-0 mb-3" role="alert">
                    <i class="ti ti-<?php echo $alertIcon; ?> fs-4 me-2"></i>
                    <div>
                        <?php echo get_flash_message($type); ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php 
                endif; 
            endforeach; 
            ?>

            <!-- Render the actual Controller View Content -->
            <?php 
            if (isset($view) && file_exists($view)) {
                require_once $view;
            } else {
                echo '<div class="alert alert-danger">Error: Content view not found.</div>';
            }
            ?>
            
        <!-- Footer and script ends will close these containers inside footer.php -->
        <?php include_once __DIR__ . '/footer.php'; ?>
