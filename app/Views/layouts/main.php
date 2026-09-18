<?php
$currentPath = trim(service('uri')->getPath(), '/');
$navActive = static function (string ...$prefixes) use ($currentPath): string {
    foreach ($prefixes as $prefix) {
        if ($currentPath === $prefix || str_starts_with($currentPath, $prefix . '/')) {
            return ' active';
        }
    }

    return '';
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= esc($title ?? 'JNG Apply Portal') ?></title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/applicant-portal.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-jng sticky-top" aria-label="Applicant portal navigation">
    <div class="container portal-container">
        <a class="navbar-brand" href="<?= session()->get('applicant_id') ? site_url('dashboard') : site_url('jobs') ?>">
            <span class="brand-mark"><i class="bi bi-people-fill"></i></span>
            <span>Joy~Nostalg <strong>Apply</strong></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link<?= $navActive('jobs') ?>" href="<?= site_url('jobs') ?>"><i class="bi bi-briefcase"></i><span>Jobs</span></a></li>

                <?php if (session()->get('applicant_id')): ?>
                    <li class="nav-item"><a class="nav-link<?= $navActive('saved-jobs') ?>" href="<?= site_url('saved-jobs') ?>"><i class="bi bi-bookmark"></i><span>Saved</span></a></li>
                    <li class="nav-item"><a class="nav-link<?= $navActive('applications') ?>" href="<?= site_url('applications') ?>"><i class="bi bi-file-earmark-text"></i><span>Applications</span></a></li>
                    <li class="nav-item"><a class="nav-link<?= $navActive('dashboard') ?>" href="<?= site_url('dashboard') ?>"><i class="bi bi-grid"></i><span>Dashboard</span></a></li>
                    <li class="nav-item"><a class="nav-link<?= $navActive('profile') ?>" href="<?= site_url('profile') ?>"><i class="bi bi-person"></i><span>Profile</span></a></li>
                    <li class="nav-item">
                        <a class="nav-link notification-link<?= $navActive('notifications') ?>" href="<?= site_url('notifications') ?>">
                            <i class="bi bi-bell"></i>
                            <span>Notifications</span>
                            <?php if ((int) ($navbarUnreadNotifications ?? 0) > 0): ?>
                                <span class="notification-count"><?= (int) $navbarUnreadNotifications > 99 ? '99+' : (int) $navbarUnreadNotifications ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-navbar" href="<?= site_url('logout') ?>"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2"><a class="btn btn-navbar btn-navbar-accent" href="<?= site_url('login') ?>"><i class="bi bi-person-circle"></i><span>Applicant sign in</span></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="page-shell">
    <main class="container portal-container portal-main">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success portal-alert" role="alert"><i class="bi bi-check-circle-fill"></i><span><?= esc(session()->getFlashdata('message')) ?></span></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger portal-alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><span><?= esc(session()->getFlashdata('error')) ?></span></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger portal-alert align-items-start" role="alert">
                <i class="bi bi-exclamation-circle-fill mt-1"></i>
                <div><?php foreach ((array) session()->getFlashdata('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?></div>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </main>

    <footer class="container portal-container portal-footer">
        <span>&copy; <?= date('Y') ?> Joy-Nostalg Group</span>
        <span class="footer-dot">&middot;</span>
        <span>Applicant information is processed for recruitment purposes.</span>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
