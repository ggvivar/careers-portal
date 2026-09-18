<?php

namespace App\Repositories;

use App\Services\CareersSchema;
use RuntimeException;

class SavedJobRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function available(): bool
    {
        return $this->schema->table('savedJobs', false) !== null;
    }

    /** @return list<int> */
    public function idsForApplicant(int $applicantId): array
    {
        $table = $this->schema->table('savedJobs', false);
        $applicantField = $this->schema->field('savedJobs', 'applicant_id', false);
        $jobField = $this->schema->field('savedJobs', 'job_post_id', false);

        if (! $table || ! $applicantField || ! $jobField) {
            return [];
        }

        $createdField = $this->schema->field('savedJobs', 'created_at', false);
        $builder = $this->schema->db()->table($table)
            ->select($jobField)
            ->where($applicantField, $applicantId);

        if ($createdField) {
            $builder->orderBy($createdField, 'DESC');
        }

        return array_values(array_unique(array_map(
            'intval',
            array_column($builder->get()->getResultArray(), $jobField)
        )));
    }

    public function exists(int $applicantId, int $jobPostId): bool
    {
        return in_array($jobPostId, $this->idsForApplicant($applicantId), true);
    }

    public function save(int $applicantId, int $jobPostId): void
    {
        $table = $this->requiredTable();
        $applicantField = $this->schema->field('savedJobs', 'applicant_id');
        $jobField = $this->schema->field('savedJobs', 'job_post_id');

        if ($this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->where($jobField, $jobPostId)
            ->countAllResults() > 0) {
            return;
        }

        $payload = [
            $applicantField => $applicantId,
            $jobField       => $jobPostId,
        ];
        $payload += $this->schema->nowPayload('savedJobs');

        if (! $this->schema->db()->table($table)->insert($payload)) {
            throw new RuntimeException('Unable to save this job.');
        }
    }

    public function remove(int $applicantId, int $jobPostId): void
    {
        $table = $this->requiredTable();

        $this->schema->db()->table($table)
            ->where($this->schema->field('savedJobs', 'applicant_id'), $applicantId)
            ->where($this->schema->field('savedJobs', 'job_post_id'), $jobPostId)
            ->delete();
    }

    public function listForApplicant(int $applicantId, int $limit = 50): array
    {
        $ids = array_slice($this->idsForApplicant($applicantId), 0, max(1, $limit));
        if ($ids === []) {
            return [];
        }

        $jobs = (new JobRepository($this->schema))->findPublicMany($ids);
        $positions = array_flip($ids);

        usort($jobs, static fn (array $a, array $b): int =>
            ($positions[(int) $a['id']] ?? PHP_INT_MAX)
            <=> ($positions[(int) $b['id']] ?? PHP_INT_MAX)
        );

        return $jobs;
    }

    private function requiredTable(): string
    {
        $table = $this->schema->table('savedJobs', false);
        if (! $table) {
            throw new RuntimeException(
                'Saved jobs are not installed yet. Run: php spark careers:upgrade-portal'
            );
        }

        return $table;
    }
}
