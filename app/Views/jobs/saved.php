<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="page-heading-row">
    <div>
        <div class="eyebrow text-primary"><i class="bi bi-bookmark-heart"></i> Your shortlist</div>
        <h1 class="page-title">Saved jobs</h1>
        <p class="page-subtitle">Keep promising positions in one place and apply when you are ready.</p>
    </div>
    <a href="<?= site_url('jobs') ?>" class="btn btn-jng"><i class="bi bi-search"></i> Browse jobs</a>
</div>

<div class="row g-3">
    <?php if (! $jobs): ?>
        <div class="col-12">
            <div class="empty-panel">
                <span class="empty-icon"><i class="bi bi-bookmark-heart"></i></span>
                <h2>No saved jobs yet</h2>
                <p>Save a position from the jobs page to add it to your shortlist.</p>
                <a href="<?= site_url('jobs') ?>" class="btn btn-jng">Explore positions</a>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($jobs as $job): ?>
        <?php $jobId = (int) $job['id']; $applied = in_array($jobId, $appliedJobIds ?? [], true); ?>
        <div class="col-md-6 col-xl-4">
            <article class="job-card h-100">
                <div class="job-card-top">
                    <div class="company-avatar"><i class="bi bi-building"></i></div>
                    <div class="job-card-heading">
                        <span class="job-code"><?= esc($job['job_code'] ?: 'Job opening') ?></span>
                        <h3><a href="<?= site_url('jobs/' . $jobId) ?>"><?= esc($job['title']) ?></a></h3>
                    </div>
                    <form method="post" action="<?= site_url('jobs/' . $jobId . '/unsave') ?>">
                        <?= csrf_field() ?>
                        <button class="save-job-button is-saved" title="Remove saved job" aria-label="Remove saved job"><i class="bi bi-bookmark-fill"></i></button>
                    </form>
                </div>

                <div class="job-company"><?= esc($job['company_name'] ?: 'Joy-Nostalg Group') ?></div>
                <div class="job-meta">
                    <?php if ($job['location']): ?><span><i class="bi bi-geo-alt"></i><?= esc($job['location']) ?></span><?php endif; ?>
                    <?php if ($job['employment_type']): ?><span><i class="bi bi-clock"></i><?= esc($job['employment_type']) ?></span><?php endif; ?>
                    <?php if ($job['salary_label']): ?><span class="job-meta-highlight"><i class="bi bi-cash-stack"></i><?= esc($job['salary_label']) ?></span><?php endif; ?>
                    <?php if ($job['experience_label']): ?><span><i class="bi bi-briefcase"></i><?= esc($job['experience_label']) ?></span><?php endif; ?>
                </div>

                <div class="job-card-actions">
                    <?php if ($applied): ?>
                        <a href="<?= site_url('applications') ?>" class="btn btn-applied"><i class="bi bi-check-circle-fill"></i> Applied</a>
                    <?php else: ?>
                        <a href="<?= site_url('apply/' . $jobId) ?>" class="btn btn-jng"><i class="bi bi-send"></i> Apply now</a>
                    <?php endif; ?>
                    <a href="<?= site_url('jobs/' . $jobId) ?>" class="btn btn-details">View details</a>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
