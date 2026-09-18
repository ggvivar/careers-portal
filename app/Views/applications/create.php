<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center"><div class="col-xl-8">
    <div class="mb-4">
        <h1 class="section-title h2 mb-1">Apply for <?= esc($job['title']) ?></h1>
        <p class="text-muted mb-0"><?= esc($job['job_code'] ?: '') ?></p>
    </div>

    <div class="card"><div class="card-body p-4 p-lg-5">
        <form method="post" action="<?= site_url('apply/' . $job['id']) ?>" enctype="multipart/form-data" id="applicationForm">
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="form-label fw-semibold">Resume</label>
                <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx" required>
                <div class="form-text">PDF, DOC, or DOCX; maximum 5 MB.</div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Cover letter</label>
                <textarea name="cover_letter" class="form-control" rows="8" maxlength="5000" placeholder="Tell us why you are interested in this role."><?= esc(old('cover_letter')) ?></textarea>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-jng px-4" id="submitApplication">
                    <span>Submit application</span><span class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
                <a href="<?= site_url('jobs/' . $job['id']) ?>" class="btn btn-light border">Cancel</a>
            </div>
        </form>
    </div></div>
</div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('applicationForm').addEventListener('submit', function () {
    if (!this.checkValidity()) return;
    const button = document.getElementById('submitApplication');
    button.disabled = true;
    button.querySelector('span:first-child').textContent = 'Submitting';
    button.querySelector('.spinner-border').classList.remove('d-none');
});
</script>
<?= $this->endSection() ?>
