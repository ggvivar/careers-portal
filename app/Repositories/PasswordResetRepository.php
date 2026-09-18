<?php

namespace App\Repositories;

use App\Services\CareersSchema;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class PasswordResetRepository
{
    private BaseConnection $db;

    private CareersSchema $schema;

    private string $table;

    public function __construct(
        ?BaseConnection $db = null,
        ?CareersSchema $schema = null
    ) {
        $this->db = $db ?? db_connect();
        $this->schema = $schema ?? new CareersSchema($this->db);

        $this->table = $this->schema->table('passwordResets');
    }

    /**
     * Create a password-reset token.
     *
     * A plain token is returned for the email link, while only its
     * SHA-256 hash is stored in the database.
     */
    public function create(int $applicantId): string
    {
        if ($applicantId <= 0) {
            throw new RuntimeException(
                'A valid applicant ID is required.'
            );
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash('sha256', $plainToken);

        $config = config('Careers');

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + ((int) $config->resetMinutes * 60)
        );

        // Invalidate previous unused reset links.
        $this->db
            ->table($this->table)
            ->where(
                $this->field('applicant_id'),
                $applicantId
            )
            ->where(
                $this->field('used_at'),
                null
            )
            ->update([
                $this->field('used_at') => date('Y-m-d H:i:s'),
            ]);

        $inserted = $this->db
            ->table($this->table)
            ->insert([
                $this->field('applicant_id') => $applicantId,
                $this->field('token')        => $tokenHash,
                $this->field('expires_at')   => $expiresAt,
                $this->field('used_at')      => null,
                $this->field('created_at')   => date('Y-m-d H:i:s'),
            ]);

        if (! $inserted) {
            $databaseError = $this->db->error();

            throw new RuntimeException(
                'Unable to create the password-reset token: '
                . ($databaseError['message'] ?? 'Database insert failed.')
            );
        }

        return $plainToken;
    }

    public function invalidateForApplicant(int $applicantId): void
    {
        if ($applicantId <= 0) {
            return;
        }

        $this->db
            ->table($this->table)
            ->where($this->field('applicant_id'), $applicantId)
            ->where($this->field('used_at'), null)
            ->update([
                $this->field('used_at') => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Determine whether a reset token is valid.
     */
    public function valid(string $plainToken): bool
    {
        return $this->findValidToken($plainToken) !== null;
    }

    /**
     * Consume the reset token and return its applicant ID.
     */
    public function consume(string $plainToken): ?int
    {
        $record = $this->findValidToken($plainToken);

        if ($record === null) {
            return null;
        }

        $idField          = $this->field('id');
        $applicantIdField = $this->field('applicant_id');
        $usedAtField      = $this->field('used_at');

        $this->db->transStart();

        $updated = $this->db
            ->table($this->table)
            ->where($idField, $record[$idField])
            ->where($usedAtField, null)
            ->update([
                $usedAtField => date('Y-m-d H:i:s'),
            ]);

        $affectedRows = $this->db->affectedRows();

        $this->db->transComplete();

        if (
            ! $updated
            || $affectedRows !== 1
            || $this->db->transStatus() === false
        ) {
            return null;
        }

        return (int) $record[$applicantIdField];
    }

    /**
     * Find one active password-reset record.
     *
     * @return array<string, mixed>|null
     */
    private function findValidToken(
        string $plainToken
    ): ?array {
        $plainToken = trim($plainToken);

        if ($plainToken === '') {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);

        $record = $this->db
            ->table($this->table)
            ->where($this->field('token'), $tokenHash)
            ->where(
                $this->field('expires_at') . ' >=',
                date('Y-m-d H:i:s')
            )
            ->where($this->field('used_at'), null)
            ->get()
            ->getRowArray();

        return is_array($record)
            ? $record
            : null;
    }

    private function field(string $name): string
    {
        return $this->schema->field(
            'passwordResets',
            $name
        );
    }
}