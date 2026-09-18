<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Applicant Account | JNG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{--jng-primary:#0a0147;--jng-yellow:#ffc91c;--jng-border:#e0e4ec}
        body{background:#f5f7fb;font-family:"Segoe UI",Arial,sans-serif}
        .register-card{border:0;border-radius:20px;box-shadow:0 16px 45px rgba(10,1,71,.09)}
        .form-control{min-height:50px;border-color:var(--jng-border);border-radius:11px}
        .btn-jng{min-height:52px;border:0;border-radius:11px;background:var(--jng-yellow);color:var(--jng-primary);font-weight:800}
        .consent-box{padding:16px;border:1px solid var(--jng-border);border-radius:12px;background:#fbfcfe}
        .modal-content{border:0;border-radius:18px;overflow:hidden}.privacy-content{height:360px;overflow:auto;line-height:1.7}
    </style>
</head>
<body>
<div class="container py-5">
    <div class="mx-auto" style="max-width:800px">
        <div class="text-center mb-4">
            <div class="fw-bold fs-2" style="color:#0a0147">Joy~Nostalg Group</div>
            <h1 class="h2 fw-bold mt-2">Create your applicant account</h1>
            <p class="text-muted">Your password will be generated securely and sent only to your email.</p>
        </div>

        <div class="card register-card">
            <div class="card-body p-4 p-lg-5">
                <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
                <?php if (session()->getFlashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <?php foreach ((array) session()->getFlashdata('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= site_url('register') ?>" id="registerForm">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">First name</label><input type="text" name="first_name" class="form-control" value="<?= esc(old('first_name')) ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Middle name</label><input type="text" name="middle_name" class="form-control" value="<?= esc(old('middle_name')) ?>"></div>
                        <div class="col-md-6"><label class="form-label">Last name</label><input type="text" name="last_name" class="form-control" value="<?= esc(old('last_name')) ?>" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Date of birth</label>
                            <input type="date" name="birthdate" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= esc(old('birthdate')) ?>" required>
                        </div>
                        <div class="col-md-6"><label class="form-label">Mobile number</label><input type="text" name="phone" class="form-control" value="<?= esc(old('phone')) ?>"></div>
                        <div class="col-12">
                            <label class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>" required>
                            <div class="form-text">Existing applicants should use the same email to sign in or reset their password.</div>
                        </div>
                    </div>

                    <div class="consent-box mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="privacy_consent" value="1" id="privacyConsent" <?= old('privacy_consent') ? 'checked' : '' ?> required>
                            <label class="form-check-label" for="privacyConsent">I have read and agree to the recruitment privacy notice.</label>
                        </div>
                        <button type="button" class="btn btn-link p-0 mt-2" data-bs-toggle="modal" data-bs-target="#privacyModal">Read privacy notice</button>
                    </div>

                    <button type="submit" class="btn btn-jng w-100 mt-4" id="createButton">
                        <span id="createButtonText">Create applicant account</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" id="createSpinner"></span>
                    </button>
                </form>

                <p class="text-center mt-4 mb-0">Already registered? <a href="<?= site_url('login') ?>">Sign in</a></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="privacyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header sticky-top bg-white">
                <h2 class="modal-title h5 fw-bold">Recruitment Privacy Notice</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body privacy-content" id="privacyContent">
                <p>Joy-Nostalg Group collects applicant information to evaluate qualifications, communicate about employment opportunities, arrange interviews, maintain recruitment records, and comply with legal and organizational requirements.</p>
                <p>Information may include identity and contact details, education, employment history, application responses, assessment results, interview notes, and documents voluntarily submitted for recruitment.</p>
                <p>Access is limited to authorized recruitment personnel and approved service providers. Information is retained only for the period required by recruitment, legitimate business needs, and applicable law.</p>
                <p>Applicants must provide accurate information and should not submit unnecessary sensitive information. Uploaded files must not contain passwords, banking credentials, or unrelated confidential records.</p>
                <p>You may request reasonable access, correction, or deletion subject to legal, contractual, and recruitment-record requirements.</p>
                <p>By continuing, you acknowledge that you have read this notice and consent to the processing of your personal information for recruitment purposes.</p>
                <p class="mb-0">Scroll to the end to enable the agreement action.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="agreePrivacy" disabled data-bs-dismiss="modal">Agree and continue</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const content = document.getElementById('privacyContent');
    const agreeButton = document.getElementById('agreePrivacy');
    const consent = document.getElementById('privacyConsent');
    const form = document.getElementById('registerForm');
    const createButton = document.getElementById('createButton');

    consent.addEventListener('click', event => {
        if (!consent.checked) return;
        event.preventDefault();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('privacyModal')).show();
    });

    content.addEventListener('scroll', () => {
        agreeButton.disabled = !(content.scrollTop + content.clientHeight >= content.scrollHeight - 8);
    });

    agreeButton.addEventListener('click', () => consent.checked = true);

    form.addEventListener('submit', () => {
        if (!form.checkValidity()) return;
        createButton.disabled = true;
        document.getElementById('createButtonText').textContent = 'Creating account';
        document.getElementById('createSpinner').classList.remove('d-none');
    });
})();
</script>
</body>
</html>
