<?php
namespace App\Repositories;

use App\Services\CareersSchema;
use CodeIgniter\Database\BaseBuilder;

class JobRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function paginate(array $filters, int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $perPage = min(50, max(1, $perPage));
        $total = $this->baseBuilder($filters)->countAllResults();

        $rows = $this->baseBuilder($filters)
            ->orderBy($this->createdOrder(), 'DESC')
            ->get($perPage, ($page - 1) * $perPage)
            ->getResultArray();

        return [
            'items' => array_map([$this, 'normalize'], $rows),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /** @param list<int> $ids */
    public function findPublicMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $rows = $this->baseBuilder([])
            ->whereIn(
                'jl.' . $this->schema->field('jobPosts', 'id'),
                $ids
            )
            ->get()
            ->getResultArray();

        return array_map([$this, 'normalize'], $rows);
    }

    public function findPublic(int $id): ?array
    {
        $row = $this->baseBuilder([])
            ->where(
                'jl.' . $this->schema->field('jobPosts', 'id'),
                $id
            )
            ->get()
            ->getRowArray();

        return $row ? $this->normalize($row) : null;
    }

    private function baseBuilder(array $filters): BaseBuilder
    {
        $builder = $this->schema->db()
            ->table($this->schema->table('jobPosts') . ' jl');

        $postId = $this->schema->field('jobPosts', 'id');
        $builder->select("jl.{$postId} AS id", false);

        $jobTable = $this->schema->table('jobs', false);
        $jobFk = $this->schema->field('jobPosts', 'job_id', false);
        $jobId = $this->schema->field('jobs', 'id', false);

        if ($jobTable && $jobFk && $jobId) {
            $builder->join(
                $jobTable . ' j',
                "j.{$jobId} = jl.{$jobFk}",
                'left',
                false
            );
        }

        $titleParts = [];
        $codeParts = [];

        $jobName = $this->schema->field('jobs', 'name', false);
        $jobCode = $this->schema->field('jobs', 'code', false);
        $postTitle = $this->schema->field('jobPosts', 'title', false);
        $postCode = $this->schema->field('jobPosts', 'job_code', false);

        if ($jobTable && $jobName) {
            $titleParts[] = "j.{$jobName}";
        }
        if ($postTitle) {
            $titleParts[] = "jl.{$postTitle}";
        }
        if ($jobTable && $jobCode) {
            $codeParts[] = "j.{$jobCode}";
        }
        if ($postCode) {
            $codeParts[] = "jl.{$postCode}";
        }

        $builder->select(
            ($titleParts
                ? 'COALESCE(' . implode(', ', $titleParts) . ')'
                : "'Untitled position'")
            . ' AS title',
            false
        );

        $builder->select(
            ($codeParts
                ? 'COALESCE(' . implode(', ', $codeParts) . ')'
                : 'NULL')
            . ' AS job_code',
            false
        );

        foreach ([
            'location',
            'employment_type',
            'salary_range',
            'experience_range',
            'description',
            'responsibilities',
            'requirements',
            'valid_until',
        ] as $key) {
            $field = $this->schema->field('jobPosts', $key, false);

            $builder->select(
                $field
                    ? "jl.{$field} AS {$key}"
                    : "NULL AS {$key}",
                false
            );
        }

        $companyTable = $this->schema->table('companies', false);
        $companyFk = $this->schema->field('jobPosts', 'company_id', false);
        $companyId = $this->schema->field('companies', 'id', false);
        $companyName = $this->schema->field('companies', 'name', false);

        if ($companyTable && $companyFk && $companyId && $companyName) {
            $builder->join(
                $companyTable . ' c',
                "c.{$companyId} = jl.{$companyFk}",
                'left',
                false
            );
            $builder->select("c.{$companyName} AS company_name", false);
        } else {
            $builder->select('NULL AS company_name', false);
        }

        $departmentTable = $this->schema->table('departments', false);
        $departmentId = $this->schema->field('departments', 'id', false);
        $departmentName = $this->schema->field('departments', 'name', false);
        $jobDepartment = $this->schema->field('jobs', 'department_id', false);

        if (
            $jobTable
            && $departmentTable
            && $departmentId
            && $departmentName
            && $jobDepartment
        ) {
            $builder->join(
                $departmentTable . ' d',
                "d.{$departmentId} = j.{$jobDepartment}",
                'left',
                false
            );
            $builder->select("d.{$departmentName} AS department_name", false);
        } else {
            $builder->select('NULL AS department_name', false);
        }

        $statusTable = $this->schema->table('statuses', false);
        $statusFk = $this->schema->field('jobPosts', 'status_id', false);
        $statusId = $this->schema->field('statuses', 'id', false);
        $statusName = $this->schema->field('statuses', 'name', false);

        if ($statusTable && $statusFk && $statusId && $statusName) {
            $builder->join(
                $statusTable . ' s',
                "s.{$statusId} = jl.{$statusFk}",
                'left',
                false
            );
            $builder->select("s.{$statusName} AS status_name", false);

            if ($this->schema->config()->publicJobStatuses) {
                $builder->whereIn(
                    "s.{$statusName}",
                    $this->schema->config()->publicJobStatuses
                );
            }
        } else {
            $builder->select('NULL AS status_name', false);
        }

        $valid = $this->schema->field('jobPosts', 'valid_until', false);

        if ($valid) {
            $builder->groupStart()
                ->where("jl.{$valid} >=", date('Y-m-d'))
                ->orWhere("jl.{$valid}", null)
                ->groupEnd();
        }

        $q = trim((string) ($filters['q'] ?? ''));

        if ($q !== '') {
            $expressions = [];

            if ($jobTable && $jobName) {
                $expressions[] = "j.{$jobName}";
            }
            if ($jobTable && $jobCode) {
                $expressions[] = "j.{$jobCode}";
            }
            if ($postTitle) {
                $expressions[] = "jl.{$postTitle}";
            }
            if ($postCode) {
                $expressions[] = "jl.{$postCode}";
            }

            $location = $this->schema->field(
                'jobPosts',
                'location',
                false
            );

            if ($location) {
                $expressions[] = "jl.{$location}";
            }

            if ($expressions !== []) {
                $builder->groupStart();

                foreach ($expressions as $index => $expression) {
                    $index === 0
                        ? $builder->like($expression, $q)
                        : $builder->orLike($expression, $q);
                }

                $builder->groupEnd();
            }
        }

        $locationValue = trim((string) ($filters['location'] ?? ''));
        $locationField = $this->schema->field(
            'jobPosts',
            'location',
            false
        );

        if ($locationValue !== '' && $locationField) {
            $builder->where("jl.{$locationField}", $locationValue);
        }

        $typeValue = trim((string) ($filters['type'] ?? ''));
        $typeField = $this->schema->field(
            'jobPosts',
            'employment_type',
            false
        );

        if ($typeValue !== '' && $typeField) {
            $builder->where("jl.{$typeField}", $typeValue);
        }

        return $builder;
    }

    private function createdOrder(): string
    {
        $field = $this->schema->field('jobPosts', 'created_at', false);

        return $field
            ? "jl.{$field}"
            : 'jl.' . $this->schema->field('jobPosts', 'id');
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        foreach ([
            'title',
            'job_code',
            'location',
            'employment_type',
            'salary_range',
            'experience_range',
            'description',
            'responsibilities',
            'requirements',
            'valid_until',
            'company_name',
            'department_name',
            'status_name',
        ] as $key) {
            $row[$key] = $row[$key] ?? null;
        }

        $row['title'] = trim((string) $row['title'])
            ?: 'Untitled position';

        $row['salary_label'] = $this->salaryLabel(
            $row['salary_range']
        );

        $row['experience_label'] = $this->plainLabel(
            $row['experience_range']
        );

        return $row;
    }

    private function salaryLabel(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        // Preserve descriptive values such as "Negotiable" or values
        // that already include a currency or pay-period label.
        if (
            preg_match('/[A-Za-z₱$€£¥]/u', $raw)
            || str_contains($raw, '/')
        ) {
            return $raw;
        }

        $normalized = str_replace(',', '', $raw);

        if (
            preg_match(
                '/^\s*(\d+(?:\.\d+)?)\s*(?:-|–|—|to)\s*(\d+(?:\.\d+)?)\s*$/iu',
                $normalized,
                $matches
            )
        ) {
            return '₱'
                . $this->formatMoney((float) $matches[1])
                . '–₱'
                . $this->formatMoney((float) $matches[2]);
        }

        if (is_numeric($normalized)) {
            return '₱' . $this->formatMoney((float) $normalized);
        }

        return $raw;
    }

    private function plainLabel(mixed $value): ?string
    {
        $label = trim((string) $value);

        return $label !== '' ? $label : null;
    }

    private function formatMoney(float $value): string
    {
        return number_format(
            $value,
            floor($value) === $value ? 0 : 2
        );
    }
}
