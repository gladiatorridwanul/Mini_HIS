<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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
            color: #dc3545;
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
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon"><i class="fas fa-lock fa-3x text-danger"></i></div>
        <div class="error-code">403</div>
        <h3>Access Denied</h3>
        <p class="text-muted">You don't have permission to access this page.</p>
        <a href="/unidia/public/admin/dashboard" class="btn-home">
            <i class="fas fa-home me-2"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>