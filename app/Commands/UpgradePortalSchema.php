<?php

namespace App\Commands;

use App\Services\CareersSchema;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use Throwable;

class UpgradePortalSchema extends BaseCommand
{
    protected $group = 'Careers';
    protected $name = 'careers:upgrade-portal';
    protected $description = 'Legacy portal schema helper. The shared schema is now maintained by JNG Careers migrations.';

    public function run(array $params)
    {
        try {
            $db = db_connect();
            $forge = Database::forge();
            $config = config('Careers');

            $this->upgradeApplicants($db, $forge, $config->tables['applicants']);
            $this->upgradeApplications($db, $forge, $config->tables['applications']);
            $this->createSavedJobs($db, $forge, $config->tables['savedJobs']);
            $this->checkWithdrawnStatus();

            CLI::newLine();
            CLI::write('Legacy Apply Portal schema checks completed.', 'green');
            CLI::write('Important: deploy JNG Careers and run `php spark migrate` there for workflow actions and live notifications.', 'yellow');
            CLI::write('Then run: php spark careers:schema', 'yellow');
            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());
            return EXIT_ERROR;
        }
    }

    private function upgradeApplicants($db, $forge, string $table): void
    {
        $fields = [
            'religion' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'civil_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'current_address' => ['type' => 'TEXT', 'null' => true],
            'permanent_address' => ['type' => 'TEXT', 'null' => true],
            'covid19_vaccinated' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'has_company_relative' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'company_relative_name' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'company_relative_relation' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'company_relative_relation_other' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ];

        $this->addMissingColumns($db, $forge, $table, $fields);
    }

    private function upgradeApplications($db, $forge, string $table): void
    {
        $fields = [
            'withdrawn_at' => ['type' => 'DATETIME', 'null' => true],
            'withdrawal_reason' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ];

        $this->addMissingColumns($db, $forge, $table, $fields);
    }

    private function addMissingColumns($db, $forge, string $table, array $fields): void
    {
        if (! $db->tableExists($table)) {
            throw new \RuntimeException('Required table not found: ' . $table);
        }

        $missing = [];
        foreach ($fields as $name => $definition) {
            if (! $db->fieldExists($name, $table)) {
                $missing[$name] = $definition;
            }
        }

        if ($missing === []) {
            CLI::write($table . ': already up to date.', 'light_gray');
            return;
        }

        $forge->addColumn($table, $missing);
        CLI::write($table . ': added ' . implode(', ', array_keys($missing)), 'green');
    }

    private function createSavedJobs($db, $forge, string $table): void
    {
        if ($db->tableExists($table)) {
            CLI::write($table . ': already exists.', 'light_gray');
            return;
        }

        $forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'applicant_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'job_list_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addUniqueKey(['applicant_id', 'job_list_id'], 'uq_applicant_saved_job');
        $forge->addKey('applicant_id');
        $forge->addKey('job_list_id');
        $forge->createTable($table, true);

        CLI::write($table . ': created.', 'green');
    }

    private function checkWithdrawnStatus(): void
    {
        $schema = new CareersSchema();
        $table = $schema->table('statuses', false);
        $name = $schema->field('statuses', 'name', false);
        $statusText = $schema->field('applications', 'status_text', false);

        if (! $table || ! $name) {
            return;
        }

        $exists = $schema->db()->table($table)
            ->whereIn($name, ['Withdrawn', 'Applicant Withdrawn', 'Withdraw'])
            ->countAllResults() > 0;

        if (! $exists && ! $statusText) {
            CLI::write(
                'Warning: add a Withdrawn status to the Careers status table before enabling withdrawals.',
                'yellow'
            );
        }
    }
}
