<div class="auth-card">
    <div class="auth-brand">
        <span class="brand-mark">
            <img src="<?= url('assets/img/logo.png') ?>" alt="<?= e(config('app_name')) ?>" class="logo">
        </span>
        <div>
            <div class="brand-name"><?= e(config('app_name')) ?></div>
            <div class="brand-sub">Information Management System</div>
        </div>
    </div>

    <form method="post" action="<?= url('/login') ?>" novalidate>
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username"
                   class="form-control <?= errors('username') ? 'is-invalid' : '' ?>"
                   value="<?= e(old('username')) ?>" autofocus>
            <div class="invalid-feedback"><?= e(errors('username')) ?></div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password"
                   class="form-control <?= errors('password') ? 'is-invalid' : '' ?>">
            <div class="invalid-feedback"><?= e(errors('password')) ?></div>
        </div>
        <button class="btn btn-primary w-100">Log in</button>
        
        <div style="text-align: center; margin-top: 1.25rem;">
            <a href="<?= url('/') ?>" style="color: #0b3d2c; text-decoration: none; font-size: 0.875rem; font-weight: 500;">
                ← Back to Landing Page
            </a>
        </div>
    </form>
</div>