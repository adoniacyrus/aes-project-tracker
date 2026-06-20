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
    <link href="<?php echo BASE_URL; ?>/public/assets/css/custom.css" rel="stylesheet">
    
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

<div class="main-wrapper">
    <!-- Include Sidebar -->
    <?php include_once __DIR__ . '/sidebar.php'; ?>

    <!-- Page Wrapper (holds topnav, body, footer) -->
    <div class="page-wrapper">
        <!-- Include Navbar -->
        <?php include_once __DIR__ . '/navbar.php'; ?>

        <!-- Page Body Content Area -->
        <div class="page-body container-fluid px-4">
            
            <!-- Global Flash Alert Messages -->
            <?php 
            $alertTypes = ['success', 'danger', 'warning', 'info'];
            foreach ($alertTypes as $type): 
                if (has_flash_message($type)): 
                    $bootstrapAlert = ($type === 'danger') ? 'danger' : (($type === 'success') ? 'success' : (($type === 'warning') ? 'warning' : 'info'));
                    $alertIcon = ($type === 'danger') ? 'alert-triangle' : (($type === 'success') ? 'circle-check' : (($type === 'warning') ? 'info-circle' : 'info-circle'));
            ?>
                <div class="alert alert-<?php echo $bootstrapAlert; ?> alert-dismissible fade show d-flex align-items-center shadow-sm border-0 mb-4" role="alert">
                    <i class="ti ti-<?php echo $alertIcon; ?> fs-3 me-2"></i>
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
