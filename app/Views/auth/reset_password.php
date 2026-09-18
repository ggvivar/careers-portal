<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create New Password | JNG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{min-height:100vh;display:grid;place-items:center;background:#f5f7fb;font-family:"Segoe UI",Arial,sans-serif}
        .card{width:min(92vw,460px);border:0;border-radius:20px;box-shadow:0 16px 45px rgba(10,1,71,.10)}
        .form-control{min-height:52px;border-radius:11px}.btn-jng{min-height:52px;border:0;border-radius:11px;background:#ffc91c;color:#0a0147;font-weight:800}
    </style>
</head>
<body>
<div class="card"><div class="card-body p-4 p-lg-5">
    <div class="fw-bold fs-3 mb-1" style="color:#0a0147">Create a new password</div>
    <p class="text-muted mb-4">Use at least 10 characters.</p>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger"><?php foreach ((array) session()->getFlashdata('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('reset-password') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token) ?>">
        <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required minlength="10"></div>
        <div class="mb-4"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required minlength="10"></div>
        <button class="btn btn-jng w-100">Save new password</button>
    </form>
</div></div>
</body>
</html>
