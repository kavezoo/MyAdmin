<?php
/**
 * Club president panel — Dashboard (all menu destinations as cards).
 *
 * @var \App\View\AppView $this
 * @var int $clubId
 * @var int $pendingApplicantsCount
 * @var int $pendingCompetitionApplicantsCount
 */
use App\Utility\PanelNav;

$this->assign('title', __('Dashboard'));

$clubId = (int)($clubId ?? 0);
$pendingApplicantsCount = (int)($pendingApplicantsCount ?? 0);
$pendingCompetitionApplicantsCount = (int)($pendingCompetitionApplicantsCount ?? 0);
$membersUrl = ['prefix' => 'Clubpresident', 'controller' => 'Members', 'action' => 'index'];
$competitionApplicantsUrl = [
	'prefix' => 'Clubpresident',
	'controller' => 'CompetitionApplicants',
	'action' => 'index',
];
$cards = $clubId > 0 ? PanelNav::forPrefix('Clubpresident', $this->getRequest()) : [];
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<?php if ($pendingApplicantsCount > 0): ?>
			<div class="alert alert-success" role="alert">
				<h4 class="alert-heading">
					<i class="fa fa-user-plus me-1" aria-hidden="true"></i>
					<?= __('New membership applications') ?>
				</h4>
				<p>
					<?= $pendingApplicantsCount === 1
						? __('There is 1 new membership application waiting for your decision.')
						: __('There are {0} new membership applications waiting for your decision.', $pendingApplicantsCount) ?>
				</p>
				<hr>
				<p class="mb-0">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-arrow-right"></i></span>' . h(__('Review applicants')),
						$membersUrl,
						['escape' => false, 'class' => 'btn btn-success']
					) ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ($pendingCompetitionApplicantsCount > 0): ?>
			<div class="alert alert-info" role="alert">
				<h4 class="alert-heading">
					<i class="fa fa-trophy me-1" aria-hidden="true"></i>
					<?= __('New competition applications') ?>
				</h4>
				<p>
					<?= $pendingCompetitionApplicantsCount === 1
						? __('There is 1 new competition application waiting for a team assignment.')
						: __('There are {0} new competition applications waiting for a team assignment.', $pendingCompetitionApplicantsCount) ?>
				</p>
				<hr>
				<p class="mb-0">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-arrow-right"></i></span>' . h(__('Assign to teams')),
						$competitionApplicantsUrl,
						['escape' => false, 'class' => 'btn btn-info']
					) ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<?= $this->element('panel/club_fee_unpaid_alert') ?>
				<?php if ($clubId < 1): ?>
					<div class="alert alert-warning mb-0" role="alert">
						<?= __('Your account is not assigned to a club yet. Contact an administrator.') ?>
					</div>
				<?php else: ?>
					<p class="mb-3"><?= __('Welcome to the Club president panel. Choose where you want to go — each card explains the destination.') ?></p>
					<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
