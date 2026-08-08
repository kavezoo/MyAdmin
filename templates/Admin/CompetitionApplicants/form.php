<?php
/**
 * Admin — edit competition application details.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsUser $app
 */
use App\Auth\MembershipProfile;

$this->Html->css(['pages/index', 'pages/form'], ['block' => true]);
$this->Html->script(['/plugins/inputmask/jquery.inputmask.min', 'pages/form'], ['block' => 'scriptBottom']);
$config = [
	'indexUrl' => $this->Url->build([
		'prefix' => 'Admin',
		'controller' => 'Competitions',
		'action' => 'view',
		$app->competition_id,
		'#' => 'applicants',
	]),
	'numberFormat' => \App\Utility\LocaleNumberParser::jsConfig(),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$userEntity = $app->user ?? null;
$name = $userEntity ? MembershipProfile::displayName($userEntity) : (string)$app->user_id;
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-edit"></i> <?= __('Edit application') ?></h3>
					<?= h((string)($app->competition->name ?? '')) ?> — <?= h($name) ?>
				</div>
				<div class="float-right">
					<a href="<?= h($this->Url->build($config['indexUrl'])) ?>" class="btn btn-outline-secondary" id="btn-close-form"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($app, [
					'url' => ['action' => 'edit', $app->id],
					'id' => 'form-horizontal',
				]) ?>
					<?= $this->element('competitions/application_fields', [
						'competition' => $app->competition,
						'application' => $app,
						'readonly' => false,
					]) ?>
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
