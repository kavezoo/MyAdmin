<?php
/**
 * Admin users add/edit.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var array<int, string> $countryOptions
 * @var array<int, string> $clubOptions
 * @var array<int, string> $languageOptions
 * @var array<string, string> $roleOptions
 * @var array<string, string> $membershipStatusOptions
 */
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'/plugins/tempus-dominus/css/tempus-dominus.min',
	'pages/form',
], ['block' => true]);

$isEdit = !$user->isNew();
$countryOptions = $countryOptions ?? [];
$clubOptions = $clubOptions ?? [];
$languageOptions = $languageOptions ?? [];
$roleOptions = $roleOptions ?? [];
$membershipStatusOptions = $membershipStatusOptions ?? [];
$countryId = (int)($user->country_id ?? 0);
$clubFeeLabel = MembershipFee::clubFeeLabel($countryId);
$nationalFeeLabel = MembershipFee::nationalFeeLabel($countryId);

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script([
	'popper',
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/tempus-dominus/js/tempus-dominus.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
], ['block' => 'scriptBottom']);

$feeFields = [
	MembershipFee::FIELD_CLUB => $clubFeeLabel,
	MembershipFee::FIELD_NATIONAL => $nationalFeeLabel,
];
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit user') : __('New user') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $user->created ? h(\App\Utility\LocaleDateParser::format($user->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $user->modified ? h(\App\Utility\LocaleDateParser::format($user->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($user, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('email', __('Email:'), ['for' => 'email']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('email', [
								'label' => false,
								'type' => 'email',
								'class' => 'form-control',
								'id' => 'email',
								'autofocus' => true,
								'readonly' => $isEdit,
							]) ?>
							<?php if ($isEdit): ?>
								<div class="form-text"><?= __('Email cannot be changed here. Use the user profile or support flow if needed.') ?></div>
							<?php endif; ?>
						</div>
					</div>

					<?php if (!$isEdit): ?>
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('password', __('Password:'), ['for' => 'password']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('password', [
									'label' => false,
									'type' => 'password',
									'class' => 'form-control',
									'id' => 'password',
									'autocomplete' => 'new-password',
								]) ?>
							</div>
						</div>
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('password_confirm', __('Confirm password:'), ['for' => 'password-confirm']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('password_confirm', [
									'label' => false,
									'type' => 'password',
									'class' => 'form-control',
									'id' => 'password-confirm',
									'autocomplete' => 'new-password',
								]) ?>
							</div>
						</div>
					<?php endif; ?>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('first_name', __('Name:'), ['for' => 'first-name']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('first_name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'first-name',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('last_name', __('Last name:'), ['for' => 'last-name', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('last_name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'last-name',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('phone', __('Phone:'), ['for' => 'phone', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('phone', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'phone',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('role', __('Role:'), ['for' => 'role']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('role', [
								'label' => false,
								'type' => 'select',
								'options' => $roleOptions,
								'class' => 'js-example-basic-single form-select',
								'id' => 'role',
								'data-placeholder' => __('Select role...'),
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('country_id', __('Country:'), ['for' => 'country-id']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('country_id', [
								'label' => false,
								'options' => $countryOptions,
								'empty' => __('Select country...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'country-id',
								'data-placeholder' => __('Select country...'),
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('club_id', __('Club:'), ['for' => 'club-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('club_id', [
								'label' => false,
								'options' => $clubOptions,
								'class' => 'js-example-basic-single form-select',
								'id' => 'club-id',
								'data-placeholder' => __('Select club...'),
							]) ?>
							<div class="form-text"><?= __('Clubs are listed with their country. Leave empty if not applicable.') ?></div>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('language_id', __('Language:'), ['for' => 'language-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('language_id', [
								'label' => false,
								'options' => $languageOptions,
								'empty' => __('Select language...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'language-id',
								'data-placeholder' => __('Select language...'),
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('membership_status', __('Membership status:'), ['for' => 'membership-status', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('membership_status', [
								'label' => false,
								'type' => 'select',
								'options' => $membershipStatusOptions,
								'class' => 'form-select',
								'id' => 'membership-status',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel(MembershipProfile::FIELD_JOINED, __('Member since:'), ['for' => 'membership-joined-date', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-4">
							<div class="form-group date mb-0">
								<div
									class="input-group js-tempus-picker"
									id="picker-membership-joined"
									data-td-target-input="nearest"
									data-td-target-toggle="nearest"
									data-picker-type="date"
									data-picker-value="<?= h($user->membership_joined_date ? substr((string)$user->membership_joined_date, 0, 10) : '') ?>"
								>
									<?= $this->Form->control(MembershipProfile::FIELD_JOINED, [
										'label' => false,
										'type' => 'text',
										'class' => 'form-control',
										'id' => 'membership-joined-date',
										'data-td-target' => '#picker-membership-joined',
										'value' => \App\Utility\LocaleDateParser::format($user->membership_joined_date, 'date'),
										'autocomplete' => 'off',
										'error' => false,
									]) ?>
									<span class="input-group-text" data-td-target="#picker-membership-joined" data-td-toggle="datetimepicker" role="button" tabindex="0">
										<i class="fa fa-calendar" aria-hidden="true"></i>
									</span>
								</div>
								<?= $this->element('admin/field_error', ['field' => MembershipProfile::FIELD_JOINED]) ?>
							</div>
						</div>
					</div>

					<?php foreach ($feeFields as $feeField => $feeLabel):
						$feeValue = $user->get($feeField);
						$pickerId = 'picker-' . $feeField;
						$inputId = $feeField;
						$pickerValue = '';
						if ($feeValue instanceof \DateTimeInterface) {
							$pickerValue = $feeValue->format('Y-m-d');
						} elseif (is_string($feeValue) && $feeValue !== '') {
							$pickerValue = substr($feeValue, 0, 10);
						}
						?>
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel($feeField, $feeLabel . ':', ['for' => $inputId, 'required' => false]) ?>
							<div class="col-12 col-md-10 col-xl-4">
								<div class="form-group date mb-0">
									<div
										class="input-group js-tempus-picker"
										id="<?= h($pickerId) ?>"
										data-td-target-input="nearest"
										data-td-target-toggle="nearest"
										data-picker-type="date"
										data-picker-value="<?= h($pickerValue) ?>"
									>
										<?= $this->Form->control($feeField, [
											'label' => false,
											'type' => 'text',
											'class' => 'form-control',
											'id' => $inputId,
											'data-td-target' => '#' . $pickerId,
											'value' => \App\Utility\LocaleDateParser::format($feeValue, 'date'),
											'autocomplete' => 'off',
											'error' => false,
										]) ?>
										<span class="input-group-text" data-td-target="#<?= h($pickerId) ?>" data-td-toggle="datetimepicker" role="button" tabindex="0">
											<i class="fa fa-calendar" aria-hidden="true"></i>
										</span>
									</div>
									<?= $this->element('admin/field_error', ['field' => $feeField]) ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('active', ['class' => 'form-check-input', 'id' => 'active']) ?>
								<?= $this->Form->adminLabel('active', __('Active'), [
									'for' => 'active',
									'class' => 'form-check-label',
								]) ?>
							</div>
							<div class="form-text"><?= __('Inactive accounts cannot log in (CakeDC activation).') ?></div>
							<?= $this->element('admin/field_error', ['field' => 'active']) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('enabled', ['class' => 'form-check-input', 'id' => 'enabled']) ?>
								<?= $this->Form->adminLabel('enabled', __('Enabled'), [
									'for' => 'enabled',
									'class' => 'form-check-label',
								]) ?>
							</div>
							<div class="form-text"><?= __('Disabled users are locked out even when active.') ?></div>
							<?= $this->element('admin/field_error', ['field' => 'enabled']) ?>
						</div>
					</div>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success">
							<span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?>
						</button>
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3" id="btn-cancel">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
