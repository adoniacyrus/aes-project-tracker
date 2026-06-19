<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AES Project Tracker - Login</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background-color:#f5f7fb;
        }

        .login-container{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .login-card{
            width:100%;
            max-width:420px;
            border:none;
            border-radius:12px;
            box-shadow:0 0 20px rgba(0,0,0,0.08);
        }

        .logo-title{
            font-weight:700;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-container">

        <div class="card login-card">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <h3 class="logo-title">
                        AES Project Tracker
                    </h3>
                    <p class="text-muted mb-0">
                        Sign in to continue
                    </p>
                </div>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter email"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter password"
                            required
                        >
                    </div>

                    <div class="d-grid">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Login
                        </button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="#">
                            Forgot Password?
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

</body>
</html>