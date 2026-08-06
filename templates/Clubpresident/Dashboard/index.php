<?php
/**
 * Club president panel — Dashboard.
 *
 * @var \App\View\AppView $this
 * @var int $clubId
 * @var int $pendingApplicantsCount
 */
$this->assign('title', __('Dashboard'));

$clubId = (int)($clubId ?? 0);
$pendingApplicantsCount = (int)($pendingApplicantsCount ?? 0);
$membersUrl = ['prefix' => 'Clubpresident', 'controller' => 'Members', 'action' => 'index'];

$cards = [];
if ($clubId > 0) {
	$cards[] = [
		'title' => __('Members'),
		'text' => __('Open your club members list: review pending applicants, approve or reject applications, and record club membership fee payments.'),
		'url' => $membersUrl,
		'button' => __('Go to Members'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-users',
	];
}
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
