<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$employeeDetails ??= null;
$isHired = (bool) ($isHired ?? false);
$health = (array) ($profileHealth ?? []);
$healthPercentage = (int) ($health['percentage'] ?? 0);

$personalValue = static function (string $key, mixed $default = '') use ($applicant): mixed {
    return old($key, $applicant[$key] ?? $default);
};

$employeeValue = static function (string $key, mixed $default = '') use ($employeeDetails): mixed {
    return old($key, $employeeDetails[$key] ?? $default);
};

$formatDate = static function (?string $value): string {
    if (! $value) {
        return '—';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('M d, Y', $timestamp) : $value;
};
?>

<style>
.profile-health-card {
    width: 100%;
    border: 0;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(10, 1, 71, 0.07);
}

.profile-health-card .progress {
    height: 8px;
    border-radius: 999px;
    background: #edf0f6;
}

.profile-health-card .progress-bar {
    border-radius: inherit;
    background: linear-gradient(90deg, var(--jng-primary, #0a0147), #4f46e5);
}

.profile-mobile-toggle {
    display: none;
}

@media (min-width: 768px) {
    .profile-mobile-collapse.collapse {
        display: block !important;
        height: auto !important;
        visibility: visible !important;
    }
}

@media (max-width: 767.98px) {
    .profile-tabs {
        display: none !important;
    }

    .profile-tab-content > .tab-pane {
        display: block !important;
        opacity: 1 !important;
        margin-bottom: 12px;
    }

    .profile-mobile-toggle {
        width: 100%;
        min-height: 54px;
        padding: 13px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #dde2ec;
        border-radius: 14px;
        background: #ffffff;
        color: #202331;
        font-size: 0.92rem;
        font-weight: 700;
        text-align: left;
        box-shadow: 0 5px 16px rgba(10, 1, 71, 0.05);
    }

    .profile-mobile-toggle[aria-expanded="true"] {
        border-color: rgba(10, 1, 71, 0.2);
        border-radius: 14px 14px 8px 8px;
        background: rgba(10, 1, 71, 0.035);
        color: var(--jng-primary, #0a0147);
    }

    .profile-mobile-toggle .profile-section-label {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .profile-mobile-toggle .profile-section-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: rgba(10, 1, 71, 0.08);
        color: var(--jng-primary, #0a0147);
        font-size: 0.95rem;
    }

    .profile-mobile-toggle .profile-section-chevron {
        transition: transform 0.2s ease;
    }

    .profile-mobile-toggle[aria-expanded="true"] .profile-section-chevron {
        transform: rotate(180deg);
    }

    .profile-mobile-collapse {
        padding-top: 10px;
    }

    .profile-mobile-collapse .card-body {
        padding: 1rem !important;
    }

    .profile-mobile-collapse .row.g-4 {
        --bs-gutter-y: 0.9rem;
    }
}
</style>

<div class="mb-3">
    <h1 class="section-title h2 mb-1">My profile</h1>
    <p class="text-muted mb-0">Keep your applicant, education, employment, and employee information current.</p>
</div>

<div class="card profile-health-card mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <span class="d-block fw-semibold">Profile health</span>
                <span class="small text-muted">Complete your information to strengthen your applicant profile.</span>
            </div>
            <strong class="fs-5 text-primary-emphasis"><?= $healthPercentage ?>%</strong>
        </div>
        <div class="progress" role="progressbar" aria-label="Profile health" aria-valuenow="<?= $healthPercentage ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width:<?= $healthPercentage ?>%"></div>
        </div>
    </div>
</div>

<ul class="nav nav-pills profile-tabs flex-nowrap overflow-x-auto gap-2 mb-4" id="profileTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal" type="button">Personal</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#education" type="button">Education</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#employment" type="button">Employment</button>
    </li>
    <?php if ($isHired): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#employee-details" type="button">
                Employee details
            </button>
        </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents" type="button">Documents</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#security" type="button">Security</button>
    </li>
</ul>

<div class="tab-content profile-tab-content" id="profileMobileAccordion">
    <section class="tab-pane fade show active" id="personal" role="tabpanel">
        <div class="card">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4">
                    <h2 class="h5 fw-bold mb-1">Applicant information</h2>
                    <p class="text-muted mb-0">These fields use the same Careers applicant record seen by the recruitment team.</p>
                </div>

                <form method="post" action="<?= site_url('profile') ?>">
                    <?= csrf_field() ?>

                    <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Personal Information</h3>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">First name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" maxlength="100" required value="<?= esc($personalValue('first_name')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Middle name</label>
                            <input type="text" name="middle_name" class="form-control" maxlength="100" value="<?= esc($personalValue('middle_name')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Last name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" maxlength="100" required value="<?= esc($personalValue('last_name')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffix" class="form-control" maxlength="20" placeholder="Jr., Sr., III" value="<?= esc($personalValue('suffix')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of birth</label>
                            <input type="date" name="birthdate" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= esc($personalValue('birthdate')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select gender</option>
                                <?php foreach ((array) $genderOptions as $value => $label): ?>
                                    <option value="<?= esc((string) $value) ?>" <?= (string) $personalValue('gender') === (string) $value ? 'selected' : '' ?>>
                                        <?= esc((string) $label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Civil status</label>
                            <select name="civil_status" class="form-select">
                                <option value="">Select civil status</option>
                                <?php foreach ((array) $civilStatusOptions as $value => $label): ?>
                                    <option value="<?= esc((string) $value) ?>" <?= (string) $personalValue('civil_status') === (string) $value ? 'selected' : '' ?>>
                                        <?= esc((string) $label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control" maxlength="100" value="<?= esc($personalValue('nationality', 'Filipino')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Religion</label>
                            <input type="text" name="religion" class="form-control" maxlength="100" value="<?= esc($personalValue('religion')) ?>">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Contact Information</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" maxlength="190" required value="<?= esc($personalValue('email')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile number</label>
                            <input type="text" name="phone" class="form-control" maxlength="40" value="<?= esc($personalValue('phone')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current address</label>
                            <textarea name="current_address" id="currentAddress" class="form-control" rows="3" maxlength="1000"><?= esc($personalValue('current_address')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Permanent address</label>
                            <textarea name="permanent_address" id="permanentAddress" class="form-control" rows="3" maxlength="1000"><?= esc($personalValue('permanent_address')) ?></textarea>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="sameAddress">
                                <label class="form-check-label" for="sameAddress">Same as current address</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">City / Municipality</label>
                            <input type="text" name="city" class="form-control" maxlength="120" value="<?= esc($personalValue('city')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Province</label>
                            <input type="text" name="province" class="form-control" maxlength="120" value="<?= esc($personalValue('province')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" maxlength="120" value="<?= esc($personalValue('country', 'Philippines')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ZIP code</label>
                            <input type="text" name="zip_code" class="form-control" maxlength="20" value="<?= esc($personalValue('zip_code')) ?>">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Additional information</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check border rounded-3 p-3 ps-5 h-100">
                                <input type="checkbox" name="covid_vaccinated" value="1" class="form-check-input" id="covidVaccinated" <?= $personalValue('covid_vaccinated', 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="covidVaccinated">
                                    <strong>Vaccinated against COVID-19</strong>
                                    <span class="d-block text-muted small">Optional applicant disclosure.</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check border rounded-3 p-3 ps-5 h-100">
                                <input type="checkbox" name="has_company_relative" value="1" class="form-check-input" id="hasCompanyRelative" <?= $personalValue('has_company_relative', 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hasCompanyRelative">
                                    <strong>I have a relative working in the company</strong>
                                    <span class="d-block text-muted small">Declare an existing family relationship.</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6" id="relativeNameWrap">
                            <label class="form-label">Relative's full name</label>
                            <input
                                type="text"
                                name="company_relative_name"
                                id="companyRelativeName"
                                class="form-control"
                                maxlength="190"
                                value="<?= esc((string) $personalValue('company_relative_name')) ?>"
                                placeholder="Enter relative's full name"
                            >
                        </div>
                        <div class="col-md-6" id="relativeRelationWrap">
                            <label class="form-label">Relationship</label>
                            <select name="company_relative_relation" id="companyRelativeRelation" class="form-select">
                                <option value="">Select relationship</option>
                                <?php foreach ((array) $relationshipOptions as $value => $label): ?>
                                    <option value="<?= esc((string) $value) ?>" <?= (string) $personalValue('company_relative_relation') === (string) $value ? 'selected' : '' ?>>
                                        <?= esc((string) $label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 ms-auto" id="relativeRelationOtherWrap">
                            <label class="form-label">Specify relationship</label>
                            <input
                                type="text"
                                name="company_relative_relation_other"
                                id="companyRelativeRelationOther"
                                class="form-control"
                                maxlength="100"
                                value="<?= esc((string) $personalValue('company_relative_relation_other')) ?>"
                                placeholder="Please specify the relationship"
                            >
                        </div>
                    </div>

                    <hr class="my-4">
                    <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Professional links</h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">LinkedIn URL</label>
                            <input type="url" name="linkedin_url" class="form-control" maxlength="500" value="<?= esc($personalValue('linkedin_url')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Portfolio URL</label>
                            <input type="url" name="portfolio_url" class="form-control" maxlength="500" value="<?= esc($personalValue('portfolio_url')) ?>">
                        </div>
                    </div>

                    <button class="btn btn-jng px-4 mt-4">Save profile information</button>
                </form>
            </div>
        </div>
    </section>

    <section class="tab-pane fade" id="education" role="tabpanel">
        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">Add education</h2>
                        <p class="text-muted small">Fields match the Careers Education record.</p>
                        <form method="post" action="<?= site_url('profile/education') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label">School / institution <span class="text-danger">*</span></label>
                                <input type="text" name="school_name" class="form-control" maxlength="150" required value="<?= esc(old('school_name')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Degree / course</label>
                                <input type="text" name="degree" class="form-control" maxlength="150" value="<?= esc(old('degree')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Field of study</label>
                                <input type="text" name="field_of_study" class="form-control" maxlength="150" value="<?= esc(old('field_of_study')) ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Start year</label>
                                    <input type="number" name="start_year" class="form-control" min="1900" max="2100" value="<?= esc(old('start_year')) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End year</label>
                                    <input type="number" name="end_year" class="form-control" min="1900" max="2100" value="<?= esc(old('end_year')) ?>">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Honors received</label>
                                <input type="text" name="honors" class="form-control" maxlength="150" value="<?= esc(old('honors')) ?>">
                            </div>
                            <button class="btn btn-jng w-100 mt-3">Add education</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">Education history</h2>
                        <?php if (! $education): ?>
                            <p class="text-muted mb-0">No education records yet.</p>
                        <?php else: ?>
                            <div class="vstack gap-3">
                                <?php foreach ($education as $record): ?>
                                    <div class="border rounded-3 p-3">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div>
                                                <strong><?= esc((string) ($record['school_name'] ?: 'School')) ?></strong>
                                                <div class="text-muted">
                                                    <?= esc(trim((string) ($record['degree'] ?? '') . (($record['field_of_study'] ?? '') ? ' — ' . $record['field_of_study'] : ''))) ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?= esc((string) ($record['start_year'] ?: '—')) ?> to <?= esc((string) ($record['end_year'] ?: 'Present')) ?>
                                                </div>
                                                <?php if (! empty($record['honors'])): ?>
                                                    <div class="small mt-1"><strong>Honors:</strong> <?= esc((string) $record['honors']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <form method="post" action="<?= site_url('profile/education/' . $record['id'] . '/delete') ?>">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this education record?')" aria-label="Delete education">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="tab-pane fade" id="employment" role="tabpanel">
        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">Add employment</h2>
                        <p class="text-muted small">Fields match the Careers Employment History record.</p>
                        <form method="post" action="<?= site_url('profile/employment') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label">Company name <span class="text-danger">*</span></label>
                                <input type="text" name="company_name" class="form-control" maxlength="150" required value="<?= esc(old('company_name')) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company address</label>
                                <input type="text" name="company_address" class="form-control" maxlength="255" value="<?= esc(old('company_address')) ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Job title</label>
                                    <input type="text" name="job_title" class="form-control" maxlength="150" value="<?= esc(old('job_title')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control" maxlength="150" value="<?= esc(old('department')) ?>">
                                </div>
                            </div>
                            <div class="row g-3 mt-0">
                                <div class="col-6">
                                    <label class="form-label">Start date</label>
                                    <input type="date" name="start_date" id="employmentStartDate" class="form-control" value="<?= esc(old('start_date')) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End date</label>
                                    <input type="date" name="end_date" id="employmentEndDate" class="form-control" value="<?= esc(old('end_date')) ?>">
                                </div>
                            </div>
                            <div class="form-check mt-3">
                                <input type="checkbox" name="currently_working" value="1" class="form-check-input" id="currentlyWorking" <?= old('currently_working') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="currentlyWorking">I currently work here</label>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Responsibilities</label>
                                <textarea name="responsibilities" class="form-control" rows="4" maxlength="5000"><?= esc(old('responsibilities')) ?></textarea>
                            </div>
                            <div class="row g-3 mt-0">
                                <div class="col-md-6">
                                    <label class="form-label">Salary</label>
                                    <input type="number" name="salary" class="form-control" min="0" step="0.01" value="<?= esc(old('salary')) ?>">
                                </div>
                                <div class="col-md-6" id="reasonForLeavingWrap">
                                    <label class="form-label">Reason for leaving</label>
                                    <input type="text" name="reason_for_leaving" id="reasonForLeaving" class="form-control" maxlength="255" value="<?= esc(old('reason_for_leaving')) ?>">
                                </div>
                            </div>
                            <button class="btn btn-jng w-100 mt-3">Add employment</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">Employment history</h2>
                        <?php if (! $employment): ?>
                            <p class="text-muted mb-0">No employment records yet.</p>
                        <?php else: ?>
                            <div class="vstack gap-3">
                                <?php foreach ($employment as $record): ?>
                                    <div class="border rounded-3 p-3">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div>
                                                <strong><?= esc((string) ($record['job_title'] ?: 'Position')) ?></strong>
                                                <div class="text-muted"><?= esc((string) ($record['company_name'] ?: '')) ?></div>
                                                <?php if (! empty($record['company_address'])): ?>
                                                    <div class="small text-muted"><?= esc((string) $record['company_address']) ?></div>
                                                <?php endif; ?>
                                                <?php if (! empty($record['department'])): ?>
                                                    <div class="small text-muted"><?= esc((string) $record['department']) ?></div>
                                                <?php endif; ?>
                                                <div class="small text-muted">
                                                    <?= esc($formatDate($record['start_date'] ?? null)) ?> to
                                                    <?= ! empty($record['currently_working']) ? 'Present' : esc($formatDate($record['end_date'] ?? null)) ?>
                                                </div>
                                                <?php if (! empty($record['responsibilities'])): ?>
                                                    <div class="small mt-2"><?= nl2br(esc((string) $record['responsibilities'])) ?></div>
                                                <?php endif; ?>
                                                <?php if ($record['salary'] !== null && $record['salary'] !== ''): ?>
                                                    <div class="small mt-1"><strong>Salary:</strong> <?= esc(number_format((float) $record['salary'], 2)) ?></div>
                                                <?php endif; ?>
                                                <?php if (! empty($record['reason_for_leaving'])): ?>
                                                    <div class="small mt-1"><strong>Reason for leaving:</strong> <?= esc((string) $record['reason_for_leaving']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <form method="post" action="<?= site_url('profile/employment/' . $record['id'] . '/delete') ?>">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this employment record?')" aria-label="Delete employment">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($isHired): ?>
        <section class="tab-pane fade" id="employee-details" role="tabpanel">
            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card h-100">
                        <div class="card-body p-4 p-lg-5">
                            <div class="d-flex align-items-start gap-3 mb-4">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width:48px;height:48px">
                                    <i class="bi bi-person-badge fs-4"></i>
                                </span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1">Employee government numbers</h2>
                                    <p class="text-muted mb-0">Available because your application has reached Hired or Onboarding status.</p>
                                </div>
                            </div>

                            <form method="post" action="<?= site_url('profile/employee-details') ?>">
                                <?= csrf_field() ?>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SSS number</label>
                                        <input type="text" name="sss_no" class="form-control" maxlength="50" value="<?= esc($employeeValue('sss_no')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TIN</label>
                                        <input type="text" name="tin_no" class="form-control" maxlength="50" value="<?= esc($employeeValue('tin_no')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">PhilHealth number</label>
                                        <input type="text" name="philhealth_no" class="form-control" maxlength="50" value="<?= esc($employeeValue('philhealth_no')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pag-IBIG number</label>
                                        <input type="text" name="pagibig_no" class="form-control" maxlength="50" value="<?= esc($employeeValue('pagibig_no')) ?>">
                                    </div>
                                </div>
                                <button class="btn btn-jng px-4 mt-4">Save government numbers</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold">Employment record</h2>
                            <p class="text-muted small">The assigned Careers user manages these fields.</p>
                            <dl class="row mb-0">
                                <dt class="col-5 py-2">Employee number</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc((string) ($employeeDetails['employee_no'] ?? '—')) ?></dd>
                                <dt class="col-5 py-2">Position</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc((string) ($employeeDetails['position'] ?? '—')) ?></dd>
                                <dt class="col-5 py-2">Department</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc((string) ($employeeDetails['department'] ?? '—')) ?></dd>
                                <dt class="col-5 py-2">Employment type</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc((string) ($employeeDetails['employment_type'] ?? '—')) ?></dd>
                                <dt class="col-5 py-2">Hired date</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc($formatDate($employeeDetails['date_hired'] ?? null)) ?></dd>
                                <dt class="col-5 py-2">Start date</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc($formatDate($employeeDetails['date_start'] ?? null)) ?></dd>
                                <dt class="col-5 py-2">Resigned date</dt>
                                <dd class="col-7 py-2 mb-0"><?= esc($formatDate($employeeDetails['date_separated'] ?? null)) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="tab-pane fade" id="documents" role="tabpanel">
        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">Upload document</h2>
                        <form method="post" action="<?= site_url('profile/documents') ?>" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label">Document type</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">Select type</option>
                                    <option>Resume</option>
                                    <option>Cover Letter</option>
                                    <option>Transcript</option>
                                    <option>Certificate</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File</label>
                                <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <div class="form-text">PDF, Word, JPG, or PNG; maximum 5 MB.</div>
                            </div>
                            <button class="btn btn-jng w-100">Upload document</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">My documents</h2>
                        <?php if (! $documents): ?>
                            <p class="text-muted mb-0">No documents uploaded yet.</p>
                        <?php else: ?>
                            <div class="vstack gap-3">
                                <?php foreach ($documents as $document): ?>
                                    <div class="border rounded-3 p-3">
                                        <div class="d-flex justify-content-between gap-3 align-items-center">
                                            <div>
                                                <strong><?= esc((string) ($document['type'] ?: 'Document')) ?></strong>
                                                <div class="small text-muted"><?= esc((string) ($document['original_name'] ?: '')) ?></div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a class="btn btn-sm btn-outline-jng" href="<?= site_url('profile/documents/' . $document['id'] . '/download') ?>" aria-label="Download document">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <form method="post" action="<?= site_url('profile/documents/' . $document['id'] . '/delete') ?>">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this document?')" aria-label="Delete document">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="tab-pane fade" id="security" role="tabpanel">
        <div class="row justify-content-center">
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="h5 fw-bold">Change password</h2>
                        <p class="text-muted">Change the temporary password sent during registration or update your existing password.</p>
                        <form method="post" action="<?= site_url('profile/password') ?>">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label">Current password</label>
                                <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New password</label>
                                <input type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="10" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm new password</label>
                                <input type="password" name="new_password_confirmation" class="form-control" autocomplete="new-password" minlength="10" required>
                            </div>
                            <button class="btn btn-jng px-4">Change password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(() => {
    const profileSectionIcons = {
        personal: 'bi-person-vcard',
        education: 'bi-mortarboard',
        employment: 'bi-briefcase',
        'employee-details': 'bi-person-badge',
        documents: 'bi-folder2-open',
        security: 'bi-shield-lock',
    };

    const buildMobileProfileAccordion = () => {
        const accordion = document.getElementById('profileMobileAccordion');
        if (!accordion) return;

        const panes = Array.from(accordion.querySelectorAll(':scope > .tab-pane'));

        panes.forEach((pane, index) => {
            if (pane.dataset.mobileAccordionReady === '1') return;

            const tabTrigger = document.querySelector(
                `#profileTabs [data-bs-target="#${pane.id}"]`
            );
            const label = tabTrigger?.textContent?.trim() || 'Profile section';
            const collapseId = `profileMobileCollapse-${pane.id}`;
            const icon = profileSectionIcons[pane.id] || 'bi-card-list';

            const collapse = document.createElement('div');
            collapse.id = collapseId;
            collapse.className = `collapse profile-mobile-collapse${index === 0 ? ' show' : ''}`;
            collapse.setAttribute('data-bs-parent', '#profileMobileAccordion');

            while (pane.firstChild) {
                collapse.appendChild(pane.firstChild);
            }

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'profile-mobile-toggle';
            toggle.setAttribute('data-bs-toggle', 'collapse');
            toggle.setAttribute('data-bs-target', `#${collapseId}`);
            toggle.setAttribute('aria-controls', collapseId);
            toggle.setAttribute('aria-expanded', index === 0 ? 'true' : 'false');
            toggle.innerHTML = `
                <span class="profile-section-label">
                    <span class="profile-section-icon"><i class="bi ${icon}"></i></span>
                    <span>${label}</span>
                </span>
                <i class="bi bi-chevron-down profile-section-chevron" aria-hidden="true"></i>
            `;

            pane.append(toggle, collapse);
            pane.dataset.mobileAccordionReady = '1';

            collapse.addEventListener('shown.bs.collapse', () => {
                if (window.matchMedia('(max-width: 767.98px)').matches) {
                    history.replaceState(null, '', `#${pane.id}`);
                }
            });
        });
    };

    buildMobileProfileAccordion();

    const openMobileSectionFromHash = () => {
        if (!window.matchMedia('(max-width: 767.98px)').matches) return;

        const hash = window.location.hash;
        if (!hash) return;

        const pane = document.querySelector(hash);
        const collapse = pane?.querySelector(':scope > .profile-mobile-collapse');
        if (!collapse) return;

        bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
    };

    const showHashTab = () => {
        const hash = window.location.hash;
        if (!hash) return;
        const trigger = Array.from(document.querySelectorAll('#profileTabs [data-bs-target]'))
            .find((item) => item.getAttribute('data-bs-target') === hash);
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    };

    document.querySelectorAll('#profileTabs [data-bs-toggle="tab"]').forEach((trigger) => {
        trigger.addEventListener('shown.bs.tab', (event) => {
            const target = event.target.getAttribute('data-bs-target');
            if (target) history.replaceState(null, '', target);
        });
    });
    showHashTab();
    openMobileSectionFromHash();

    window.addEventListener('hashchange', openMobileSectionFromHash);

    const currentAddress = document.getElementById('currentAddress');
    const permanentAddress = document.getElementById('permanentAddress');
    const sameAddress = document.getElementById('sameAddress');
    const syncAddress = () => {
        if (!sameAddress || !permanentAddress || !currentAddress) return;
        if (sameAddress.checked) {
            permanentAddress.value = currentAddress.value;
            permanentAddress.readOnly = true;
        } else {
            permanentAddress.readOnly = false;
        }
    };
    sameAddress?.addEventListener('change', syncAddress);
    currentAddress?.addEventListener('input', () => {
        if (sameAddress?.checked && permanentAddress) permanentAddress.value = currentAddress.value;
    });

    const relative = document.getElementById('hasCompanyRelative');
    const relativeNameWrap = document.getElementById('relativeNameWrap');
    const relativeName = document.getElementById('companyRelativeName');
    const relativeWrap = document.getElementById('relativeRelationWrap');
    const relativeSelect = document.getElementById('companyRelativeRelation');
    const relativeOtherWrap = document.getElementById('relativeRelationOtherWrap');
    const relativeOther = document.getElementById('companyRelativeRelationOther');

    const isOtherRelationship = () => {
        const value = String(relativeSelect?.value || '').trim().toLowerCase();
        return value === 'other' || value === 'others';
    };

    const toggleRelative = () => {
        const visible = Boolean(relative?.checked);
        const showOther = visible && isOtherRelationship();

        relativeNameWrap?.classList.toggle('d-none', !visible);
        relativeWrap?.classList.toggle('d-none', !visible);
        relativeOtherWrap?.classList.toggle('d-none', !showOther);

        if (relativeName) {
            relativeName.required = visible;
            if (!visible) relativeName.value = '';
        }
        if (relativeSelect) {
            relativeSelect.required = visible;
            if (!visible) relativeSelect.value = '';
        }
        if (relativeOther) {
            relativeOther.required = showOther;
            if (!showOther) relativeOther.value = '';
        }
    };
    relative?.addEventListener('change', toggleRelative);
    relativeSelect?.addEventListener('change', toggleRelative);
    toggleRelative();

    const currentlyWorking = document.getElementById('currentlyWorking');
    const endDate = document.getElementById('employmentEndDate');
    const reasonWrap = document.getElementById('reasonForLeavingWrap');
    const reason = document.getElementById('reasonForLeaving');
    const toggleCurrentEmployer = () => {
        const current = Boolean(currentlyWorking?.checked);
        if (endDate) {
            endDate.disabled = current;
            if (current) endDate.value = '';
        }
        reasonWrap?.classList.toggle('d-none', current);
        if (reason && current) reason.value = '';
    };
    currentlyWorking?.addEventListener('change', toggleCurrentEmployer);
    toggleCurrentEmployer();
})();
</script>
<?= $this->endSection() ?>
