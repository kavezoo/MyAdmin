<?php
/**
 * Admin — competition sub-team edit (visible / pos only).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsClub $team
 * @var string $teamName
 */
$this->Html->css(['pages/form'], ['block' => true]);
$this->Html->script(['/plugins/inputmask/jquery.inputmask.min', 'pages/form'], ['block' => 'scriptBottom']);
$config = [
	'indexUrl' => $this->Url->build([
		'prefix' => 'Admin',
		'controller' => 'Competitions',
		'action' => 'view',
		$team->competition_id,
	]),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-pencil"></i> <?= __('Edit sub-team') ?></h3>
					<?= h($teamName) ?>
				</div>
				<div class="float-right">
					<a href="<?= h($this->Url->build($config['indexUrl'])) ?>" class="btn btn-outline-secondary" id="btn-close-form"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($team, ['id' => 'form-horizontal', 'autocomplete' => 'off']) ?>
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
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions(
								$team->pos,
								['id' => 'pos', 'label' => false]
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
						<a href="<?= h($this->Url->build($config['indexUrl'])) ?>" class="btn btn-outline-secondary ms-3">
							<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
