<?php

namespace App\Repositories;

use App\Services\CareersSchema;
use RuntimeException;

class ProfileRepository
{
    private const HIRED_STATUSES = ['hired', 'on-boarding', 'onboarding'];

    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function education(int $applicantId): array
    {
        return $this->listOwned('education', $applicantId);
    }

    public function employment(int $applicantId): array
    {
        return $this->listOwned('employment', $applicantId);
    }

    public function addEducation(int $applicantId, array $data): void
    {
        $this->insertOwned('education', $applicantId, $data);
    }

    public function addEmployment(int $applicantId, array $data): void
    {
        $this->insertOwned('employment', $applicantId, $data);
    }

    public function deleteEducation(int $id, int $applicantId): void
    {
        $this->deleteOwned('education', $id, $applicantId);
    }

    public function deleteEmployment(int $id, int $applicantId): void
    {
        $this->deleteOwned('employment', $id, $applicantId);
    }

    public function employeeDetails(int $applicantId): ?array
    {
        $table = $this->schema->table('employeeDetails', false);
        $applicantField = $this->schema->field('employeeDetails', 'applicant_id', false);

        if (! $table || ! $applicantField) {
            return null;
        }

        $row = $this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->get()
            ->getRowArray();

        return $row ? $this->normalize('employeeDetails', $row) : null;
    }

