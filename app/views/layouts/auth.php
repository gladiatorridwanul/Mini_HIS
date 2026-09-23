<?php
// app/views/layouts/auth.php
// Minimal layout for login/registration pages
?>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            max-width: 420px;
            width: 100%;
            padding: 20px;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        .auth-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-logo h3 {
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        .auth-logo h3 span {
            color: #10b981;
        }
        .auth-logo p {
            color: #94a3b8;
            font-size: 14px;
        }
        .auth-divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }
        .auth-divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #94a3b8;
            font-size: 13px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
        }
        .btn-login {
            background: #10b981;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
        }
        .demo-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #64748b;
            border: 1px dashed #e2e8f0;
            text-align: center;
        }
        .demo-info strong {
            color: #1e293b;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            font-size: 12px;
        }
        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: #334155;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <?php echo $content; ?>
        </div>
        <div class="footer-text">
            © <?php echo date('Y'); ?> BCPCC. All rights reserved.
        </div>
    </div>
</body>
</html>