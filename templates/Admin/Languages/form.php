<?php
/**
 * Languages add/edit — superuser: full fields; admin: visible + pos only.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Language $language
 * @var bool $canEditFully
 */
$this->Html->css([
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
	'/plugins/inputmask/jquery.inputmask.min',
	'pages/form',
], ['block' => 'scriptBottom']);

$isEdit = !$language->isNew();
$canEditFully = (bool)$this->get('canEditFully', false);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit language') : __('New language') ?></h3>
					<?= $canEditFully
						? ($isEdit ? __('Edit the selected record.') : __('Create a new record.'))
						: __('Only visibility and position can be changed.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $language->created ? h(\App\Utility\LocaleDateParser::format($language->created, 'date')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $language->modified ? h(\App\Utility\LocaleDateParser::format($language->modified, 'date')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($language, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
				]) ?>
					<?php if ($canEditFully): ?>
						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('code', __('Code:'), ['for' => 'code']) ?>
							<div class="col-12 col-md-10 col-xl-3">
								<?= $this->Form->control('code', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'code',
									'maxlength' => 10,
									'placeholder' => 'hu_HU',
									'autofocus' => true,
								]) ?>
								<div class="form-text">
									<?= __('Locale code used for UI translations (e.g. hu_HU, en_US).') ?>
								</div>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('name', __('Name:'), ['for' => 'name']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('name', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'name',
									'placeholder' => 'Hungarian',
								]) ?>
								<div class="form-text">
									<?= __('English canonical name (also stored as Translate default locale).') ?>
								</div>
							</div>
						</div>

						<div class="form-group row mb-3">
							<?= $this->Form->adminLabel('endonim_name', __('Endonym:'), ['for' => 'endonim-name']) ?>
							<div class="col-12 col-md-10 col-xl-5">
								<?= $this->Form->control('endonim_name', [
									'label' => false,
									'class' => 'form-control',
									'id' => 'endonim-name',
									'placeholder' => 'magyar',
								]) ?>
								<div class="form-text">
									<?= __('Endonym — how the language name is written in itself (e.g. magyar, Deutsch).') ?>
								</div>
							</div>
						</div>
					<?php else: ?>
						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Code:') ?></label>
							<div class="col-12 col-md-10 col-xl-3">
								<p class="form-control-plaintext mb-0"><code><?= h($language->code) ?></code></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Name:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($language->name) ?></p>
							</div>
						</div>

						<div class="form-group row mb-3">
							<label class="col-sm-3 col-md-2 col-form-label"><?= __('Endonym:') ?></label>
							<div class="col-12 col-md-10 col-xl-5">
								<p class="form-control-plaintext mb-0"><?= h($language->endonim_name) ?></p>
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
								<?= $this->Form->adminLabel('visible', __('Visible'), [
									'for' => 'visible',
									'class' => 'form-check-label',
								]) ?>
							</div>
							<div class="form-text">
								<?= __('Visible languages appear in the login language selector.') ?>
							</div>
							<?= $this->element('admin/field_error', ['field' => 'visible']) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos']) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$language->pos,
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
