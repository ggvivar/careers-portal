<?php
namespace App\Repositories;

use App\Services\CareersSchema;

class AuthTokenRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function available(): bool
    {
        return $this->schema->table('tokens', false) !== null
            && $this->schema->hasField('tokens', 'applicant_id')
            && $this->schema->hasField('tokens', 'token');
    }

    public function create(int $applicantId): ?string
    {
        if (! $this->available()) {
            return null;
        }

        $plain = bin2hex(random_bytes(32));
        $payload = [
            $this->schema->field('tokens', 'applicant_id') => $applicantId,
            $this->schema->field('tokens', 'token') => hash('sha256', $plain),
        ];

        $expires = $this->schema->field('tokens', 'expires_at', false);
        if ($expires) {
            $payload[$expires] = date('Y-m-d H:i:s', time() + ($this->schema->config()->rememberDays * DAY));
        }
        $payload += $this->schema->nowPayload('tokens');

        $this->schema->db()->table($this->schema->table('tokens'))->insert($payload);
        return $plain;
    }

    public function resolve(string $plain): ?int
    {
        if (! $this->available() || $plain === '') {
            return null;
        }

        $builder = $this->schema->db()
            ->table($this->schema->table('tokens'))
            ->where($this->schema->field('tokens', 'token'), hash('sha256', $plain));

        $expires = $this->schema->field('tokens', 'expires_at', false);
        if ($expires) {
            $builder->groupStart()
                ->where($expires . ' >=', date('Y-m-d H:i:s'))
                ->orWhere($expires, null)
                ->groupEnd();
        }

        $row = $builder->get()->getRowArray();

        if (! $row) {
            return null;
        }

        $applicantId = (int) $row[$this->schema->field('tokens', 'applicant_id')];
        $lastUsedAt = $this->schema->field('tokens', 'last_used_at', false);

        if ($lastUsedAt) {
            $this->schema->db()
                ->table($this->schema->table('tokens'))
                ->where($this->schema->field('tokens', 'token'), hash('sha256', $plain))
                ->update([$lastUsedAt => date('Y-m-d H:i:s')]);
        }

        return $applicantId;
    }

    public function revokeForApplicant(int $applicantId): void
    {
        if (! $this->available() || $applicantId <= 0) {
            return;
        }

        $this->schema->db()
            ->table($this->schema->table('tokens'))
            ->where($this->schema->field('tokens', 'applicant_id'), $applicantId)
            ->delete();
    }

    public function revoke(string $plain): void
    {
        if ($this->available() && $plain !== '') {
            $this->schema->db()
                ->table($this->schema->table('tokens'))
                ->where($this->schema->field('tokens', 'token'), hash('sha256', $plain))
                ->delete();
        }
    }
}
