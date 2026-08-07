<?php
/**
 * Counties add/edit.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\County $county
 * @var array<int, string> $countryOptions
 */
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);

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

$isEdit = !$county->isNew();
$countryOptions = $countryOptions ?? [];
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit county') : __('New county') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $county->created ? h(\App\Utility\LocaleDateParser::format($county->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $county->modified ? h(\App\Utility\LocaleDateParser::format($county->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($county, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
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
						<?= $this->Form->adminLabel('shortname', __('Short name:'), ['for' => 'shortname', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('shortname', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'shortname',
								'maxlength' => 100,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('capitalcity', __('Capital city:'), ['for' => 'capitalcity', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('capitalcity', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'capitalcity',
								'maxlength' => 100,
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('region', __('Region:'), ['for' => 'region', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('region', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'region',
								'maxlength' => 100,
							]) ?>
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
								$county->pos,
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
						<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3" id="btn-cancel">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
