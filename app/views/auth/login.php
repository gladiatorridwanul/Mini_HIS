<div class="auth-logo">
    <i class="fas fa-hospital"></i>
    <h3>BCPCC <span style="color: #10b981;"> Login</span></h3>
    <p>Bangladesh Cancer & Palliative Care Center</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- FIX: Use absolute URL for form action -->
<form method="POST" action="/unidia/public/do-login">
    <div class="mb-3">
        <label class="form-label fw-semibold">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="" value="" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="form-check">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <a href="#" class="text-decoration-none small" style="color: #667eea;">Forgot password?</a>
    </div>
    <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt me-2"></i> Sign In
    </button>
</form>