    public function hiredApplication(int $applicantId): ?array
    {
        $applications = $this->schema->table('applications', false);
        $statuses = $this->schema->table('statuses', false);
        $applicationId = $this->schema->field('applications', 'id', false);
        $applicantField = $this->schema->field('applications', 'applicant_id', false);
        $jobPostField = $this->schema->field('applications', 'job_post_id', false);
        $statusField = $this->schema->field('applications', 'status_id', false);
        $statusId = $this->schema->field('statuses', 'id', false);
        $statusName = $this->schema->field('statuses', 'name', false);

        if (! $applications || ! $statuses || ! $applicationId || ! $applicantField
            || ! $jobPostField || ! $statusField || ! $statusId || ! $statusName) {
            return null;
        }

        $row = $this->schema->db()->table($applications . ' a')
            ->select("a.{$applicationId} AS application_id", false)
            ->select("a.{$applicantField} AS applicant_id", false)
            ->select("a.{$jobPostField} AS job_post_id", false)
            ->select("s.{$statusName} AS status_name", false)
            ->join($statuses . ' s', "s.{$statusId} = a.{$statusField}", 'inner', false)
            ->where("a.{$applicantField}", $applicantId)
            ->whereIn("LOWER(TRIM(s.{$statusName}))", self::HIRED_STATUSES)
            ->orderBy("a.{$applicationId}", 'DESC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function ensureEmployeeDetailsForHiredApplication(int $applicationId): ?array
    {
        $applications = $this->schema->table('applications', false);
        $idField = $this->schema->field('applications', 'id', false);
        $applicantField = $this->schema->field('applications', 'applicant_id', false);

        if (! $applications || ! $idField || ! $applicantField) {
            return null;
        }

        $application = $this->schema->db()->table($applications)
            ->where($idField, $applicationId)
            ->get()
            ->getRowArray();

        if (! $application) {
            return null;
        }

        return $this->ensureEmployeeDetailsForApplicant((int) $application[$applicantField]);
    }

    public function ensureEmployeeDetailsForApplicant(int $applicantId): ?array
    {
        $hiredApplication = $this->hiredApplication($applicantId);
        if (! $hiredApplication) {
            return null;
        }

        $table = $this->schema->table('employeeDetails', false);
        $applicantField = $this->schema->field('employeeDetails', 'applicant_id', false);
        if (! $table || ! $applicantField) {
            return null;
        }

        $jobData = $this->jobPostEmployeeData((int) ($hiredApplication['job_post_id'] ?? 0));
        $existing = $this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->get()
            ->getRowArray();

        $data = [
            'job_post_id' => (int) ($hiredApplication['job_post_id'] ?? 0) ?: null,
            'position' => $jobData['position'] ?? null,
            'department' => $jobData['department'] ?? null,
            'employment_type' => $jobData['employment_type'] ?? null,
            'status' => 'Hired',
        ];

        $payload = [];
        foreach ($data as $key => $value) {
            $field = $this->schema->field('employeeDetails', $key, false);
            if ($field) {
                if ($existing && in_array($key, ['position', 'department', 'employment_type'], true)
                    && trim((string) ($existing[$field] ?? '')) !== '') {
                    continue;
                }
                $payload[$field] = $value;
            }
        }

        $payload += $this->schema->nowPayload('employeeDetails', true);

        if ($existing) {
            $idField = $this->schema->field('employeeDetails', 'id', false);
            if ($idField && $payload !== []) {
                $this->schema->db()->table($table)
                    ->where($idField, (int) $existing[$idField])
                    ->update($payload);
            }
            return $this->employeeDetails($applicantId);
        }

        $payload[$applicantField] = $applicantId;
        $createdField = $this->schema->field('employeeDetails', 'created_at', false);
        if ($createdField) {
            $payload[$createdField] = date('Y-m-d H:i:s');
        }

        $this->schema->db()->table($table)->insert($payload);
        return $this->employeeDetails($applicantId);
    }

    public function saveApplicantGovernmentIds(int $applicantId, array $data): void
    {
        if (! $this->hiredApplication($applicantId)) {
            throw new RuntimeException('Employee details are available only after your application reaches Hired status.');
        }

        $this->ensureEmployeeDetailsForApplicant($applicantId);

        $table = $this->schema->table('employeeDetails');
        $applicantField = $this->schema->field('employeeDetails', 'applicant_id');
        $payload = [];

        foreach (['sss_no', 'tin_no', 'philhealth_no', 'pagibig_no'] as $key) {
            $field = $this->schema->field('employeeDetails', $key, false);
            if ($field && array_key_exists($key, $data)) {
                $payload[$field] = trim((string) $data[$key]) ?: null;
            }
        }

        $payload += $this->schema->nowPayload('employeeDetails', true);

        if ($payload !== []) {
            $this->schema->db()->table($table)
                ->where($applicantField, $applicantId)
                ->update($payload);
        }
    }

    private function listOwned(string $key, int $applicantId): array
    {
        $table = $this->schema->table($key, false);
        $applicantField = $this->schema->field($key, 'applicant_id', false);
        if (! $table || ! $applicantField) {
            return [];
        }

        $id = $this->schema->field($key, 'id', false);
        $created = $this->schema->field($key, 'created_at', false);
        $rows = $this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->orderBy($created ?: $id ?: $applicantField, 'DESC')
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->normalize($key, $row), $rows);
    }

    private function insertOwned(string $key, int $applicantId, array $data): void
    {
        $payload = [$this->schema->field($key, 'applicant_id') => $applicantId];
        foreach ($data as $fieldKey => $value) {
            $field = $this->schema->field($key, $fieldKey, false);
            if ($field) {
                $payload[$field] = $value;
            }
        }

        $payload += $this->schema->nowPayload($key);
        $this->schema->db()->table($this->schema->table($key))->insert($payload);
    }

    private function deleteOwned(string $key, int $id, int $applicantId): void
    {
        $table = $this->schema->table($key, false);
        $idField = $this->schema->field($key, 'id', false);
        $applicantField = $this->schema->field($key, 'applicant_id', false);
        if ($table && $idField && $applicantField) {
            $this->schema->db()->table($table)
                ->where($idField, $id)
                ->where($applicantField, $applicantId)
                ->delete();
        }
    }

    private function normalize(string $key, array $row): array
    {
        $result = ['raw' => $row];
        foreach (array_keys($this->schema->config()->fields[$key] ?? []) as $fieldKey) {
            $field = $this->schema->field($key, $fieldKey, false);
            $result[$fieldKey] = $field ? ($row[$field] ?? null) : null;
        }
        return $result;
    }

    private function jobPostEmployeeData(int $jobPostId): array
    {
        if ($jobPostId <= 0) {
            return [];
        }

        $posts = $this->schema->table('jobPosts', false);
        $postId = $this->schema->field('jobPosts', 'id', false);
        $jobFk = $this->schema->field('jobPosts', 'job_id', false);
        if (! $posts || ! $postId) {
            return [];
        }

        $builder = $this->schema->db()->table($posts . ' jl')
            ->where("jl.{$postId}", $jobPostId);

        $employmentType = $this->schema->field('jobPosts', 'employment_type', false);
        $builder->select($employmentType
            ? "jl.{$employmentType} AS employment_type"
            : 'NULL AS employment_type', false);

        $jobs = $this->schema->table('jobs', false);
        $jobId = $this->schema->field('jobs', 'id', false);
        $jobName = $this->schema->field('jobs', 'name', false);
        if ($jobs && $jobFk && $jobId && $jobName) {
            $builder->select("j.{$jobName} AS position", false)
                ->join($jobs . ' j', "j.{$jobId} = jl.{$jobFk}", 'left', false);
        } else {
            $builder->select('NULL AS position', false);
        }

        $departmentFk = $this->schema->field('jobs', 'department_id', false)
            ?: $this->schema->field('jobPosts', 'department_id', false);
        $departments = $this->schema->table('departments', false);
        $departmentId = $this->schema->field('departments', 'id', false);
        $departmentName = $this->schema->field('departments', 'name', false);

        if ($departments && $departmentFk && $departmentId && $departmentName) {
            $alias = $this->schema->field('jobPosts', 'department_id', false) ? 'jl' : 'j';
            $builder->select("d.{$departmentName} AS department", false)
                ->join($departments . ' d', "d.{$departmentId} = {$alias}.{$departmentFk}", 'left', false);
        } else {
            $builder->select('NULL AS department', false);
        }

        return $builder->get()->getRowArray() ?: [];
    }
}