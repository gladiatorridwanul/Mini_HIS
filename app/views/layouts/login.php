<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Login'; ?> - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .login-card .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-card .logo i {
            font-size: 48px;
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            padding: 15px;
            border-radius: 15px;
        }
        .login-card .logo h4 {
            margin-top: 15px;
            font-weight: 700;
            color: #1a202c;
        }
        .login-card .logo p {
            color: #718096;
            font-size: 14px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            color: white;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .alert {
            border-radius: 10px;
            font-size: 14px;
        }
        .form-check-label {
            font-size: 14px;
            color: #4a5568;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #a0aec0;
        }
        .footer-text a {
            color: #667eea;
            text-decoration: none;
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            border: 2px solid #e2e8f0;
            border-right: none;
            background: white;
        }
        .input-group .form-control {
            border-radius: 0 10px 10px 0;
            border-left: none;
        }
        .input-group .form-control:focus {
            border-color: #667eea;
        }
        .input-group:focus-within .input-group-text {
            border-color: #667eea;
        }
        .input-group:focus-within .form-control {
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="fas fa-hospital"></i>
            <h4>UniDia HMS</h4>
            <p>Healthcare Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>/login">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" name="email" class="form-control" placeholder="admin@unidia.com" value="admin@unidia.com" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" value="password" required>
                </div>
            </div>
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="#" class="text-decoration-none small" style="color: #667eea;">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In
            </button>
        </form>
        
        <div class="footer-text">
            &copy; <?php echo date('Y'); ?> UniDia HMS. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>