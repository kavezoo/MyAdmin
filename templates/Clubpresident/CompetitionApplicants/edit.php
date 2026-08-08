<?php
/**
 * Clubpresident — edit member competition application details.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsUser $app
 * @var bool $pastDeadline
 */
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;

$this->Html->css(['pages/index', 'pages/form'], ['block' => true]);
$this->Html->script(['/plugins/inputmask/jquery.inputmask.min', 'pages/form'], ['block' => 'scriptBottom']);
$config = ['numberFormat' => \App\Utility\LocaleNumberParser::jsConfig()];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);

$userEntity = $app->user ?? null;
$name = $userEntity ? MembershipProfile::displayName($userEntity) : '';
if ($name === '') {
	$name = (string)($userEntity->email ?? $app->user_id);
}
$pastDeadline = (bool)($pastDeadline ?? false);
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
					<a href="<?= $this->Url->build(['action' => 'index', '#' => 'competition-' . $app->competition_id]) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?php if ($pastDeadline): ?>
					<div class="alert alert-info">
						<?= __('The application deadline has passed. Only the club president may change these details (and should inform the presidency if needed).') ?>
					</div>
				<?php endif; ?>
				<p class="mb-3">
					<?= __('Status') ?>:
					<strong><?= h(CompetitionApplication::statusLabel((string)$app->status)) ?></strong>
					<?php if (!empty($app->competitions_club->subclub->name)): ?>
						— <?= h((string)$app->competitions_club->subclub->name) ?>
					<?php endif; ?>
				</p>
				<?= $this->Form->create($app, [
					'url' => ['action' => 'edit', $app->id],
					'id' => 'form-horizontal',
				]) ?>
					<?= $this->element('competitions/application_fields', [
						'competition' => $app->competition,
						'application' => $app,
						'readonly' => false,
					]) ?>
					<button type="submit" class="btn btn-success">
						<span class="btn-label"><i class="fa fa-check"></i></span><?= __('Save') ?>
					</button>
					<?= $this->Html->link(
						__('Cancel'),
						['action' => 'index', '#' => 'competition-' . $app->competition_id],
						['class' => 'btn btn-outline-secondary ms-2']
					) ?>
				<?= $this->Form->end() ?>
			</div>
		</div>
	</div>
</div>
