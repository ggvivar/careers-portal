<?php

namespace App\Repositories;

use App\Services\CareersSchema;
use RuntimeException;
use Throwable;

class ApplicationRepository
{
    private ?int $applicationsFeatureId = null;

    public function __construct(private ?CareersSchema $schema = null)
    {
        $this->schema ??= new CareersSchema();
    }

    public function exists(int $applicantId, int $jobPostId): bool
    {
        return $this->schema->db()
            ->table($this->schema->table('applications'))
            ->where($this->schema->field('applications', 'applicant_id'), $applicantId)
            ->where($this->schema->field('applications', 'job_post_id'), $jobPostId)
            ->countAllResults() > 0;
    }

    /**
     * Return the job-post IDs already applied to by an applicant.
     *
     * @return list<int>
     */
    public function jobPostIdsForApplicant(int $applicantId): array
    {
        if ($applicantId <= 0) {
            return [];
        }

        $table = $this->schema->table('applications');
        $applicantField = $this->schema->field('applications', 'applicant_id');
        $jobPostField = $this->schema->field('applications', 'job_post_id');

        $rows = $this->schema->db()
            ->table($table)
            ->select($jobPostField)
            ->where($applicantField, $applicantId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row[$jobPostField] ?? 0),
            $rows
        ))));
    }

    public function create(int $applicantId, int $jobPostId, array $data): int
    {
        if ($this->exists($applicantId, $jobPostId)) {
            throw new RuntimeException('You have already applied for this position.');
        }

        $payload = [
            $this->schema->field('applications', 'applicant_id') => $applicantId,
            $this->schema->field('applications', 'job_post_id') => $jobPostId,
        ];

        foreach (['source', 'cover_letter'] as $key) {
            $field = $this->schema->field('applications', $key, false);
            if ($field && array_key_exists($key, $data)) {
                $payload[$field] = $data[$key];
            }
        }

        $initial = $this->initialWorkflowTransition();
        $statusId = $initial ? (int) ($initial['status_id_to'] ?? 0) : 0;
        $statusName = $statusId > 0 ? ($this->statusName($statusId) ?: 'Submitted') : '';

        if ($statusId <= 0) {
            $statusName = $this->schema->config()->initialApplicationStatuses[0] ?? 'Submitted';
            $statusId = (int) ($this->statusIdByNames($this->schema->config()->initialApplicationStatuses) ?? 0);
        }

        $statusField = $this->schema->field('applications', 'status_id', false);
        if ($statusField && $statusId > 0) {
            $payload[$statusField] = $statusId;
        }

        $statusText = $this->schema->field('applications', 'status_text', false);
        if ($statusText) {
            $payload[$statusText] = $statusName !== '' ? $statusName : 'Submitted';
        }

        $dueAtField = $this->schema->field('applications', 'due_at', false);
        $dueAt = $this->dueAt($initial['grace_period'] ?? null);
        if ($dueAtField) {
            $payload[$dueAtField] = $dueAt;
        }

        $appliedAt = $this->schema->field('applications', 'applied_at', false);
        if ($appliedAt) {
            $payload[$appliedAt] = date('Y-m-d H:i:s');
        }
        $payload += $this->schema->nowPayload('applications');

        $db = $this->schema->db();
        $db->transStart();

        if (! $db->table($this->schema->table('applications'))->insert($payload)) {
            $db->transRollback();
            throw new RuntimeException('Unable to submit the application.');
        }

        $id = (int) $db->insertID();
        $this->addHistory(
            $id,
            null,
            $statusId > 0 ? $statusId : null,
            $statusName !== '' ? $statusName : 'Submitted',
            'Application submitted through Apply Portal.',
            $applicantId,
            null,
            $dueAt
        );

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException('Unable to submit the application.');
        }

        return $id;
    }

    public function deleteOwned(int $applicationId, int $applicantId): void
    {
        $this->schema->db()
            ->table($this->schema->table('applications'))
            ->where($this->schema->field('applications', 'id'), $applicationId)
            ->where($this->schema->field('applications', 'applicant_id'), $applicantId)
            ->delete();
    }

    public function listForApplicant(int $applicantId): array
    {
        $applications = $this->schema->table('applications');
        $applicationId = $this->schema->field('applications', 'id');
        $applicantField = $this->schema->field('applications', 'applicant_id');
        $postFk = $this->schema->field('applications', 'job_post_id');
        $posts = $this->schema->table('jobPosts');
        $postId = $this->schema->field('jobPosts', 'id');

        $builder = $this->schema->db()->table($applications . ' a')
            ->select("a.{$applicationId} AS id", false)
            ->select("a.{$applicantField} AS applicant_id", false)
            ->select("a.{$postFk} AS job_post_id", false)
            ->join($posts . ' jl', "jl.{$postId} = a.{$postFk}", 'left', false)
            ->where("a.{$applicantField}", $applicantId);

        $this->joinJob($builder);

        foreach (['status_id', 'assigned_to', 'assignee_id', 'due_at', 'withdrawn_at', 'withdrawal_reason'] as $key) {
            $field = $this->schema->field('applications', $key, false);
            $builder->select($field ? "a.{$field} AS {$key}" : "NULL AS {$key}", false);
        }

        $statusText = $this->schema->field('applications', 'status_text', false);
        $statusId = $this->schema->field('applications', 'status_id', false);
        $statusTable = $this->schema->table('statuses', false);
        $statusPk = $this->schema->field('statuses', 'id', false);
        $statusName = $this->schema->field('statuses', 'name', false);

        if ($statusTable && $statusId && $statusPk && $statusName) {
            $builder->join($statusTable . ' s', "s.{$statusPk} = a.{$statusId}", 'left', false);
            $expression = $statusText
                ? "COALESCE(s.{$statusName}, a.{$statusText})"
                : "s.{$statusName}";
            $builder->select($expression . ' AS status_name', false);
        } elseif ($statusText) {
            $builder->select("a.{$statusText} AS status_name", false);
        } else {
            $builder->select("'Submitted' AS status_name", false);
        }

        $appliedAt = $this->schema->field('applications', 'applied_at', false);
        if ($appliedAt) {
            $builder->select("a.{$appliedAt} AS applied_at", false)
                ->orderBy("a.{$appliedAt}", 'DESC');
        } else {
            $builder->select('NULL AS applied_at', false)
                ->orderBy("a.{$applicationId}", 'DESC');
        }

        return array_map([$this, 'decorate'], $builder->get()->getResultArray());
    }

    public function findOwned(int $id, int $applicantId): ?array
    {
        foreach ($this->listForApplicant($applicantId) as $row) {
            if ((int) $row['id'] === $id) {
                $row['history'] = $this->history($id);
                $row['applicant_actions'] = $this->applicantActions(
                    isset($row['status_id']) && $row['status_id'] !== null ? (int) $row['status_id'] : null
                );
                return $row;
            }
        }

        return null;
    }

    public function applicantActions(?int $fromStatusId): array
    {
        $table = $this->schema->table('workflowTransitions', false);
        $featureId = $this->applicationsFeatureId();
        if (! $table || ! $featureId) {
            return [];
        }

        $id = $this->schema->field('workflowTransitions', 'id', false);
        $feature = $this->schema->field('workflowTransitions', 'feature_id', false);
        $from = $this->schema->field('workflowTransitions', 'status_from', false);
        $to = $this->schema->field('workflowTransitions', 'status_to', false);
        $enabled = $this->schema->field('workflowTransitions', 'applicant_action_enabled', false);
        if (! $id || ! $feature || ! $from || ! $to || ! $enabled) {
            return [];
        }

        $builder = $this->schema->db()->table($table . ' wt')
            ->select("wt.{$id} AS transition_id, wt.{$to} AS status_id_to", false)
            ->where("wt.{$feature}", $featureId)
            ->where("wt.{$enabled}", 1);

        if ($fromStatusId === null || $fromStatusId <= 0) {
            $builder->groupStart()->where("wt.{$from}", null)->orWhere("wt.{$from}", 0)->groupEnd();
        } else {
            $builder->where("wt.{$from}", $fromStatusId);
        }

        $active = $this->schema->field('workflowTransitions', 'active', false);
        if ($active) {
            $builder->where("wt.{$active}", 1);
        }
        $deleted = $this->schema->field('workflowTransitions', 'deleted_at', false);
        if ($deleted) {
            $builder->where("wt.{$deleted}", null);
        }

        foreach ([
            'grace_period', 'require_remarks', 'applicant_action_label', 'applicant_prompt',
            'applicant_input_type', 'applicant_input_label', 'applicant_input_required',
        ] as $key) {
            $field = $this->schema->field('workflowTransitions', $key, false);
            $builder->select($field ? "wt.{$field} AS {$key}" : "NULL AS {$key}", false);
        }

        $statuses = $this->schema->table('statuses', false);
        $statusId = $this->schema->field('statuses', 'id', false);
        $statusName = $this->schema->field('statuses', 'name', false);
        if ($statuses && $statusId && $statusName) {
            $builder->join($statuses . ' st', "st.{$statusId} = wt.{$to}", 'left', false)
                ->select("st.{$statusName} AS status_name", false);
        } else {
            $builder->select('NULL AS status_name', false);
        }

        $rows = $builder->orderBy('wt.id', 'ASC')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['transition_id'] = (int) ($row['transition_id'] ?? 0);
            $row['status_id_to'] = (int) ($row['status_id_to'] ?? 0);
            $row['applicant_action_label'] = trim((string) ($row['applicant_action_label'] ?? ''))
                ?: ('Update to ' . (trim((string) ($row['status_name'] ?? '')) ?: 'next status'));
            $row['applicant_input_type'] = in_array(
                strtolower((string) ($row['applicant_input_type'] ?? 'none')),
                ['none', 'text', 'textarea'],
                true
            ) ? strtolower((string) ($row['applicant_input_type'] ?? 'none')) : 'none';
            $row['applicant_input_required'] = (int) ($row['applicant_input_required'] ?? 0);
            $row['require_remarks'] = (int) ($row['require_remarks'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    public function performApplicantAction(
        int $applicationId,
        int $applicantId,
        int $toStatusId,
        ?string $applicantInput = null
    ): array {
        $input = trim((string) $applicantInput);
        if (mb_strlen($input) > 2000) {
            throw new RuntimeException('Applicant response must not exceed 2,000 characters.');
        }

        $application = $this->findOwned($applicationId, $applicantId);
        if (! $application) {
            throw new RuntimeException('Application not found.');
        }

        $fromStatusId = isset($application['status_id']) && $application['status_id'] !== null
            ? (int) $application['status_id']
            : null;
        $action = null;
        foreach ($application['applicant_actions'] ?? [] as $candidate) {
            if ((int) ($candidate['status_id_to'] ?? 0) === $toStatusId) {
                $action = $candidate;
                break;
            }
        }

        if (! $action) {
            throw new RuntimeException('This applicant action is no longer available for the current status.');
        }

        $inputType = (string) ($action['applicant_input_type'] ?? 'none');
        $required = (int) ($action['applicant_input_required'] ?? 0) === 1
            || (int) ($action['require_remarks'] ?? 0) === 1;
        if ($inputType !== 'none' && $required && $input === '') {
            $label = trim((string) ($action['applicant_input_label'] ?? 'Applicant response'));
            throw new RuntimeException(($label !== '' ? $label : 'Applicant response') . ' is required.');
        }

        $toStatusName = trim((string) ($action['status_name'] ?? '')) ?: ($this->statusName($toStatusId) ?: 'Updated');
        $fromStatusName = trim((string) ($application['status_name'] ?? '')) ?: 'Current status';
        $dueAt = $this->dueAt($action['grace_period'] ?? null);

        $payload = [];
        $statusIdField = $this->schema->field('applications', 'status_id', false);
        if ($statusIdField) {
            $payload[$statusIdField] = $toStatusId;
        }
        $statusTextField = $this->schema->field('applications', 'status_text', false);
        if ($statusTextField) {
            $payload[$statusTextField] = $toStatusName;
        }
        $dueAtField = $this->schema->field('applications', 'due_at', false);
        if ($dueAtField) {
            $payload[$dueAtField] = $dueAt;
        }

        if (str_contains(strtolower($toStatusName), 'withdraw')) {
            $withdrawnAt = $this->schema->field('applications', 'withdrawn_at', false);
            if ($withdrawnAt) {
                $payload[$withdrawnAt] = date('Y-m-d H:i:s');
            }
            $reasonField = $this->schema->field('applications', 'withdrawal_reason', false);
            if ($reasonField) {
                $payload[$reasonField] = $input !== '' ? $input : null;
            }
        }
        $payload += $this->schema->nowPayload('applications', true);

        $remarks = 'Applicant selected: ' . (string) $action['applicant_action_label'] . '.';
        if ($input !== '') {
            $remarks .= ' Applicant response recorded.';
        }

        $db = $this->schema->db();
        $db->transStart();
        $updated = $db->table($this->schema->table('applications'))
            ->where($this->schema->field('applications', 'id'), $applicationId)
            ->where($this->schema->field('applications', 'applicant_id'), $applicantId);
        if ($statusIdField && $fromStatusId) {
            $updated->where($statusIdField, $fromStatusId);
        }
        $updated->update($payload);

        if ($db->affectedRows() < 1) {
            $db->transRollback();
            throw new RuntimeException('The application changed before this response was saved. Please reload and try again.');
        }

        $this->addHistory(
            $applicationId,
            $fromStatusId,
            $toStatusId,
            $toStatusName,
            $remarks,
            $applicantId,
            $input !== '' ? $input : null,
            $dueAt
        );
        $this->createApplicantNotification(
            $applicantId,
            'Application response recorded',
            'Your response "' . (string) $action['applicant_action_label'] . '" was recorded for ' . (string) ($application['title'] ?? 'your application') . '.'
        );
        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new RuntimeException('Unable to update the application.');
        }

        // When the applicant action moves the application to Hired/Onboarding,
        // create or synchronize the shared Careers employee-details record.
        (new ProfileRepository($this->schema))
            ->ensureEmployeeDetailsForHiredApplication($applicationId);

        $applicant = (new ApplicantRepository($this->schema))->normalized(
            (new ApplicantRepository($this->schema))->find($applicantId) ?? []
        );
        (new AdminNotificationRepository($this->schema))->notifyApplicationAction(
            $application,
            $applicant,
            $fromStatusName,
            $toStatusName,
            $input !== '' ? $input : null
        );

        return [
            'action_label' => (string) $action['applicant_action_label'],
            'status_name' => $toStatusName,
        ];
    }

    public function withdrawOwned(int $applicationId, int $applicantId, ?string $reason = null): void
    {
        $application = $this->findOwned($applicationId, $applicantId);
        if (! $application) {
            throw new RuntimeException('Application not found.');
        }

        foreach ($application['applicant_actions'] ?? [] as $action) {
            if (str_contains(strtolower((string) ($action['status_name'] ?? '')), 'withdraw')) {
                $this->performApplicantAction(
                    $applicationId,
                    $applicantId,
                    (int) $action['status_id_to'],
                    $reason
                );
                return;
            }
        }

        // Backward-compatible fallback for installations that have not yet
        // configured applicant workflow actions.
        if (! $application['can_withdraw']) {
            throw new RuntimeException('This application can no longer be withdrawn.');
        }

        $statusName = 'Withdrawn';
        $statusId = $this->statusIdByNames(['Withdrawn', 'Applicant Withdrawn', 'Withdraw']);
        if (! $statusId) {
            throw new RuntimeException('A Withdraw status and workflow transition are required before applications can be withdrawn.');
        }

        $this->fallbackApplicantStatusChange($application, $applicantId, $statusId, $statusName, $reason);
    }

    public function history(int $applicationId): array
    {
        $table = $this->schema->table('applicationHistories', false);
        $applicationField = $this->schema->field('applicationHistories', 'application_id', false);
        if (! $table || ! $applicationField) {
            return [];
        }

        $builder = $this->schema->db()->table($table . ' h')
            ->where("h.{$applicationField}", $applicationId);

        $featureField = $this->schema->field('applicationHistories', 'feature_id', false);
        $featureId = $this->applicationsFeatureId();
        if ($featureField && $featureId) {
            $builder->where("h.{$featureField}", $featureId);
        }

        $statusText = $this->schema->field('applicationHistories', 'status_text', false);
        $statusId = $this->schema->field('applicationHistories', 'status_id', false);
        $statusTable = $this->schema->table('statuses', false);
        $statusPk = $this->schema->field('statuses', 'id', false);
        $statusName = $this->schema->field('statuses', 'name', false);

        if ($statusTable && $statusId && $statusPk && $statusName) {
            $builder->join($statusTable . ' s', "s.{$statusPk} = h.{$statusId}", 'left', false);
            $expression = $statusText
                ? "COALESCE(s.{$statusName}, h.{$statusText})"
                : "s.{$statusName}";
            $builder->select($expression . ' AS status_name', false);
        } elseif ($statusText) {
            $builder->select("h.{$statusText} AS status_name", false);
        } else {
            $builder->select('NULL AS status_name', false);
        }

        foreach (['remarks', 'applicant_input', 'actor_type'] as $key) {
            $field = $this->schema->field('applicationHistories', $key, false);
            $builder->select($field ? "h.{$field} AS {$key}" : "NULL AS {$key}", false);
        }
        $created = $this->schema->field('applicationHistories', 'created_at', false);
        $builder->select($created ? "h.{$created} AS created_at" : 'NULL AS created_at', false);
        if ($created) {
            $builder->orderBy("h.{$created}", 'DESC');
        }

        return $builder->get()->getResultArray();
    }

    private function decorate(array $row): array
    {
        $status = trim((string) ($row['status_name'] ?? 'Submitted')) ?: 'Submitted';
        $progress = $this->progressForStatus($status);

        $row['status_name'] = $status;
        $row['progress'] = $progress;
        $row['can_withdraw'] = ! $progress['terminal'];

        return $row;
    }

    private function progressForStatus(string $status): array
    {
        $value = strtolower($status);
        $terminal = false;
        $tone = 'primary';
        $active = 0;
        $percent = 20;

        if (preg_match('/withdraw/', $value)) {
            $terminal = true;
            $tone = 'secondary';
            $active = 4;
            $percent = 100;
        } elseif (preg_match('/reject|declin|not selected|failed|closed/', $value)) {
            $terminal = true;
            $tone = 'danger';
            $active = 4;
            $percent = 100;
        } elseif (preg_match('/hired|onboard|accepted/', $value)) {
            $terminal = true;
            $tone = 'success';
            $active = 4;
            $percent = 100;
        } elseif (preg_match('/offer|decision|final/', $value)) {
            $active = 3;
            $percent = 80;
        } elseif (preg_match('/interview|assessment|exam|test/', $value)) {
            $active = 2;
            $percent = 60;
        } elseif (preg_match('/screen|review|shortlist|qualified|process/', $value)) {
            $active = 1;
            $percent = 40;
        }

        $steps = ['Submitted', 'Review', 'Interview', 'Decision', 'Complete'];

        return compact('steps', 'active', 'percent', 'terminal', 'tone');
    }

    private function joinJob($builder): void
    {
        $jobTable = $this->schema->table('jobs', false);
        $jobFk = $this->schema->field('jobPosts', 'job_id', false);
        $jobId = $this->schema->field('jobs', 'id', false);
        if ($jobTable && $jobFk && $jobId) {
            $builder->join($jobTable . ' j', "j.{$jobId} = jl.{$jobFk}", 'left', false);
        }

        $title = [];
        $code = [];
        $jobName = $this->schema->field('jobs', 'name', false);
        $jobCode = $this->schema->field('jobs', 'code', false);
        $postTitle = $this->schema->field('jobPosts', 'title', false);
        $postCode = $this->schema->field('jobPosts', 'job_code', false);
        if ($jobTable && $jobName) $title[] = "j.{$jobName}";
        if ($postTitle) $title[] = "jl.{$postTitle}";
        if ($jobTable && $jobCode) $code[] = "j.{$jobCode}";
        if ($postCode) $code[] = "jl.{$postCode}";

        $builder->select(($title ? 'COALESCE(' . implode(', ', $title) . ')' : "'Untitled position'") . ' AS title', false);
        $builder->select(($code ? 'COALESCE(' . implode(', ', $code) . ')' : 'NULL') . ' AS job_code', false);

        $location = $this->schema->field('jobPosts', 'location', false);
        $builder->select($location ? "jl.{$location} AS location" : 'NULL AS location', false);
    }

    private function initialWorkflowTransition(): ?array
    {
        $table = $this->schema->table('workflowTransitions', false);
        $featureId = $this->applicationsFeatureId();
        if (! $table || ! $featureId) {
            return null;
        }

        $feature = $this->schema->field('workflowTransitions', 'feature_id', false);
        $from = $this->schema->field('workflowTransitions', 'status_from', false);
        $to = $this->schema->field('workflowTransitions', 'status_to', false);
        if (! $feature || ! $from || ! $to) {
            return null;
        }

        $builder = $this->schema->db()->table($table)
            ->select($to . ' AS status_id_to')
            ->where($feature, $featureId)
            ->groupStart()->where($from, null)->orWhere($from, 0)->groupEnd();
        $grace = $this->schema->field('workflowTransitions', 'grace_period', false);
        $builder->select($grace ? $grace . ' AS grace_period' : 'NULL AS grace_period', false);
        $active = $this->schema->field('workflowTransitions', 'active', false);
        if ($active) {
            $builder->where($active, 1);
        }
        $deleted = $this->schema->field('workflowTransitions', 'deleted_at', false);
        if ($deleted) {
            $builder->where($deleted, null);
        }

        return $builder->orderBy('id', 'ASC')->get()->getRowArray() ?: null;
    }

    private function statusIdByNames(array $names): ?int
    {
        $table = $this->schema->table('statuses', false);
        $id = $this->schema->field('statuses', 'id', false);
        $name = $this->schema->field('statuses', 'name', false);
        $code = $this->schema->field('statuses', 'code', false);
        if (! $table || ! $id || (! $name && ! $code)) {
            return null;
        }

        $builder = $this->schema->db()->table($table)->select($id)->groupStart();
        $hasCondition = false;

        if ($name) {
            $builder->whereIn($name, $names);
            $hasCondition = true;
        }
        if ($code) {
            $codes = array_map(
                static fn (string $item): string => strtoupper(str_replace(' ', '_', $item)),
                $names
            );
            $hasCondition ? $builder->orWhereIn($code, $codes) : $builder->whereIn($code, $codes);
        }

        $row = $builder->groupEnd()->orderBy($id, 'ASC')->get()->getRowArray();
        return $row ? (int) $row[$id] : null;
    }

    private function statusName(int $statusId): ?string
    {
        $table = $this->schema->table('statuses', false);
        $id = $this->schema->field('statuses', 'id', false);
        $name = $this->schema->field('statuses', 'name', false);
        if (! $table || ! $id || ! $name || $statusId <= 0) {
            return null;
        }

        $row = $this->schema->db()->table($table)->select($name)->where($id, $statusId)->get()->getRowArray();
        return $row ? trim((string) $row[$name]) : null;
    }

    private function applicationsFeatureId(): ?int
    {
        if ($this->applicationsFeatureId !== null) {
            return $this->applicationsFeatureId > 0 ? $this->applicationsFeatureId : null;
        }

        $table = $this->schema->table('features', false);
        $id = $this->schema->field('features', 'id', false);
        $code = $this->schema->field('features', 'code', false);
        if (! $table || ! $id || ! $code) {
            $this->applicationsFeatureId = 0;
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
        $this->applicationsFeatureId = $row ? (int) $row[$id] : 0;

        return $this->applicationsFeatureId > 0 ? $this->applicationsFeatureId : null;
    }

    private function dueAt(mixed $gracePeriod): ?string
    {
        if ($gracePeriod === null || $gracePeriod === '' || ! is_numeric($gracePeriod)) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . max(0, (int) $gracePeriod) . ' days'));
    }

    private function addHistory(
        int $applicationId,
        ?int $fromStatusId,
        ?int $statusId,
        string $statusName,
        string $remarks,
        int $applicantId,
        ?string $applicantInput,
        ?string $dueAt
    ): void {
        $table = $this->schema->table('applicationHistories', false);
        $applicationField = $this->schema->field('applicationHistories', 'application_id', false);
        if (! $table || ! $applicationField) {
            return;
        }

        $payload = [$applicationField => $applicationId];
        foreach ([
            'feature_id' => $this->applicationsFeatureId(),
            'status_id_from' => $fromStatusId,
            'status_id' => $statusId,
            'status_text' => $statusName,
            'remarks' => $remarks,
            'actor_type' => 'applicant',
            'actor_id' => $applicantId,
            'applicant_input' => $applicantInput,
            'due_at' => $dueAt,
        ] as $key => $value) {
            $field = $this->schema->field('applicationHistories', $key, false);
            if ($field) {
                $payload[$field] = $value;
            }
        }

        $payload += $this->schema->nowPayload('applicationHistories');
        $this->schema->db()->table($table)->insert($payload);
    }

    private function createApplicantNotification(int $applicantId, string $subject, string $message): void
    {
        $table = $this->schema->table('notifications', false);
        if (! $table) {
            return;
        }

        $payload = [];
        foreach ([
            'applicant_id' => $applicantId,
            'subject' => $subject,
            'message' => $message,
            'read' => 0,
        ] as $key => $value) {
            $field = $this->schema->field('notifications', $key, false);
            if ($field) {
                $payload[$field] = $value;
            }
        }
        $payload += $this->schema->nowPayload('notifications');
        if ($payload !== []) {
            $this->schema->db()->table($table)->insert($payload);
        }
    }

    private function fallbackApplicantStatusChange(
        array $application,
        int $applicantId,
        int $statusId,
        string $statusName,
        ?string $input
    ): void {
        $applicationId = (int) $application['id'];
        $payload = [];
        $statusIdField = $this->schema->field('applications', 'status_id', false);
        if ($statusIdField) $payload[$statusIdField] = $statusId;
        $statusTextField = $this->schema->field('applications', 'status_text', false);
        if ($statusTextField) $payload[$statusTextField] = $statusName;
        $withdrawnAt = $this->schema->field('applications', 'withdrawn_at', false);
        if ($withdrawnAt) $payload[$withdrawnAt] = date('Y-m-d H:i:s');
        $reasonField = $this->schema->field('applications', 'withdrawal_reason', false);
        if ($reasonField) $payload[$reasonField] = trim((string) $input) ?: null;
        $payload += $this->schema->nowPayload('applications', true);

        $db = $this->schema->db();
        $db->transStart();
        $db->table($this->schema->table('applications'))
            ->where($this->schema->field('applications', 'id'), $applicationId)
            ->where($this->schema->field('applications', 'applicant_id'), $applicantId)
            ->update($payload);
        $this->addHistory(
            $applicationId,
            isset($application['status_id']) ? (int) $application['status_id'] : null,
            $statusId,
            $statusName,
            'Application withdrawn by applicant.',
            $applicantId,
            trim((string) $input) ?: null,
            null
        );
        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException('Unable to withdraw the application.');
        }

        $applicant = (new ApplicantRepository($this->schema))->normalized(
            (new ApplicantRepository($this->schema))->find($applicantId) ?? []
        );
        (new AdminNotificationRepository($this->schema))->notifyApplicationAction(
            $application,
            $applicant,
            (string) ($application['status_name'] ?? 'Current status'),
            $statusName,
            trim((string) $input) ?: null
        );
    }
}
