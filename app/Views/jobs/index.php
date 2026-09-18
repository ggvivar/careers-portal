<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<section class="jobs-hero">
    <div>
        <div class="eyebrow"><i class="bi bi-stars"></i> Careers at Joy~Nostalg</div>
        <h1>Find your next opportunity</h1>
        <p>Explore <?= number_format((int) ($result['total'] ?? 0)) ?> open position<?= (int) ($result['total'] ?? 0) === 1 ? '' : 's' ?> across the group.</p>
    </div>

    <?php if (session()->get('applicant_id')): ?>
        <a href="<?= site_url('saved-jobs') ?>" class="btn btn-surface"><i class="bi bi-bookmark-heart"></i> Saved jobs</a>
    <?php endif; ?>
</section>

<section class="filter-card" aria-label="Job search filters">
    <form method="get" class="row g-2 align-items-center">
        <div class="col-lg-5">
            <div class="input-icon-wrap">
                <i class="bi bi-search"></i>
                <input type="search" name="q" class="form-control" placeholder="Job title, code, or keyword" value="<?= esc($filters['q']) ?>">
            </div>
        </div>
        <div class="col-md-5 col-lg-3">
            <div class="input-icon-wrap">
                <i class="bi bi-geo-alt"></i>
                <input type="text" name="location" class="form-control" placeholder="Location" value="<?= esc($filters['location']) ?>">
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <select name="type" class="form-select" aria-label="Employment type">
                <option value="">All job types</option>
                <?php foreach (['Full-time', 'Part-time', 'Contract', 'Internship'] as $type): ?>
                    <option value="<?= esc($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= esc($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 col-lg-2 d-grid">
            <button class="btn btn-jng"><i class="bi bi-sliders2"></i> Search</button>
        </div>
    </form>
</section>

<div class="results-header">
    <div>
        <h2>Available positions</h2>
        <p><?= number_format((int) ($result['total'] ?? 0)) ?> result<?= (int) ($result['total'] ?? 0) === 1 ? '' : 's' ?></p>
    </div>
    <?php if (array_filter($filters, static fn ($value): bool => trim((string) $value) !== '') !== []): ?>
        <a href="<?= site_url('jobs') ?>" class="clear-filter"><i class="bi bi-x-circle"></i> Clear filters</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php if (! $result['items']): ?>
        <div class="col-12">
            <div class="empty-panel">
                <span class="empty-icon"><i class="bi bi-search"></i></span>
                <h2>No matching jobs found</h2>
                <p>Try a broader keyword or clear the current filters.</p>
                <a href="<?= site_url('jobs') ?>" class="btn btn-jng">View all jobs</a>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($result['items'] as $job): ?>
        <?php
        $jobId = (int) $job['id'];
        $saved = in_array($jobId, $savedJobIds ?? [], true);
        $applied = in_array($jobId, $appliedJobIds ?? [], true);
        $company = trim((string) ($job['company_name'] ?? '')) ?: 'Joy-Nostalg Group';
        $description = trim(strip_tags((string) ($job['description'] ?? '')));
        ?>
        <div class="col-md-6 col-xl-4">
            <article class="job-card h-100">
                <div class="job-card-top">
                    <div class="company-avatar"><i class="bi bi-building"></i></div>
                    <div class="job-card-heading">
                        <span class="job-code"><?= esc($job['job_code'] ?: 'Job opening') ?></span>
                        <h3><a href="<?= site_url('jobs/' . $jobId) ?>"><?= esc($job['title']) ?></a></h3>
                    </div>

                    <?php if (session()->get('applicant_id')): ?>
                        <form method="post" action="<?= site_url('jobs/' . $jobId . '/' . ($saved ? 'unsave' : 'save')) ?>">
                            <?= csrf_field() ?>
                            <button class="save-job-button<?= $saved ? ' is-saved' : '' ?>" title="<?= $saved ? 'Remove from saved jobs' : 'Save job' ?>" aria-label="<?= $saved ? 'Remove from saved jobs' : 'Save job' ?>">
                                <i class="bi <?= $saved ? 'bi-bookmark-fill' : 'bi-bookmark' ?>"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="job-company"><?= esc($company) ?><?php if ($job['department_name']): ?><span>&middot;</span><?= esc($job['department_name']) ?><?php endif; ?></div>

                <div class="job-meta">
                    <?php if ($job['location']): ?><span><i class="bi bi-geo-alt"></i><?= esc($job['location']) ?></span><?php endif; ?>
                    <?php if ($job['employment_type']): ?><span><i class="bi bi-clock"></i><?= esc($job['employment_type']) ?></span><?php endif; ?>
                    <?php if ($job['salary_label']): ?><span class="job-meta-highlight"><i class="bi bi-cash-stack"></i><?= esc($job['salary_label']) ?></span><?php endif; ?>
                    <?php if ($job['experience_label']): ?><span><i class="bi bi-briefcase"></i><?= esc($job['experience_label']) ?></span><?php endif; ?>
                </div>

                <?php if ($description !== ''): ?>
                    <p class="job-summary"><?= esc($description) ?></p>
                <?php endif; ?>

                <?php if ($job['valid_until']): ?>
                    <div class="closing-date"><i class="bi bi-calendar3"></i> Apply by <?= esc(date('M d, Y', strtotime($job['valid_until']))) ?></div>
                <?php endif; ?>

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

<?php if ($result['totalPages'] > 1): ?>
    <nav class="portal-pagination" aria-label="Job result pages">
        <ul class="pagination justify-content-center mb-0">
            <?php for ($page = 1; $page <= $result['totalPages']; $page++): ?>
                <li class="page-item <?= $page === $result['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= current_url() . '?' . http_build_query(array_merge($filters, ['page' => $page])) ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?= $this->endSection() ?>
