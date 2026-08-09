<?php
/**
 * Club president — membership applicants.
 *
 * @var \App\View\AppView $this
 * @var iterable<\CakeDC\Users\Model\Entity\User> $applicants
 * @var string $clubName
 * @var int $clubId
 * @var bool $applicantsEnabledOnly
 */
use App\Auth\MembershipProfile;
use App\Utility\AdminCountry;

$this->Html->css(['pages/index', 'pages/users_list_avatar'], ['block' => true]);
$this->Html->script('pages/clubpresident_applicants', ['block' => 'scriptBottom']);
$this->assign('title', __('Applicants'));

$clubName = (string)($clubName ?? '');
$applicantsEnabledOnly = (bool)($applicantsEnabledOnly ?? true);
$enabledFilterQuery = $this->request->getQueryParams();
unset($enabledFilterQuery['enabled_only']);
$enabledFilterQuery['page'] = '1';
?>
<div class="row">
	<div class="col-12 p-2">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-user-plus"></i> <?= __('Applicants') ?></h3>
					<?php if ($clubName !== ''): ?>
						<div class="text-muted"><?= h(__('Pending membership applications for {0}', $clubName)) ?></div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>"
						class="applicants-enabled-filter mb-0"
						id="applicants-enabled-filter">
						<?php foreach ($enabledFilterQuery as $name => $value): ?>
							<?php if (!is_scalar($value)) {
								continue;
							} ?>
							<input type="hidden" name="<?= h((string)$name) ?>" value="<?= h((string)$value) ?>">
						<?php endforeach; ?>
						<input type="hidden" name="enabled_only" value="0" id="applicants-enabled-only-off"
							<?= $applicantsEnabledOnly ? 'disabled' : '' ?>>
						<div class="form-check form-switch mb-0">
							<input type="checkbox"
								class="form-check-input"
								id="applicants-enabled-only"
								name="enabled_only"
								value="1"
								<?= $applicantsEnabledOnly ? 'checked' : '' ?>
								onchange="document.getElementById('applicants-enabled-only-off').disabled = this.checked; this.form.submit();">
							<label class="form-check-label text-nowrap" for="applicants-enabled-only"><?= __('Only enabled applicants') ?></label>
						</div>
					</form>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-striped mb-0 align-middle">
						<thead>
							<tr>
								<th><?= __('Name') ?></th>
								<th><?= __('Email') ?></th>
								<th><?= __('Phone') ?></th>
								<th><?= __('Country') ?></th>
								<th><?= __('Submitted') ?></th>
								<th class="text-end"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$empty = true;
							foreach ($applicants as $applicant):
								$empty = false;
								$name = MembershipProfile::displayName($applicant);
								if ($name === '') {
									$name = (string)($applicant->email ?? '');
								}
								$countryLabel = AdminCountry::label((int)($applicant->country_id ?? 0));
								$applicantId = (string)$applicant->id;
								$approveFormId = 'applicant-approve-form-' . $applicantId;
								$rejectFormId = 'applicant-reject-form-' . $applicantId;
								$isEnabled = (int)($applicant->enabled ?? 0) === 1;
								?>
								<tr<?= !$isEnabled ? ' class="text-muted"' : '' ?>>
									<td class="users-list-name-cell">
										<?= $this->element('users/list_name_cell', [
											'user' => $applicant,
											'displayName' => $name,
											'size' => 40,
										]) ?>
									</td>
									<td><?= h((string)$applicant->email) ?></td>
									<td><?= h((string)($applicant->phone ?? '')) ?></td>
									<td><?= h($countryLabel) ?></td>
									<td><?= $applicant->modified ? h(\App\Utility\LocaleDateParser::format($applicant->modified, 'datetime_short')) : '' ?></td>
									<td class="text-end">
										<?php if ($isEnabled): ?>
										<div class="d-inline-flex flex-wrap gap-1 justify-content-end">
											<?= $this->Form->create(null, [
												'url' => ['action' => 'approve', $applicantId],
												'id' => $approveFormId,
												'class' => 'd-none applicant-action-form',
											]) ?>
											<?= $this->Form->end() ?>
											<button
												type="button"
												class="btn btn-sm btn-success js-applicant-approve"
												data-form-id="<?= h($approveFormId) ?>"
											>
												<span class="btn-label"><i class="fa fa-check"></i></span><?= h(__('Approve')) ?>
											</button>

											<?= $this->Form->create(null, [
												'url' => ['action' => 'reject', $applicantId],
												'id' => $rejectFormId,
												'class' => 'd-none applicant-action-form',
											]) ?>
											<?= $this->Form->end() ?>
											<button
												type="button"
												class="btn btn-sm btn-outline-danger js-applicant-reject"
												data-form-id="<?= h($rejectFormId) ?>"
											>
												<span class="btn-label"><i class="fa fa-times"></i></span><?= h(__('Reject')) ?>
											</button>
										</div>
										<?php else: ?>
											<span class="text-muted small"><?= __('Rejected') ?></span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($empty): ?>
								<tr>
									<td colspan="6" class="text-center text-muted py-4">
										<?= __('No pending applicants.') ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
<script>
window.ClubpresidentApplicants = {
	approveTitle: <?= json_encode(__('Approve membership?'), JSON_UNESCAPED_UNICODE) ?>,
	approveText: <?= json_encode(__('Do you really want to approve this applicant as a full member?'), JSON_UNESCAPED_UNICODE) ?>,
	approveConfirm: <?= json_encode(__('Yes, approve'), JSON_UNESCAPED_UNICODE) ?>,
	rejectTitle: <?= json_encode(__('Reject application?'), JSON_UNESCAPED_UNICODE) ?>,
	rejectText: <?= json_encode(__('Do you really want to reject this application? The user will be disabled and cannot log in.'), JSON_UNESCAPED_UNICODE) ?>,
	rejectConfirm: <?= json_encode(__('Yes, reject'), JSON_UNESCAPED_UNICODE) ?>
};
</script>
