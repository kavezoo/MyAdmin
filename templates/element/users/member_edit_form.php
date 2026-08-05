<?php
/**
 * Panel member edit form (Clubpresident / President).
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $member
 * @var string $feeField club_membership_fee_date | national_membership_fee_date
 * @var string $feeLabel
 * @var bool $showEnabled
 */
use App\Auth\MembershipProfile;
use App\Utility\MembershipFee;

$feeField = (string)($feeField ?? MembershipFee::FIELD_CLUB);
$feeLabel = (string)($feeLabel ?? __('Membership fee'));
$showEnabled = (bool)($showEnabled ?? false);
$feeValue = $member->get($feeField);
$feePickerValue = '';
if ($feeValue instanceof \DateTimeInterface) {
	$feePickerValue = $feeValue->format('Y-m-d');
} elseif (is_object($feeValue) && method_exists($feeValue, 'format')) {
	$feePickerValue = (string)$feeValue->format('Y-m-d');
} elseif (is_string($feeValue) && $feeValue !== '') {
	$feePickerValue = substr($feeValue, 0, 10);
}
$pickerId = 'picker-membership-fee-date';
$feeInputId = 'membership-fee-date';
$displayName = MembershipProfile::displayName($member);
if ($displayName === '') {
	$displayName = (string)($member->get('email') ?? '');
}

$this->Html->css([
	'/plugins/tempus-dominus/css/tempus-dominus.min',
	'pages/form',
], ['block' => true]);

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'dateFormat' => \App\Utility\LocaleDateParser::jsConfig(),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
// Tempus Dominus 6 needs Popper for correct popup placement (same as Admin Samples form).
$this->Html->script([
	'popper',
	'/plugins/tempus-dominus/js/tempus-dominus.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
], ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-pencil"></i> <?= __('Edit member') ?></h3>
					<?= h($displayName) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($member, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('first_name', __('Name:'), ['for' => 'first-name']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('first_name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'first-name',
								'autofocus' => true,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<label class="col-sm-3 col-md-2 col-form-label"><?= __('Email:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<input type="text" class="form-control" value="<?= h((string)$member->get('email')) ?>" readonly disabled>
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
						<?= $this->Form->adminLabel($feeField, $feeLabel . ':', ['for' => $feeInputId, 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-4">
							<div class="form-group date mb-0">
								<div
									class="input-group js-tempus-picker"
									id="<?= h($pickerId) ?>"
									data-td-target-input="nearest"
									data-td-target-toggle="nearest"
									data-picker-type="date"
									data-picker-value="<?= h($feePickerValue) ?>"
								>
									<?= $this->Form->control($feeField, [
										'label' => false,
										'type' => 'text',
										'class' => 'form-control',
										'id' => $feeInputId,
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

					<?php if ($showEnabled): ?>
						<div class="row">
							<div class="col-12 col-xxl-11">
								<hr class="my-4">
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
								<?= $this->element('admin/field_error', ['field' => 'enabled']) ?>
							</div>
						</div>
					<?php endif; ?>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 col-xxl-9 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success">
							<span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?>
						</button>
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
