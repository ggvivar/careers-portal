-- Back up the shared Careers database before applying this script.

ALTER TABLE applicants
    ADD COLUMN religion VARCHAR(100) NULL,
    ADD COLUMN civil_status VARCHAR(50) NULL,
    ADD COLUMN current_address TEXT NULL,
    ADD COLUMN permanent_address TEXT NULL,
    ADD COLUMN covid19_vaccinated TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN has_company_relative TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN company_relative_relation VARCHAR(100) NULL;

ALTER TABLE job_applications
    ADD COLUMN withdrawn_at DATETIME NULL,
    ADD COLUMN withdrawal_reason VARCHAR(500) NULL;

CREATE TABLE applicant_saved_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    applicant_id BIGINT UNSIGNED NOT NULL,
    job_list_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_applicant_saved_job (applicant_id, job_list_id),
    KEY idx_saved_job_applicant (applicant_id),
    KEY idx_saved_job_post (job_list_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure your status table includes a status named "Withdrawn"
-- when job_applications relies only on status_id.
