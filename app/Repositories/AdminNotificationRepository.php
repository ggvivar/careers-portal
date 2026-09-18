<?php

namespace App\Repositories;

use App\Services\CareersSchema;
use Throwable;

class AdminNotificationRepository
{
    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    /**
     * Create one notification for every administrator who should see the
     * application, including the assigned users and roles with Applications
     * view access. Failures are logged but never undo the applicant action.
     */
    public function notifyApplicationAction(
        array $application,
        array $applicant,
        string $fromStatus,
        string $toStatus,
        ?string $applicantInput = null
    ): int {
        try {
            $notificationTable = $this->schema->table('adminNotifications', false);
            if (! $notificationTable) {
                return 0;
            }

            $applicationId = (int) ($application['id'] ?? 0);
            $applicantId = (int) ($applicant['id'] ?? $application['applicant_id'] ?? 0);
            if ($applicationId <= 0) {
                return 0;
            }

            $recipientIds = $this->recipientUserIds($application);
            if ($recipientIds === []) {
                return 0;
            }

            $applicantName = trim(implode(' ', array_filter([
                (string) ($applicant['first_name'] ?? $applicant['firstname'] ?? ''),
                (string) ($applicant['middle_name'] ?? $applicant['middlename'] ?? ''),
                (string) ($applicant['last_name'] ?? $applicant['lastname'] ?? ''),
            ])));
            if ($applicantName === '') {
                $applicantName = trim((string) ($applicant['email'] ?? 'Applicant')) ?: 'Applicant';
            }

            $jobTitle = trim((string) ($application['title'] ?? ''));
            $actionText = $this->actionText($toStatus);
            $message = $applicantName . ' ' . $actionText;
            if ($jobTitle !== '') {
                $message .= ' for ' . $jobTitle;
            }
            $input = trim((string) $applicantInput);
            if ($input !== '') {
                $preview = mb_strlen($input) > 400 ? mb_substr($input, 0, 400) . '…' : $input;
                $message .= '. Applicant response: ' . $preview;
            }

            $eventKey = sprintf(
                'application:%d:applicant-action:%s:%s',
                $applicationId,
                preg_replace('/[^a-z0-9]+/i', '-', strtolower($toStatus)) ?: 'updated',
                bin2hex(random_bytes(6))
            );
            $now = date('Y-m-d H:i:s');

            $batch = [];
            foreach ($recipientIds as $userId) {
                $batch[] = [
                    $this->schema->field('adminNotifications', 'user_id') => $userId,
                    $this->schema->field('adminNotifications', 'feature_code') => 'applications',
                    $this->schema->field('adminNotifications', 'record_id') => $applicationId,
                    $this->schema->field('adminNotifications', 'applicant_id') => $applicantId ?: null,
                    $this->schema->field('adminNotifications', 'event_key') => $eventKey,
                    $this->schema->field('adminNotifications', 'title') => 'Applicant application update',
                    $this->schema->field('adminNotifications', 'message') => $message,
                    $this->schema->field('adminNotifications', 'type') => $this->notificationType($toStatus),
                    $this->schema->field('adminNotifications', 'action_url') => 'admin/applications/' . $applicationId,
                    $this->schema->field('adminNotifications', 'read') => 0,
                    $this->schema->field('adminNotifications', 'created_at') => $now,
                ];
            }

            if ($batch === []) {
                return 0;
            }

            $this->schema->db()->table($notificationTable)->insertBatch($batch);
            return count($batch);
        } catch (Throwable $exception) {
            log_message('error', 'Unable to create admin application notification: {message}', [
                'message' => $exception->getMessage(),
            ]);
            return 0;
        }
    }

    private function recipientUserIds(array $application): array
    {
        $ids = [];
        foreach (['assigned_to', 'assignee_id'] as $key) {
            $value = (int) ($application[$key] ?? 0);
            if ($value > 0) {
                $ids[$value] = $value;
            }
        }

        $usersTable = $this->schema->table('users', false);
        $userId = $this->schema->field('users', 'id', false);
        if (! $usersTable || ! $userId) {
            return array_values($ids);
        }

        $featureId = $this->applicationFeatureId();
        $permissionsTable = $this->schema->table('rolePermissions', false);
        $roleId = $this->schema->field('users', 'role_id', false);
        $permissionRole = $this->schema->field('rolePermissions', 'role_id', false);
        $permissionFeature = $this->schema->field('rolePermissions', 'feature_id', false);
        $canView = $this->schema->field('rolePermissions', 'can_view', false);

        if ($featureId && $permissionsTable && $roleId && $permissionRole && $permissionFeature && $canView) {
            $builder = $this->schema->db()->table($usersTable . ' u')
                ->distinct()
                ->select("u.{$userId} AS id", false)
                ->join($permissionsTable . ' p', "p.{$permissionRole} = u.{$roleId}", 'inner', false)
                ->where("p.{$permissionFeature}", $featureId)
                ->where("p.{$canView}", 1);
            $this->excludeDeletedUsers($builder);

            foreach ($builder->get()->getResultArray() as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        // If no permission rows are configured yet, notify active admin users
        // rather than silently losing an applicant response.
        if ($ids === []) {
            $builder = $this->schema->db()->table($usersTable . ' u')
                ->select("u.{$userId} AS id", false);
            $this->excludeDeletedUsers($builder);
            foreach ($builder->get()->getResultArray() as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        return array_values($ids);
    }

    private function applicationFeatureId(): ?int
    {
        $table = $this->schema->table('features', false);
        $id = $this->schema->field('features', 'id', false);
        $code = $this->schema->field('features', 'code', false);
        if (! $table || ! $id || ! $code) {
            return null;
        }

        $builder = $this->schema->db()->table($table)
            ->select($id)
            ->where("LOWER(TRIM({$code}))", 'applications');
        $deleted = $this->schema->field('features', 'deleted_at', false);
        if ($deleted) {
            $builder->where($deleted, null);
        }
        $row = $builder->get()->getRowArray();

        return $row ? (int) $row[$id] : null;
    }

    private function excludeDeletedUsers($builder): void
    {
        $deleted = $this->schema->field('users', 'deleted_at', false);
        if ($deleted) {
            $builder->where('u.' . $deleted, null);
        }
    }

    private function actionText(string $toStatus): string
    {
        $value = strtolower($toStatus);
        if (str_contains($value, 'withdraw')) {
            return 'withdrew an application';
        }
        if (str_contains($value, 'declin')) {
            return 'declined the job offer';
        }
        if (str_contains($value, 'hire') || str_contains($value, 'accept')) {
            return 'accepted the job offer';
        }

        return 'updated an application to ' . $toStatus;
    }

    private function notificationType(string $toStatus): string
    {
        $value = strtolower($toStatus);
        if (str_contains($value, 'declin') || str_contains($value, 'withdraw')) {
            return 'warning';
        }
        if (str_contains($value, 'hire') || str_contains($value, 'accept')) {
            return 'success';
        }

        return 'info';
    }
}
