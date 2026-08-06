<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Club $club
 * @var int $countryId
 * @var string $countryLabel
 * @var array<string, string> $presidentOptions
 * @var string $clubPresidentId
 */
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);

$isEdit = !$club->isNew();
$presidentOptions = $presidentOptions ?? [];
$clubPresidentId = (string)($clubPresidentId ?? '');
$countryLabel = (string)($countryLabel ?? '');

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

$jsCfg = [
	'ajaxUrl' => $this->Url->build(['action' => 'userOptions']),
	'noResults' => __('No results found.'),
	'searching' => __('Search...'),
	'inputTooShort' => __('Please enter 2 or more characters'),
	'placeholder' => __('Select club president...'),
];
$this->Html->scriptBlock(
	'window.PresidentClubsForm = ' . json_encode($jsCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
	['block' => 'script']
);

$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
	'pages/president_clubs_form',
], ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit club') : __('New club') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
					<?php if ($countryLabel !== ''): ?>
						<div class="text-muted small"><?= h(__('Country: {0}', $countryLabel)) ?></div>
					<?php endif; ?>
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
						<?= $this->Form->adminLabel('club_president_id', __('Club president:'), ['for' => 'club-president-id', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-8">
							<?= $this->Form->control('club_president_id', [
								'label' => false,
								'type' => 'select',
								'options' => $presidentOptions,
								'empty' => true,
								'value' => $clubPresidentId !== '' ? $clubPresidentId : null,
								'class' => 'form-select js-club-president-select',
								'id' => 'club-president-id',
								'data-placeholder' => __('Select club president...'),
								'data-ajax-url' => $this->Url->build(['action' => 'userOptions']),
							]) ?>
							<div class="form-text"><?= __('Search by name or email (same country; not applicants with role new). Members become club president; a president or vice president keeps their role. Leave empty to clear.') ?></div>
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
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
