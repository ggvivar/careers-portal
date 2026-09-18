<?php
namespace App\Commands;

use App\Services\CareersSchema;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\Commands;
use Psr\Log\LoggerInterface;
use Throwable;

class CareersSchemaCheck extends BaseCommand
{
    protected $group = 'Careers';
    protected $name = 'careers:schema';
    protected $description = 'Checks Apply Portal compatibility with the shared Careers database.';

    public function __construct(LoggerInterface $logger, Commands $commands)
    {
        parent::__construct($logger, $commands);
    }

    public function run(array $params): void
    {
        try {
            $report = (new CareersSchema())->diagnostics();
        } catch (Throwable $exception) {
            CLI::error('Database connection failed: ' . $exception->getMessage());
            return;
        }

        CLI::write('JNG Apply Portal - Careers Schema Compatibility', 'yellow');
        CLI::newLine();

        $requiredTables = [
            'applicants', 'jobs', 'jobPosts', 'applications',
            'education', 'employment', 'employeeDetails', 'commonDefaults',
            'documents', 'statuses', 'savedJobs',
        ];
        $requiredFields = [
            'applicants' => [
                'suffix', 'birthdate', 'gender', 'nationality', 'zip_code',
                'religion', 'civil_status', 'current_address',
                'permanent_address', 'covid_vaccinated',
                'has_company_relative', 'company_relative_name',
                'company_relative_relation', 'company_relative_relation_other',
            ],
            'education' => [
                'id', 'applicant_id', 'school_name', 'degree',
                'field_of_study', 'start_year', 'end_year', 'honors',
            ],
            'employment' => [
                'id', 'applicant_id', 'company_name', 'company_address',
                'job_title', 'department', 'start_date', 'end_date',
                'currently_working', 'responsibilities', 'salary',
                'reason_for_leaving',
            ],
            'employeeDetails' => [
                'id', 'applicant_id', 'employee_no', 'sss_no', 'tin_no',
                'philhealth_no', 'pagibig_no', 'date_hired', 'date_start',
                'date_separated',
            ],
            'commonDefaults' => ['id', 'group', 'value'],
            'savedJobs' => ['id', 'applicant_id', 'job_post_id', 'created_at'],
        ];

        $hasError = false;

        foreach ($report as $key => $item) {
            $isRequired = in_array($key, $requiredTables, true);
            $color = $item['exists'] ? 'green' : ($isRequired ? 'red' : 'yellow');

            CLI::write(sprintf(
                '[%s] %-23s %s',
                $item['exists'] ? 'OK' : ($isRequired ? 'MISSING' : 'OPTIONAL'),
                $key,
                $item['configured']
            ), $color);

            if (! $item['exists']) {
                $hasError = $hasError || $isRequired;
                continue;
            }

            foreach ($item['fields'] as $field => $resolved) {
                if ($resolved !== null) {
                    CLI::write("    {$field} -> {$resolved}", 'light_gray');
                    continue;
                }

                if (in_array($field, $requiredFields[$key] ?? [], true)) {
                    CLI::write("    MISSING REQUIRED FIELD: {$field}", 'red');
                    $hasError = true;
                }
            }
        }

        CLI::newLine();

        if ($hasError) {
            CLI::error('Required portal tables or fields are missing. Run: php spark careers:upgrade-portal');
        } else {
            CLI::write('Required Careers tables and applicant-portal fields were found.', 'green');
            CLI::write('Test profile common defaults, education/employment, employee details, withdrawals, notifications, and application progress on staging.', 'yellow');
        }
    }
}
