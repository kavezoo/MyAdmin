<?php
/**
 * Admin clubs add/edit.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Club $club
 * @var array<int, string> $countryOptions
 * @var array<int, string> $cityOptions
 */
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);

$isEdit = !$club->isNew();
$countryOptions = $countryOptions ?? [];
$cityOptions = $cityOptions ?? [];

$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
], ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit club') : __('New club') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $club->created ? h(\App\Utility\LocaleDateParser::format($club->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $club->modified ? h(\App\Utility\LocaleDateParser::format($club->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($club, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
					'type' => 'file',
				]) ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('name', __('Name:'), ['for' => 'name']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'name',
								'autofocus' => true,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('short_name', __('Short name:'), ['for' => 'short-name', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('short_name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'short-name',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('logo_file', __('Club logo:'), ['for' => 'club-logo-file', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-6">
							<?php
							$clubLogoUrl = $isEdit
								? \App\Utility\ClubLogo::publicUrlFor(
									(int)$club->id,
									is_string($club->get('logo')) ? (string)$club->get('logo') : null
								)
								: '';
							?>
							<?php if ($clubLogoUrl !== ''): ?>
								<div class="mb-2">
									<img src="<?= h($clubLogoUrl) ?>" alt="" class="img-fluid" style="max-width:120px;max-height:120px;object-fit:contain;background:transparent;">
								</div>
							<?php endif; ?>
							<?= $this->Form->control('logo_file', [
								'label' => false,
								'type' => 'file',
								'class' => 'form-control',
								'id' => 'club-logo-file',
								'accept' => 'image/png,image/jpeg,image/webp',
							]) ?>
							<div class="form-text">
								<?= __('PNG recommended (transparency kept). Used as {{club_logo}} in competition announcements.') ?>
							</div>
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
						<?= $this->Form->adminLabel('city_id', __('City:'), ['for' => 'city-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('city_id', [
								'label' => false,
								'options' => $cityOptions,
								'empty' => __('Select city...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'city-id',
								'data-placeholder' => __('Select city...'),
							]) ?>
							<div class="form-text"><?= __('Cities are listed with their country. Leave empty if not applicable.') ?></div>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('address', __('Address:'), ['for' => 'address', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('address', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'address',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('email', __('Email:'), ['for' => 'email', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('email', [
								'label' => false,
								'type' => 'email',
								'class' => 'form-control',
								'id' => 'email',
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
						<?= $this->Form->adminLabel('web', __('Website:'), ['for' => 'web', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('web', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'web',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('facebook', __('Facebook:'), ['for' => 'facebook', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('facebook', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'facebook',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('insta', __('Instagram:'), ['for' => 'insta', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('insta', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'insta',
							]) ?>
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
							<div class="form-text"><?= __('Disabled clubs cannot be selected on the membership profile.') ?></div>
							<?= $this->element('admin/field_error', ['field' => 'enabled']) ?>
						</div>
					</div>

					<div class="row">
						<div class="col-12 col-xxl-11">
							<hr class="my-4">
						</div>
					</div>

					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('visible', ['class' => 'form-check-input', 'id' => 'visible']) ?>
								<?= $this->Form->adminLabel('visible', __('Visible'), [
									'for' => 'visible',
									'class' => 'form-check-label',
								]) ?>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'visible']) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos']) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$club->pos,
								['id' => 'pos']
							)) ?>
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
