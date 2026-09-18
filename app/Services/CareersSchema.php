<?php
namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\Careers;
use RuntimeException;

class CareersSchema
{
    private BaseConnection $db;
    private Careers $config;
    private array $fieldNames = [];
    private array $resolvedFields = [];

    public function __construct(?BaseConnection $db = null, ?Careers $config = null)
    {
        $this->db = $db ?? db_connect();
        $this->config = $config ?? config('Careers');
    }

    public function db(): BaseConnection { return $this->db; }
    public function config(): Careers { return $this->config; }

    public function table(string $key, bool $required = true): ?string
    {
        $table = $this->config->tables[$key] ?? null;

        if (is_string($table) && $table !== '' && $this->db->tableExists($table)) {
            return $table;
        }

        if ($required) {
            throw new RuntimeException(
                sprintf('Required Careers table "%s" was not found. Configured name: %s', $key, $table ?: '(empty)')
            );
        }

        return null;
    }

    public function field(string $tableKey, string $fieldKey, bool $required = true): ?string
    {
        $cacheKey = $tableKey . '.' . $fieldKey;

        if (array_key_exists($cacheKey, $this->resolvedFields)) {
            $value = $this->resolvedFields[$cacheKey];
            if ($required && $value === null) {
                throw new RuntimeException("Required Careers field {$cacheKey} was not found.");
            }
            return $value;
        }

        $table = $this->table($tableKey, $required);
        if ($table === null) {
            return $this->resolvedFields[$cacheKey] = null;
        }

        $fields = $this->fieldNames[$table] ??= $this->db->getFieldNames($table);
        $candidates = $this->config->fields[$tableKey][$fieldKey] ?? [];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $this->resolvedFields[$cacheKey] = $candidate;
            }
        }

        $this->resolvedFields[$cacheKey] = null;

        if ($required) {
            throw new RuntimeException(
                sprintf(
                    'Required Careers field "%s.%s" was not found. Candidates: %s',
                    $tableKey,
                    $fieldKey,
                    implode(', ', $candidates)
                )
            );
        }

        return null;
    }

    public function hasField(string $tableKey, string $fieldKey): bool
    {
        return $this->field($tableKey, $fieldKey, false) !== null;
    }

    public function nowPayload(string $tableKey, bool $update = false): array
    {
        $field = $this->field($tableKey, $update ? 'updated_at' : 'created_at', false);
        return $field ? [$field => date('Y-m-d H:i:s')] : [];
    }

    public function diagnostics(): array
    {
        $report = [];

        foreach ($this->config->tables as $key => $configuredName) {
            $exists = $this->db->tableExists($configuredName);
            $resolved = [];

            foreach (array_keys($this->config->fields[$key] ?? []) as $fieldKey) {
                $resolved[$fieldKey] = $exists ? $this->field($key, $fieldKey, false) : null;
            }

            $report[$key] = [
                'configured' => $configuredName,
                'exists' => $exists,
                'fields' => $resolved,
            ];
        }

        return $report;
    }
}
