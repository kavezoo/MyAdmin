<?php
/**
 * Countries add/edit — superuser: full fields; admin: visible + pos only.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Country $country
 * @var array<int, string> $continents
 * @var bool $canEditFully
 */
$this->Html->css(['pages/form'], ['block' => true]);

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
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
], ['block' => 'scriptBottom']);

$isEdit = !$country->isNew();
$canEditFully = (bool)$this->get('canEditFully', false);
$continents = $continents ?? [];
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit country') : __('New country') ?></h3>
					<?= $canEditFully
						? ($isEdit ? __('Edit the selected record.') : __('Create a new record.'))
						: __('Only visibility and position can be changed.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $country->created ? h(\App\Utility\LocaleDateParser::format($country->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $country->modified ? h(\App\Utility\LocaleDateParser::format($country->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($country, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
					<?php if ($canEditFully): ?>
						<div class="form-group row mb-3">
							<label for="iso2" class="col-sm-3 col-md-2 col-form-label"><?= __('ISO:') ?></label>
							<div class="col-12 col-md-10 col-xl-2">
								<?= $this->Form->control('iso2', [
									'label' => false,
									'class' => 'form-control text-uppercase',
									'id' => 'iso2',
									'maxlength' => 2,
									'autofocus' => true,
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label for="name" class="col-sm-3 col-md-2 col-form-label"><?= __('Name:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('name', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'name',
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label for="locale" class="col-sm-3 col-md-2 col-form-label"><?= __('Primary locale:') ?></label>
							<div class="col-12 col-md-10 col-xl-4">
								<?= $this->Form->control('locale', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'locale',
									'placeholder' => 'en_GB',
								]) ?>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label for="continent-id" class="col-sm-3 col-md-2 col-form-label"><?= __('Continent:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('continent_id', [
									'label' => false,
									'options' => $continents,
									'empty' => __('Select continent...'),
									'class' => 'form-select',
									'id' => 'continent-id',
								]) ?>
							</div>
						</div>
					<?php else: ?>
						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('ISO:') ?></label>
							<div class="col-12 col-md-10 col-xl-2">
								<p class="form-control-plaintext mb-0"><code><?= h($country->iso2) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Name:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($country->name) ?></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Primary locale:') ?></label>
							<div class="col-12 col-md-10 col-xl-4">
								<p class="form-control-plaintext mb-0"><code><?= h($country->locale) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Continent:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($country->continent->name ?? '') ?></p>
							</div>
						</div>
					<?php endif; ?>

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
								<label class="form-check-label" for="visible"><?= __('Visible') ?></label>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'visible']) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<label for="pos" class="col-sm-3 col-md-2 col-form-label"><?= __('Position:') ?></label>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$country->pos,
								['id' => 'pos', 'autofocus' => !$canEditFully]
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
