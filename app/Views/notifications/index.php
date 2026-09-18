<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="mb-4"><h1 class="section-title h2 mb-1">Notifications</h1><p class="text-muted mb-0">Updates related to your applicant account and job applications.</p></div>

<div class="card"><div class="card-body p-4">
    <?php if (! $notifications): ?>
        <div class="empty-state"><i class="bi bi-bell display-4"></i><p class="mt-3 mb-0">No notifications are available.</p></div>
    <?php else: ?>
        <div class="vstack gap-3">
            <?php foreach ($notifications as $notification): ?>
                <article class="border rounded-3 p-3 <?= ! $notification['read'] ? 'bg-light' : '' ?>">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <h2 class="h6 fw-bold mb-1"><?= esc($notification['subject'] ?: 'Recruitment update') ?></h2>
                            <div class="text-muted"><?= nl2br(esc($notification['message'] ?: '')) ?></div>
                            <?php if ($notification['created_at']): ?><div class="small text-muted mt-2"><?= esc(date('M d, Y h:i A', strtotime($notification['created_at']))) ?></div><?php endif; ?>
                        </div>
                        <?php if (! $notification['read']): ?>
                            <form method="post" action="<?= site_url('notifications/' . $notification['id'] . '/read') ?>">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-jng">Mark read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
