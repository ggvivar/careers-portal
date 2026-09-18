<?php
$activeMode = session()->getFlashdata('mode') ?: ($mode ?? 'login');
if (! in_array($activeMode, ['login', 'forgot'], true)) $activeMode = 'login';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Applicant Login | JNG Recruitment Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{--jng-primary:#0a0147;--jng-yellow:#ffc91c;--jng-border:#dfe3eb;--jng-muted:#6b7280}
        html,body{min-height:100%}
        body{margin:0;background:#f8fafc;font-family:"Segoe UI",Arial,sans-serif}
        .left-side{position:relative;min-height:100vh;padding:50px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:radial-gradient(circle at 18% 18%,rgba(255,201,28,.16),transparent 31%),linear-gradient(145deg,#0a0147,#170b65);color:#fff;text-align:center}
        .left-side:before{content:"";position:absolute;width:330px;height:330px;top:-160px;right:-120px;border:48px solid rgba(255,255,255,.05);border-radius:50%}
        .brand-content{position:relative;z-index:1;max-width:540px}
        .brand-mark{width:90px;height:90px;margin:0 auto 28px;display:grid;place-items:center;border-radius:25px;background:var(--jng-yellow);color:var(--jng-primary);font-size:42px}
        .brand-content h1{font-size:clamp(2.2rem,4vw,3.7rem);font-weight:800}.brand-content h1 span{color:var(--jng-yellow)}
        .brand-content p{color:rgba(255,255,255,.72);line-height:1.75}
        .right-side{min-height:100vh;padding:40px 24px;display:flex;align-items:center;justify-content:center;background:#fff}
        .auth-box{width:100%;max-width:420px}.auth-title{color:var(--jng-primary);font-weight:800}
        .auth-panel{animation:fadePanel .22s ease}@keyframes fadePanel{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:none}}
        .form-floating>.form-control{min-height:58px;border-color:var(--jng-border);border-radius:12px;background:#fbfcfe}
        .form-control:focus{border-color:var(--jng-primary);box-shadow:0 0 0 .22rem rgba(10,1,71,.10)}
        .password-wrapper{position:relative}.password-wrapper .form-control{padding-right:55px}
        .password-toggle{position:absolute;top:50%;right:10px;z-index:10;width:40px;height:40px;border:0;border-radius:10px;background:transparent;color:var(--jng-muted);transform:translateY(-50%)}
        .btn-jng{min-height:54px;border:0;border-radius:12px;background:var(--jng-yellow);color:var(--jng-primary);font-weight:800}.btn-jng:hover{background:#e7b500;color:var(--jng-primary)}
        .link-button{padding:0;border:0;background:transparent;color:var(--jng-primary);font-size:13px;font-weight:700}
        .mobile-brand{display:none;text-align:center;margin-bottom:32px}@media(max-width:767.98px){.mobile-brand{display:block}}
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row g-0">
        <section class="col-md-6 d-none d-md-flex left-side">
            <div class="brand-content">
                <div class="brand-mark"><i class="bi bi-person-workspace"></i></div>
                <h1 id="leftTitle">Your next <span>opportunity</span> starts here.</h1>
                <p id="leftDescription">Create one applicant profile, apply to available positions, and track your recruitment progress.</p>
            </div>
        </section>

        <section class="col-12 col-md-6 right-side">
            <div class="auth-box">
                <div class="mobile-brand">
                    <div class="fw-bold fs-2" style="color:#0a0147">Joy~Nostalg Group</div>
                    <div class="text-muted">Applicant Portal</div>
                </div>

                <?php if (session()->getFlashdata('message')): ?><div class="alert alert-success border-0"><?= esc(session()->getFlashdata('message')) ?></div><?php endif; ?>
                <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger border-0"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger border-0">
                        <?php foreach ((array) session()->getFlashdata('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="loginPanel" class="auth-panel <?= $activeMode === 'login' ? '' : 'd-none' ?>">
                    <h1 class="auth-title h2">Applicant sign in</h1>
                    <p class="text-muted mb-4">Access your profile and job applications.</p>

                    <form method="post" action="<?= site_url('login') ?>" id="loginForm">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <input type="email" name="email" id="email" class="form-control" placeholder="Email address" value="<?= esc(old('email')) ?>" autocomplete="email" required autofocus>
                            <label for="email">Email address</label>
                        </div>

                        <div class="form-floating mb-3 password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" autocomplete="current-password" required>
                            <label for="password">Password</label>
                            <button type="button" class="password-toggle" id="togglePassword"><i class="bi bi-eye"></i></button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" <?= old('remember') ? 'checked' : '' ?>>
                                <label class="form-check-label small text-muted" for="remember">Remember me</label>
                            </div>
                            <button type="button" class="link-button" data-show-panel="forgot">Forgot password?</button>
                        </div>

                        <button type="submit" class="btn btn-jng w-100" data-loading-text="Signing in">
                            <span class="button-text">Sign in</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none"></span>
                        </button>
                    </form>

                    <p class="text-center text-muted mt-4 mb-2">New applicant? <a href="<?= site_url('register') ?>">Create an account</a></p>
                    <p class="text-center mb-0"><a href="<?= esc(config('Careers')->websiteURL) ?>">Back to careers website</a></p>
                </div>

                <div id="forgotPanel" class="auth-panel <?= $activeMode === 'forgot' ? '' : 'd-none' ?>">
                    <h1 class="auth-title h2">Reset password</h1>
                    <p class="text-muted mb-4">Enter your registered email address. We will send a secure reset link.</p>

                    <form method="post" action="<?= site_url('forgot-password') ?>" id="forgotForm">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <input type="email" name="forgot_email" id="forgotEmail" class="form-control" placeholder="Email address" value="<?= esc(old('forgot_email')) ?>" autocomplete="email" required>
                            <label for="forgotEmail">Email address</label>
                        </div>

                        <button type="submit" class="btn btn-jng w-100" data-loading-text="Processing">
                            <span class="button-text">Send reset link</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none"></span>
                        </button>
                        <button type="button" class="btn btn-light border w-100 mt-2" data-show-panel="login"><i class="bi bi-arrow-left me-1"></i>Back to sign in</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(() => {
    const loginPanel = document.getElementById('loginPanel');
    const forgotPanel = document.getElementById('forgotPanel');
    const leftTitle = document.getElementById('leftTitle');
    const leftDescription = document.getElementById('leftDescription');

    function showPanel(mode) {
        const forgot = mode === 'forgot';
        loginPanel.classList.toggle('d-none', forgot);
        forgotPanel.classList.toggle('d-none', !forgot);

        if (leftTitle) {
            leftTitle.innerHTML = forgot ? 'Recover your <span>account</span>.' : 'Your next <span>opportunity</span> starts here.';
            leftDescription.textContent = forgot
                ? 'Use your registered applicant email to securely create a new password.'
                : 'Create one applicant profile, apply to available positions, and track your recruitment progress.';
        }

        history.replaceState(null, '', forgot ? '?mode=forgot' : '<?= site_url('login') ?>');
        setTimeout(() => document.getElementById(forgot ? 'forgotEmail' : 'email')?.focus(), 50);
    }

    document.querySelectorAll('[data-show-panel]').forEach(button => {
        button.addEventListener('click', () => showPanel(button.dataset.showPanel));
    });

    document.getElementById('togglePassword')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        this.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (!button || !form.checkValidity()) return;
            button.disabled = true;
            button.querySelector('.button-text').textContent = button.dataset.loadingText || 'Processing';
            button.querySelector('.spinner-border')?.classList.remove('d-none');
        });
    });
})();
</script>
</body>
</html>
