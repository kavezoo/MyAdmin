<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\City $city
 * @var array<int, string> $samples
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
	'pages/form',
], ['block' => 'scriptBottom']);

$isEdit = !$city->isNew();
$samples = $samples ?? [];
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit city') : __('New city') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-3">
					<?php if ($isEdit): ?>
						<div class="text-end text-muted small lh-sm">
							<div><?= __('Created:') ?> <b><?= $city->created ? h($city->created->format('Y.m.d.')) : '—' ?></b></div>
							<div><?= __('Modified:') ?> <b><?= $city->modified ? h($city->modified->format('Y.m.d.')) : '—' ?></b></div>
						</div>
					<?php endif; ?>
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h('<b>' . __('Close window') . '</b>') ?>">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($city, [
					'id' => 'form-horizontal',
					'autocomplete' => 'off',
					'templates' => [
						'inputContainer' => '{{content}}',
						'inputContainerError' => '{{content}}{{error}}',
					],
				]) ?>
					<div class="form-group row mb-3">
						<label for="name" class="col-sm-3 col-md-2 col-form-label"><?= __('Name:') ?></label>
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
						<label for="samples-ids" class="col-sm-3 col-md-2 col-form-label"><?= __('Samples:') ?></label>
						<div class="col-12 col-md-10 col-xl-10 col-xxl-9">
							<?= $this->Form->control('samples._ids', [
								'label' => false,
								'options' => $samples,
								'multiple' => true,
								'class' => 'js-example-basic-multiple form-select',
								'id' => 'samples-ids',
								'data-placeholder' => __('Select samples...'),
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<label for="pos" class="col-sm-3 col-md-2 col-form-label"><?= __('Position:') ?></label>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', [
								'label' => false,
								'type' => 'text',
								'class' => 'form-control js-input-integer',
								'id' => 'pos',
								'value' => $city->pos !== null && $city->pos !== ''
									? \App\Utility\LocaleNumberParser::format($city->pos, decimals: 0)
									: '',
							]) ?>
						</div>
					</div>

					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('visible', ['class' => 'form-check-input', 'id' => 'visible']) ?>
								<label class="form-check-label" for="visible"><?= __('Visible') ?></label>
							</div>
						</div>
					</div>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
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
