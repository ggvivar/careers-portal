<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<a href="<?= site_url('jobs') ?>" class="back-link"><i class="bi bi-arrow-left"></i> Back to jobs</a>

<div class="job-detail-grid">
    <article class="job-detail-card">
        <header class="job-detail-header">
            <div class="company-avatar company-avatar-lg"><i class="bi bi-building"></i></div>
            <div>
                <span class="job-code"><?= esc($job['job_code'] ?: 'Job opening') ?></span>
                <h1><?= esc($job['title']) ?></h1>
                <p><?= esc($job['company_name'] ?: 'Joy-Nostalg Group') ?><?php if ($job['department_name']): ?> <span>&middot;</span> <?= esc($job['department_name']) ?><?php endif; ?></p>
            </div>
        </header>

        <div class="detail-meta-grid">
            <?php if ($job['location']): ?><div><i class="bi bi-geo-alt"></i><span><small>Location</small><?= esc($job['location']) ?></span></div><?php endif; ?>
            <?php if ($job['employment_type']): ?><div><i class="bi bi-clock"></i><span><small>Job type</small><?= esc($job['employment_type']) ?></span></div><?php endif; ?>
            <?php if ($job['salary_label']): ?><div><i class="bi bi-cash-stack"></i><span><small>Salary range</small><?= esc($job['salary_label']) ?></span></div><?php endif; ?>
            <?php if ($job['experience_label']): ?><div><i class="bi bi-briefcase"></i><span><small>Experience</small><?= esc($job['experience_label']) ?></span></div><?php endif; ?>
            <?php if ($job['valid_until']): ?><div><i class="bi bi-calendar3"></i><span><small>Apply until</small><?= esc(date('M d, Y', strtotime($job['valid_until']))) ?></span></div><?php endif; ?>
        </div>

        <?php foreach (['description' => 'About the role', 'responsibilities' => 'Key responsibilities', 'requirements' => 'What we are looking for'] as $field => $heading): ?>
            <?php if (trim((string) $job[$field]) !== ''): ?>
                <section class="job-content-section">
                    <h2><?= esc($heading) ?></h2>
                    <div><?= nl2br(esc($job[$field])) ?></div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    </article>

    <aside class="job-action-card">
        <div class="job-action-card-inner">
            <span class="action-icon"><i class="bi bi-send"></i></span>
            <h2>Interested in this role?</h2>
            <p>Submit your profile and application to the recruitment team.</p>

            <?php if ($alreadyApplied): ?>
                <a href="<?= site_url('applications') ?>" class="btn btn-applied w-100"><i class="bi bi-check-circle-fill"></i> View my application</a>
            <?php else: ?>
                <a href="<?= site_url('apply/' . $job['id']) ?>" class="btn btn-jng w-100"><i class="bi bi-send"></i> Apply now</a>
            <?php endif; ?>

            <?php if (session()->get('applicant_id')): ?>
                <form method="post" action="<?= site_url('jobs/' . $job['id'] . '/' . ($saved ? 'unsave' : 'save')) ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <button class="btn btn-details w-100"><i class="bi <?= $saved ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i> <?= $saved ? 'Saved' : 'Save for later' ?></button>
                </form>
            <?php endif; ?>

            <div class="action-note"><i class="bi bi-shield-check"></i><span>Your information is securely processed for recruitment purposes.</span></div>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
