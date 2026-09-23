<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            text-align: center;
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .error-code {
            font-size: 100px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 80px;
            color: #764ba2;
            margin-bottom: 20px;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            color: white;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        <div class="error-code">404</div>
        <h3>Page Not Found</h3>
        <p class="text-muted">The page you're looking for doesn't exist or has been moved.</p>
        <a href="/unidia/public/login" class="btn-home">
            <i class="fas fa-home me-2"></i> Back to Login
        </a>
    </div>
</body>
</html>