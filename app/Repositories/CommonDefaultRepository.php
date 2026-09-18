<?php

namespace App\Repositories;

use App\Services\CareersSchema;

class CommonDefaultRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function options(string $group): array
    {
        $table = $this->schema->table('commonDefaults', false);
        $groupField = $this->schema->field('commonDefaults', 'group', false);
        $valueField = $this->schema->field('commonDefaults', 'value', false);

        if (! $table || ! $groupField || ! $valueField) {
            return [];
        }

        $builder = $this->schema->db()->table($table)
            ->select($valueField)
            ->where($groupField, $group);

        $deletedField = $this->schema->field('commonDefaults', 'deleted_at', false);
        if ($deletedField) {
            $builder->where($deletedField, null);
        }

        $rows = $builder
            ->orderBy($valueField, 'ASC')
            ->get()
            ->getResultArray();

        $options = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row[$valueField] ?? ''));
            if ($value !== '') {
                $options[$value] = $value;
            }
        }

        return $options;
    }

    public function contains(string $group, ?string $value): bool
    {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }

        return array_key_exists($value, $this->options($group));
    }
}
