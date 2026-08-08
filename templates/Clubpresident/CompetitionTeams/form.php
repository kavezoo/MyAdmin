<?php
/**
 * Clubpresident — competition team form (name stored on subclubs).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsClub $team
 * @var string $teamName
 * @var array<string, string> $competitionOptions
 */
$isEdit = !$team->isNew();
$competitionOptions = $competitionOptions ?? [];
$teamName = (string)($teamName ?? '');
$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/form',
], ['block' => true]);
$config = [
	'indexUrl' => $this->Url->build(['action' => 'index']),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
	'suggestedNameUrl' => $this->Url->build(['action' => 'suggestedName']),
	'isNewTeam' => !$isEdit,
	'initialSuggestedName' => $teamName,
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
$this->Html->scriptBlock(<<<'JS'
(function ($) {
	$(function () {
		var cfg = (window.MyAdmin && window.MyAdmin.config) || {};
		if (!cfg.isNewTeam || !cfg.suggestedNameUrl) {
			return;
		}
		var lastSuggested = String(cfg.initialSuggestedName || '');
		var $competition = $('#competition-id');
		var $name = $('#name');
		if (!$competition.length || !$name.length) {
			return;
		}
		function refreshSuggestedName() {
			var competitionId = String($competition.val() || '');
			if (!competitionId) {
				return;
			}
			var current = String($name.val() || '').trim();
			var mayOverwrite = current === '' || current === lastSuggested;
			if (!mayOverwrite) {
				return;
			}
			$.getJSON(cfg.suggestedNameUrl, { competition_id: competitionId })
				.done(function (data) {
					if (!data || !data.success || !data.name) {
						return;
					}
					var stillOk = String($name.val() || '').trim();
					if (stillOk !== '' && stillOk !== lastSuggested) {
						return;
					}
					lastSuggested = String(data.name);
					$name.val(lastSuggested);
					if (typeof window.MyAdmin.recaptureFormBaseline === 'function') {
						window.MyAdmin.recaptureFormBaseline();
					}
				});
		}
		$competition.on('change.select2 change', refreshSuggestedName);
	});
})(jQuery);
JS
, ['block' => 'scriptBottom']);
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-users"></i> <?= $isEdit ? __('Edit sub-team') : __('New sub-team') ?></h3>
				</div>
				<div class="float-right">
					<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary" id="btn-close-form"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($team, ['id' => 'form-horizontal', 'autocomplete' => 'off']) ?>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('competition_id', __('Competition:'), ['for' => 'competition-id']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('competition_id', [
								'label' => false,
								'type' => 'select',
								'options' => $competitionOptions,
								'empty' => __('Select competition...'),
								'class' => 'js-example-basic-single form-select',
								'id' => 'competition-id',
								'disabled' => $isEdit,
							]) ?>
							<?php if ($isEdit): ?>
								<?= $this->Form->hidden('competition_id') ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('name', __('Team name:'), ['for' => 'name']) ?>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('name', [
								'label' => false,
								'class' => 'form-control',
								'id' => 'name',
								'autofocus' => true,
								'value' => $teamName,
							]) ?>
							<div class="form-text"><?= __('Stored on subclubs. Default: club short name + serial per competition, e.g. „Ibafai PC 1” (restarts at 1 for each competition).') ?></div>
						</div>
					</div>
					<div class="row"><div class="col-12 col-xxl-11"><hr class="my-4"></div></div>
					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('visible', ['class' => 'form-check-input', 'id' => 'visible']) ?>
								<?= $this->Form->adminLabel('visible', __('Visible'), ['for' => 'visible', 'class' => 'form-check-label']) ?>
							</div>
						</div>
					</div>
					<div class="form-group row mb-3">
						<?= $this->Form->adminLabel('pos', __('Position:'), ['for' => 'pos', 'required' => false]) ?>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', \App\Utility\LocaleNumberParser::formIntegerOptions($team->pos, ['id' => 'pos', 'label' => false])) ?>
						</div>
					</div>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<div class="row">
					<div class="col-12 col-md-10 offset-md-2">
						<button type="submit" form="form-horizontal" class="btn btn-success"><span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?></button>
						<a href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3"><span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
