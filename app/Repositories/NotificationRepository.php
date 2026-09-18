<?php
namespace App\Repositories;

use App\Services\CareersSchema;

class NotificationRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function list(int $applicantId, int $limit = 50): array
    {
        $table = $this->schema->table('notifications', false);
        $applicantField = $this->schema->field('notifications', 'applicant_id', false);
        if (! $table || ! $applicantField) return [];

        $order = $this->schema->field('notifications', 'created_at', false)
            ?: $this->schema->field('notifications', 'id');

        $rows = $this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->orderBy($order, 'DESC')
            ->get($limit)
            ->getResultArray();

        return array_map([$this, 'normalize'], $rows);
    }

    public function unreadCount(int $applicantId): int
    {
        $table = $this->schema->table('notifications', false);
        $applicantField = $this->schema->field('notifications', 'applicant_id', false);
        $readField = $this->schema->field('notifications', 'read', false);
        if (! $table || ! $applicantField || ! $readField) return 0;

        return $this->schema->db()->table($table)
            ->where($applicantField, $applicantId)
            ->where($readField, 0)
            ->countAllResults();
    }

    public function markRead(int $id, int $applicantId): void
    {
        $readField = $this->schema->field('notifications', 'read', false);
        if (! $readField) return;

        $payload = [$readField => 1];
        $readAt = $this->schema->field('notifications', 'read_at', false);
        if ($readAt) $payload[$readAt] = date('Y-m-d H:i:s');

        $this->schema->db()->table($this->schema->table('notifications'))
            ->where($this->schema->field('notifications', 'id'), $id)
            ->where($this->schema->field('notifications', 'applicant_id'), $applicantId)
            ->update($payload);
    }

    private function normalize(array $row): array
    {
        $result = ['raw' => $row];
        foreach (array_keys($this->schema->config()->fields['notifications']) as $key) {
            $field = $this->schema->field('notifications', $key, false);
            $result[$key] = $field ? ($row[$field] ?? null) : null;
        }
        return $result;
    }
}
