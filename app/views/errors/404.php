<?php if (!isset($pageTitle)): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found - AES Project Tracker</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    </style>
</head>
<body>
<?php endif; ?>

<div class="container text-center px-4 py-5">
    <div class="card mx-auto p-5 border-0 shadow-sm" style="max-width: 500px; width: 100%; border: 1px solid rgba(101, 109, 119, 0.16); border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
        <div class="card-body">
            <div class="mb-3" style="font-size: 5rem; font-weight: 700; color: #206bc4; line-height: 1;">404</div>
            <h1 class="h3 mb-2 font-weight-bold">Page Not Found</h1>
            <p class="text-secondary mb-4">
                Oops... The page you are looking for could not be found or has been moved.
            </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="<?php echo route('dashboard'); ?>" class="btn btn-primary px-4">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (!isset($pageTitle)): ?>
</body>
</html>
<?php endif; ?>
