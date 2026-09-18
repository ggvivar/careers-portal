<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$applicantActions = is_array($application['applicant_actions'] ?? null)
    ? $application['applicant_actions']
    : [];
$hasWorkflowWithdraw = false;
foreach ($applicantActions as $candidateAction) {
    if (stripos((string) ($candidateAction['status_name'] ?? ''), 'withdraw') !== false) {
        $hasWorkflowWithdraw = true;
        break;
    }
}
?>
<div class="row justify-content-center">
  <div class="col-xl-9">
    <div class="card mb-4">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between gap-3">
          <div>
            <div class="small text-muted"><?= esc($application['job_code'] ?: '') ?></div>
            <h1 class="section-title h2 mb-0"><?= esc($application['title']) ?></h1>
          </div>
          <span class="badge badge-status align-self-start"><?= esc($application['status_name']) ?></span>
        </div>
        <dl class="row mt-4 mb-0">
          <dt class="col-sm-4">Location</dt>
          <dd class="col-sm-8"><?= esc($application['location'] ?: '—') ?></dd>
          <dt class="col-sm-4">Applied</dt>
          <dd class="col-sm-8">
            <?= $application['applied_at'] ? esc(date('M d, Y h:i A', strtotime($application['applied_at']))) : '—' ?>
          </dd>
        </dl>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="h5 fw-bold mb-0">Application progress</h2>
          <strong><?= (int) $application['progress']['percent'] ?>%</strong>
        </div>
        <div class="progress mb-4" style="height:9px">
          <div class="progress-bar bg-<?= esc($application['progress']['tone']) ?>"
               style="width:<?= (int) $application['progress']['percent'] ?>%"></div>
        </div>
        <div class="row g-2 text-center small">
          <?php foreach ($application['progress']['steps'] as $index => $step): ?>
            <div class="col">
              <div class="rounded-3 p-2 <?= $index <= $application['progress']['active'] ? 'bg-light fw-semibold text-dark' : 'text-muted' ?>">
                <i class="bi <?= $index < $application['progress']['active']
                    ? 'bi-check-circle-fill text-success'
                    : ($index === $application['progress']['active'] ? 'bi-circle-fill text-primary' : 'bi-circle') ?> d-block mb-1"></i>
                <?= esc($step) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="text-muted small mt-4 mb-0">Progress is based on the latest status provided by the Recruitment Team.</p>
      </div>
    </div>

    <?php if ($applicantActions): ?>
      <div class="card mb-4 border-primary-subtle">
        <div class="card-body p-4 p-lg-5">
          <div class="d-flex align-items-start gap-3 mb-4">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
              <i class="bi bi-person-check fs-5"></i>
            </div>
            <div>
              <h2 class="h5 fw-bold mb-1">Response required</h2>
              <p class="text-muted mb-0">Choose one of the available actions for your application.</p>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($applicantActions as $index => $action): ?>
              <?php
              $actionStatus = strtolower((string) ($action['status_name'] ?? ''));
              $isNegative = str_contains($actionStatus, 'declin') || str_contains($actionStatus, 'withdraw');
              $buttonClass = $isNegative ? 'btn-outline-danger' : 'btn-primary';
              $modalId = 'applicantActionModal' . (int) ($action['transition_id'] ?? $index);
              ?>
              <button type="button" class="btn <?= esc($buttonClass) ?>" data-bs-toggle="modal" data-bs-target="#<?= esc($modalId) ?>">
                <?= esc($action['applicant_action_label']) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header bg-white border-0 p-4 pb-0">
        <h2 class="h5 fw-bold mb-0">Status history</h2>
      </div>
      <div class="card-body p-4">
        <?php if (! $application['history']): ?>
          <p class="text-muted">No additional status history is available yet.</p>
        <?php else: ?>
          <div class="vstack gap-3">
            <?php foreach ($application['history'] as $history): ?>
              <div class="border-start border-3 ps-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <div class="fw-semibold"><?= esc($history['status_name'] ?: 'Updated') ?></div>
                  <?php if (($history['actor_type'] ?? '') === 'applicant'): ?>
                    <span class="badge text-bg-light border">Your response</span>
                  <?php endif; ?>
                </div>
                <?php if ($history['remarks']): ?>
                  <div class="text-muted small"><?= esc($history['remarks']) ?></div>
                <?php endif; ?>
                <?php if (trim((string) ($history['applicant_input'] ?? '')) !== ''): ?>
                  <div class="small mt-2 p-3 rounded-3 bg-light border">
                    <strong>Your input:</strong><br>
                    <?= nl2br(esc((string) $history['applicant_input'])) ?>
                  </div>
                <?php endif; ?>
                <?php if ($history['created_at']): ?>
                  <div class="text-muted small mt-1"><?= esc(date('M d, Y h:i A', strtotime($history['created_at']))) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
          <a href="<?= site_url('applications') ?>" class="btn btn-light border">Back to applications</a>
          <?php if ($application['can_withdraw'] && ! $hasWorkflowWithdraw): ?>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#withdrawApplicationModal">
              <i class="bi bi-x-circle me-1"></i>Withdraw application
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php foreach ($applicantActions as $index => $action): ?>
  <?php
  $actionStatus = strtolower((string) ($action['status_name'] ?? ''));
  $isNegative = str_contains($actionStatus, 'declin') || str_contains($actionStatus, 'withdraw');
  $modalId = 'applicantActionModal' . (int) ($action['transition_id'] ?? $index);
  $inputType = (string) ($action['applicant_input_type'] ?? 'none');
  $inputRequired = (int) ($action['applicant_input_required'] ?? 0) === 1
      || (int) ($action['require_remarks'] ?? 0) === 1;
  $inputLabel = trim((string) ($action['applicant_input_label'] ?? '')) ?: 'Your response';
  ?>
  <div class="modal fade" id="<?= esc($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 rounded-4">
        <form method="post" action="<?= site_url('applications/' . $application['id'] . '/respond') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="status_id" value="<?= (int) $action['status_id_to'] ?>">
          <div class="modal-header">
            <h2 class="modal-title h5"><?= esc($action['applicant_action_label']) ?></h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <?php if (trim((string) ($action['applicant_prompt'] ?? '')) !== ''): ?>
              <p><?= esc((string) $action['applicant_prompt']) ?></p>
            <?php else: ?>
              <p>Please confirm this application action.</p>
            <?php endif; ?>

            <?php if ($inputType === 'text'): ?>
              <label class="form-label" for="input_<?= esc($modalId) ?>">
                <?= esc($inputLabel) ?><?= $inputRequired ? ' *' : '' ?>
              </label>
              <input type="text" class="form-control" id="input_<?= esc($modalId) ?>"
                     name="applicant_input" maxlength="2000" <?= $inputRequired ? 'required' : '' ?>>
            <?php elseif ($inputType === 'textarea'): ?>
              <label class="form-label" for="input_<?= esc($modalId) ?>">
                <?= esc($inputLabel) ?><?= $inputRequired ? ' *' : '' ?>
              </label>
              <textarea class="form-control" id="input_<?= esc($modalId) ?>" name="applicant_input"
                        rows="4" maxlength="2000" <?= $inputRequired ? 'required' : '' ?>></textarea>
            <?php endif; ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
            <button class="btn <?= $isNegative ? 'btn-danger' : 'btn-primary' ?>">
              Confirm <?= esc($action['applicant_action_label']) ?>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php if ($application['can_withdraw'] && ! $hasWorkflowWithdraw): ?>
  <div class="modal fade" id="withdrawApplicationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 rounded-4">
        <form method="post" action="<?= site_url('applications/' . $application['id'] . '/withdraw') ?>">
          <?= csrf_field() ?>
          <div class="modal-header">
            <h2 class="modal-title h5">Withdraw application?</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>This action will notify the Recruitment Team that you no longer want to continue with this position.</p>
            <label class="form-label">Reason <span class="text-muted">(optional)</span></label>
            <textarea name="withdrawal_reason" class="form-control" rows="4" maxlength="500"
                      placeholder="Share a brief reason for withdrawing."></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Keep application</button>
            <button class="btn btn-danger">Confirm withdrawal</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>
<?= $this->endSection() ?>
