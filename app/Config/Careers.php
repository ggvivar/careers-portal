<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class Careers extends BaseConfig
{
    public string $storagePath;
    public string $websiteURL;
    public int $rememberDays;
    public int $resetMinutes;
    public array $tables = [];
    public array $publicJobStatuses = [];
    public array $initialApplicationStatuses = [];

    public array $fields = [
        'applicants' => [
            'id' => ['id', 'applicant_id'],
            'email' => ['email', 'email_address'],
            'password' => ['password_hash', 'password'],
            'first_name' => ['first_name', 'firstname', 'given_name'],
            'middle_name' => ['middle_name', 'middlename'],
            'last_name' => ['last_name', 'lastname', 'surname'],
            'suffix' => ['suffix'],
            'birthdate' => ['birthdate', 'date_of_birth', 'dob'],
            'gender' => ['gender', 'sex'],
            'nationality' => ['nationality', 'citizenship'],
            'phone' => ['phone', 'mobile_number', 'contact_number', 'contact_no'],
            'city' => ['city', 'current_city'],
            'province' => ['province', 'current_province'],
            'country' => ['country'],
            'zip_code' => ['zip_code', 'postal_code'],
            'linkedin_url' => ['linkedin_url', 'linkedin'],
            'portfolio_url' => ['portfolio_url', 'portfolio'],
            'religion' => ['religion'],
            'civil_status' => ['civil_status', 'marital_status'],
            'current_address' => ['current_address', 'present_address'],
            'permanent_address' => ['permanent_address', 'home_address'],
            'covid_vaccinated' => ['covid19_vaccinated', 'covid_vaccinated', 'is_covid_vaccinated', 'vaccinated_covid_19'],
            'has_company_relative' => ['has_company_relative', 'with_relatives_in_company', 'has_relative_in_company'],
            'company_relative_name' => ['company_relative_name', 'relative_name', 'company_relative_employee_name'],
            'company_relative_relation' => ['company_relative_relation', 'relative_relationship', 'relationship_to_employee'],
            'company_relative_relation_other' => ['company_relative_relation_other', 'relative_relationship_other', 'other_relationship_to_employee'],
            'active' => ['is_active', 'active'],
            'consent' => ['privacy_consent', 'data_privacy_consent', 'consent'],
            'consented_at' => ['privacy_consented_at', 'consented_at'],
            'created_at' => ['created_at', 'date_created'],
            'updated_at' => ['updated_at', 'date_updated'],
        ],
        'jobs' => [
            'id' => ['id', 'job_id'],
            'code' => ['code', 'job_code'],
            'name' => ['name', 'title', 'job_name', 'job_title'],
            'department_id' => ['department_id', 'dept_id'],
            'created_at' => ['created_at', 'date_created'],
        ],
        'jobPosts' => [
            'id' => ['id', 'job_list_id', 'job_post_id'],
            'job_id' => ['job_id'],
            'company_id' => ['company_id'],
            'department_id' => ['department_id'],
            'title' => ['title', 'job_title', 'name'],
            'job_code' => ['job_code', 'code'],
            'location' => ['location', 'work_location'],
            'employment_type' => ['employment_type', 'employment_type_name'],
            'salary_range' => ['salary_range', 'salary', 'compensation_range'],
            'experience_range' => ['experience_range', 'required_experience', 'experience'],
            'description' => ['description', 'job_description'],
            'responsibilities' => ['responsibilities', 'responsibility'],
            'requirements' => ['requirements', 'requirement'],
            'valid_until' => ['validity_date', 'valid_until', 'expiration_date'],
            'status_id' => ['status_id'],
            'created_at' => ['created_at', 'date_created'],
            'updated_at' => ['updated_at', 'date_updated'],
        ],
        'applications' => [
            'id' => ['id', 'job_application_id', 'application_id'],
            'applicant_id' => ['applicant_id'],
            'job_post_id' => ['job_list_id', 'job_post_id', 'job_id'],
            'status_id' => ['status_id'],
            'status_text' => ['status', 'application_status'],
            'source' => ['source', 'application_source'],
            'cover_letter' => ['cover_letter', 'message'],
            'applied_at' => ['applied_at', 'submitted_at', 'date_applied', 'created_at'],
            'created_at' => ['created_at', 'date_created'],
            'withdrawn_at' => ['withdrawn_at'],
            'withdrawal_reason' => ['withdrawal_reason', 'withdraw_reason'],
            'assigned_to' => ['assigned_to'],
            'assignee_id' => ['assignee_id'],
            'due_at' => ['due_at'],
            'updated_at' => ['updated_at', 'date_updated'],
        ],
        'applicationHistories' => [
            'id' => ['id'],
            'feature_id' => ['feature_id'],
            'application_id' => ['record_id', 'job_application_id', 'application_id'],
            'status_id_from' => ['status_id_from'],
            'status_id' => ['status_id_to', 'status_id'],
            'status_text' => ['status', 'status_name'],
            'remarks' => ['remarks', 'notes', 'comment'],
            'actor_type' => ['actor_type'],
            'actor_id' => ['actor_id'],
            'applicant_input' => ['applicant_input'],
            'due_at' => ['due_at'],
            'created_at' => ['created_at', 'date_created'],
        ],
        'workflowTransitions' => [
            'id' => ['id'],
            'feature_id' => ['feature_id'],
            'status_from' => ['status_id_from'],
            'status_to' => ['status_id_to'],
            'grace_period' => ['grace_period'],
            'require_remarks' => ['require_remarks'],
            'active' => ['status_id'],
            'applicant_action_enabled' => ['applicant_action_enabled'],
            'applicant_action_label' => ['applicant_action_label'],
            'applicant_prompt' => ['applicant_prompt'],
            'applicant_input_type' => ['applicant_input_type'],
            'applicant_input_label' => ['applicant_input_label'],
            'applicant_input_required' => ['applicant_input_required'],
            'created_at' => ['date_created', 'created_at'],
            'updated_at' => ['date_updated', 'updated_at'],
            'deleted_at' => ['date_deleted', 'deleted_at'],
        ],
        'features' => [
            'id' => ['id'],
            'code' => ['code'],
            'deleted_at' => ['date_deleted', 'deleted_at'],
        ],
        'users' => [
            'id' => ['id'],
            'role_id' => ['role_id'],
            'name' => ['name'],
            'email' => ['email'],
            'deleted_at' => ['date_deleted', 'deleted_at'],
        ],
        'rolePermissions' => [
            'role_id' => ['role_id'],
            'feature_id' => ['feature_id'],
            'can_view' => ['can_view'],
        ],
        'adminNotifications' => [
            'id' => ['id'],
            'user_id' => ['user_id'],
            'feature_code' => ['feature_code'],
            'record_id' => ['record_id'],
            'applicant_id' => ['applicant_id'],
            'event_key' => ['event_key'],
            'title' => ['title'],
            'message' => ['message'],
            'type' => ['type'],
            'action_url' => ['action_url'],
            'read' => ['is_read'],
            'created_at' => ['created_at'],
        ],
        'education' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'school_name' => ['school_name'],
            'degree' => ['degree'],
            'field_of_study' => ['field_of_study'],
            'start_year' => ['start_year'],
            'end_year' => ['end_year'],
            'honors' => ['honors'],
            'created_at' => ['date_created', 'created_at'],
        ],
        'employment' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'company_name' => ['company_name'],
            'company_address' => ['company_address'],
            'job_title' => ['job_title'],
            'department' => ['department'],
            'start_date' => ['start_date'],
            'end_date' => ['end_date'],
            'currently_working' => ['currently_working'],
            'responsibilities' => ['responsibilities'],
            'salary' => ['salary'],
            'reason_for_leaving' => ['reason_for_leaving'],
            'created_at' => ['date_created', 'created_at'],
        ],
        'employeeDetails' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'job_post_id' => ['job_post_id'],
            'employee_no' => ['employee_no'],
            'sss_no' => ['sss_no'],
            'tin_no' => ['tin_no'],
            'philhealth_no' => ['philhealth_no'],
            'pagibig_no' => ['pagibig_no'],
            'position' => ['position'],
            'department' => ['department'],
            'employment_type' => ['employment_type'],
            'date_hired' => ['date_hired'],
            'date_start' => ['date_start'],
            'date_regularized' => ['date_regularized'],
            'date_separated' => ['date_separated'],
            'status' => ['status'],
            'created_at' => ['date_created', 'created_at'],
            'updated_at' => ['date_updated', 'updated_at'],
        ],
        'commonDefaults' => [
            'id' => ['id'],
            'group' => ['key1'],
            'value' => ['value'],
            'definition' => ['definition'],
            'deleted_at' => ['date_deleted', 'deleted_at'],
        ],
        'documents' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'application_id' => ['job_application_id', 'application_id'],
            'type' => ['document_type', 'type', 'category'],
            'original_name' => ['original_name', 'file_name', 'filename'],
            'stored_name' => ['stored_name', 'saved_name'],
            'path' => ['file_path', 'path'],
            'mime_type' => ['mime_type', 'file_type'],
            'size' => ['file_size', 'size'],
            'created_at' => ['created_at', 'uploaded_at'],
        ],
        'tokens' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'token' => ['token_hash', 'token'],
            'expires_at' => ['expires_at', 'expiry'],
            'created_at' => ['created_at'],
            'last_used_at' => ['last_used_at', 'updated_at'],
        ],
        'passwordResets' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'token' => ['reset_token'],
            'expires_at' => ['expires_at'],
            'used_at' => ['used_at'],
            'created_at' => ['created_at'],
        ],
        'savedJobs' => [
            'id' => ['id', 'saved_job_id'],
            'applicant_id' => ['applicant_id'],
            'job_post_id' => ['job_list_id', 'job_post_id', 'job_id'],
            'created_at' => ['created_at', 'saved_at'],
        ],
        'notifications' => [
            'id' => ['id'],
            'applicant_id' => ['applicant_id'],
            'subject' => ['subject', 'title'],
            'message' => ['message', 'body', 'content'],
            'read' => ['is_read', 'read'],
            'read_at' => ['read_at'],
            'created_at' => ['created_at'],
        ],
        'statuses' => [
            'id' => ['id', 'status_id'],
            'name' => ['name', 'status_name'],
            'code' => ['code', 'status_code'],
        ],
        'departments' => [
            'id' => ['id', 'department_id'],
            'name' => ['name', 'department_name'],
        ],
        'companies' => [
            'id' => ['id', 'company_id'],
            'name' => ['name', 'company_name'],
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->storagePath = (string) env('careers.storagePath', WRITEPATH . 'careers-storage');
        $this->websiteURL = rtrim((string) env('careers.websiteURL', 'https://careers.joy-nostalg.com/'), '/') . '/';
        $this->rememberDays = max(1, (int) env('careers.rememberDays', 30));
        $this->resetMinutes = max(10, (int) env('careers.resetMinutes', 60));

        $this->tables = [
            'applicants' => (string) env('careers.table.applicants', 'applicants'),
            'jobs' => (string) env('careers.table.jobs', 'job'),
            'jobPosts' => (string) env('careers.table.jobPosts', 'job_list'),
            'applications' => (string) env('careers.table.applications', 'job_applications'),
            'applicationHistories' => (string) env('careers.table.applicationHistories', 'application_histories'),
            'workflowTransitions' => (string) env('careers.table.workflowTransitions', 'workflow_transitions'),
            'features' => (string) env('careers.table.features', 'features'),
            'users' => (string) env('careers.table.users', 'users'),
            'rolePermissions' => (string) env('careers.table.rolePermissions', 'role_feature_permissions'),
            'adminNotifications' => (string) env('careers.table.adminNotifications', 'admin_notifications'),
            'education' => (string) env('careers.table.education', 'applicant_education'),
            'employment' => (string) env('careers.table.employment', 'applicant_job_history'),
            'employeeDetails' => (string) env('careers.table.employeeDetails', 'applicant_employment_details'),
            'commonDefaults' => (string) env('careers.table.commonDefaults', 'common_defaults'),
            'documents' => (string) env('careers.table.documents', 'applicant_document_attachments'),
            'tokens' => (string) env('careers.table.tokens', 'applicant_tokens'),
            'passwordResets' => (string) env(
                'careers.table.passwordResets',
                'applicant_password_resets'
            ),
            'savedJobs' => (string) env('careers.table.savedJobs', 'applicant_saved_jobs'),
            'notifications' => (string) env('careers.table.notifications', 'applicant_notifications'),
            'statuses' => (string) env('careers.table.statuses', 'status'),
            'departments' => (string) env('careers.table.departments', 'departments'),
            'companies' => (string) env('careers.table.companies', 'companies'),
        ];

        $this->publicJobStatuses = $this->csv(
            (string) env('careers.publicJobStatuses', 'Published,Open,Active')
        );
        $this->initialApplicationStatuses = $this->csv(
            (string) env('careers.initialApplicationStatuses', 'Submitted,Applied,New')
        );
    }

    private function csv(string $value): array
    {
        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value)
        )));
    }
}
