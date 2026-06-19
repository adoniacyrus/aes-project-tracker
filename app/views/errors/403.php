<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - AES Project Tracker</title>
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
        .error-card {
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(101, 109, 119, 0.16);
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: #d63939;
            line-height: 1;
        }
    </style>
</head>
<body>
<div class="container text-center px-4">
    <div class="card error-card mx-auto p-5">
        <div class="card-body">
            <div class="error-code mb-3">403</div>
            <h1 class="h3 mb-2">Access Denied</h1>
            <p class="text-secondary mb-4">
                Oops... You do not have permission to access this page. If you believe this is an error, please contact the administrator.
            </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="?page=dashboard" class="btn btn-primary px-4">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
