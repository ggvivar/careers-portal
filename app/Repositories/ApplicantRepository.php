<?php
namespace App\Repositories;

use App\Services\CareersSchema;
use RuntimeException;

class ApplicantRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function findByEmail(string $email): ?array
    {
        $row = $this->schema->db()
            ->table($this->schema->table('applicants'))
            ->where($this->schema->field('applicants', 'email'), strtolower(trim($email)))
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $row = $this->schema->db()
            ->table($this->schema->table('applicants'))
            ->where($this->schema->field('applicants', 'id'), $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $payload = $this->map($data, [
            'email', 'password', 'first_name', 'middle_name', 'last_name',
            'birthdate', 'phone', 'country', 'consent', 'consented_at',
        ]);
        $payload += $this->schema->nowPayload('applicants');

        $activeField = $this->schema->field('applicants', 'active', false);
        if ($activeField) {
            $payload[$activeField] = 1;
        }

        if (! $this->schema->db()->table($this->schema->table('applicants'))->insert($payload)) {
            throw new RuntimeException('Unable to create the applicant record.');
        }

        return (int) $this->schema->db()->insertID();
    }

    public function delete(int $id): void
    {
        $this->schema->db()
            ->table($this->schema->table('applicants'))
            ->where($this->schema->field('applicants', 'id'), $id)
            ->delete();
    }

    public function updateProfile(int $id, array $data): void
    {
        $payload = $this->map($data, [
            'email', 'first_name', 'middle_name', 'last_name', 'suffix', 'birthdate',
            'gender', 'nationality', 'phone',
            'city', 'province', 'country', 'zip_code', 'linkedin_url', 'portfolio_url',
            'religion', 'civil_status', 'current_address', 'permanent_address',
            'covid_vaccinated', 'has_company_relative', 'company_relative_name',
            'company_relative_relation', 'company_relative_relation_other',
        ]);
        $payload += $this->schema->nowPayload('applicants', true);

        $this->schema->db()
            ->table($this->schema->table('applicants'))
            ->where($this->schema->field('applicants', 'id'), $id)
            ->update($payload);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $payload = [$this->schema->field('applicants', 'password') => $passwordHash];
        $payload += $this->schema->nowPayload('applicants', true);

        $this->schema->db()
            ->table($this->schema->table('applicants'))
            ->where($this->schema->field('applicants', 'id'), $id)
            ->update($payload);
    }

    public function verifyPassword(array $applicant, string $plainPassword): bool
    {
        $field = $this->schema->field('applicants', 'password');
        $hash = (string) ($applicant[$field] ?? '');

        return $hash !== '' && password_verify($plainPassword, $hash);
    }

    public function isActive(array $applicant): bool
    {
        $field = $this->schema->field('applicants', 'active', false);

        if ($field === null) {
            return true;
        }

        $value = strtolower(trim((string) ($applicant[$field] ?? '')));

        return in_array($value, ['1', 'true', 'yes', 'active', 'enabled'], true);
    }

    public function displayName(array $applicant): string
    {
        $parts = [];
        foreach (['first_name', 'middle_name', 'last_name'] as $key) {
            $field = $this->schema->field('applicants', $key, false);
            if ($field && trim((string) ($applicant[$field] ?? '')) !== '') {
                $parts[] = trim((string) $applicant[$field]);
            }
        }

        return $parts ? implode(' ', $parts) : 'Applicant';
    }

    public function email(array $applicant): string
    {
        return (string) ($applicant[$this->schema->field('applicants', 'email')] ?? '');
    }

    public function normalized(array $applicant): array
    {
        $result = ['raw' => $applicant];

        foreach ([
            'id', 'email', 'first_name', 'middle_name', 'last_name', 'suffix',
            'birthdate', 'gender', 'nationality', 'phone',
            'city', 'province', 'country', 'zip_code', 'linkedin_url', 'portfolio_url',
            'religion', 'civil_status', 'current_address', 'permanent_address',
            'covid_vaccinated', 'has_company_relative', 'company_relative_name',
            'company_relative_relation', 'company_relative_relation_other',
        ] as $key) {
            $field = $this->schema->field('applicants', $key, false);
            $result[$key] = $field ? ($applicant[$field] ?? null) : null;
        }

        $result['display_name'] = $this->displayName($applicant);
        return $result;
    }

    private function map(array $data, array $keys): array
    {
        $payload = [];
        foreach ($keys as $key) {
            $field = $this->schema->field('applicants', $key, false);
            if ($field && array_key_exists($key, $data)) {
                $payload[$field] = $data[$key];
            }
        }
        return $payload;
    }
}
