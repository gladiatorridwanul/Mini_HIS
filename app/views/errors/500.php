<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-card { text-align: center; padding: 50px; }
        .error-code { font-size: 100px; font-weight: 700; color: #e74a3b; opacity: 0.4; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">500</div>
        <h3>Internal Server Error</h3>
        <p class="text-muted">Something went wrong on our end. Please try again later.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">Back to Home</a>
    </div>
</body>
</html>