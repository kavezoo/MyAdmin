<?php
/**
 * Admin — competition applicant view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CompetitionsUser $app
 */
use App\Auth\MembershipProfile;
use App\Utility\CompetitionApplication;

$userEntity = $app->user ?? null;
$name = $userEntity ? MembershipProfile::displayName($userEntity) : (string)$app->user_id;
$competitionViewUrl = [
	'prefix' => 'Admin',
	'controller' => 'Competitions',
	'action' => 'view',
	$app->competition_id,
];
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-user-plus"></i> <?= __('Application details') ?></h3>
					<?= h((string)($app->competition->name ?? '')) ?> — <?= h($name) ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($competitionViewUrl) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$app->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Member') ?></dt><dd><?= h($name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Email') ?></dt><dd><?= h((string)($userEntity->email ?? '')) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Status') ?></dt><dd><?= h(CompetitionApplication::statusLabel((string)$app->status)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Club') ?></dt><dd><?= h((string)($app->competitions_club->club->name ?? '—')) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Team') ?></dt><dd><?= h((string)($app->competitions_club->subclub->name ?? '—')) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Lunch') ?></dt><dd><?= h(\App\Utility\LocaleNumberParser::format($app->lunch_for_the_attendant, decimals: 0)) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Comment') ?></dt><dd><?= h((string)($app->comment ?? '')) ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $app->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
