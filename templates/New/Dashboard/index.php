<?php
/**
 * New panel — Dashboard.
 *
 * @var \App\View\AppView $this
 * @var bool $needsCompletion Profile missing name / club / country
 * @var bool $waiting Profile complete — waiting for club president approval
 * @var list<string> $missingFields Human labels of missing required fields
 */
use CakeDC\Users\Utility\UsersUrl;

$needsCompletion = (bool)($needsCompletion ?? false);
$waiting = (bool)($waiting ?? false);
$missingFields = $missingFields ?? [];
$this->assign('title', __('Dashboard'));

$cards = [];
if ($waiting) {
	$cards[] = [
		'title' => __('Profile'),
		'text' => __('View the profile you submitted. Your club president will review your application.'),
		'url' => UsersUrl::actionUrl('profile'),
		'button' => __('Go to Profile'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-user',
	];
} elseif ($needsCompletion) {
	$cards[] = [
		'title' => __('Complete your profile'),
		'text' => __('Enter the missing details so you can submit your membership application.'),
		'url' => '/complete-profile',
		'button' => __('Go to Complete profile'),
		'btnClass' => 'btn-primary',
		'icon' => 'fa-id-card',
	];
}
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<?= $this->element('panel/club_fee_unpaid_alert') ?>
				<?php if ($waiting): ?>
					<div class="alert alert-info mb-3">
						<h5 class="alert-heading"><?= __('Waiting for approval') ?></h5>
						<p class="mb-0">
							<?= __('Your application is waiting for acceptance by the club president. You will receive an email when you are approved.') ?>
						</p>
					</div>
				<?php elseif ($needsCompletion): ?>
					<div class="alert alert-warning mb-3">
						<h5 class="alert-heading"><?= __('Incomplete profile') ?></h5>
						<p class="mb-2">
							<?= __('Your profile data is incomplete. Until you provide the required details, you cannot apply for membership.') ?>
						</p>
						<?php if ($missingFields !== []): ?>
							<p class="mb-0">
								<strong><?= __('Missing:') ?></strong>
								<?= h(implode(', ', $missingFields)) ?>
							</p>
						<?php endif; ?>
					</div>
				<?php else: ?>
					<p class="mb-3"><?= __('Welcome.') ?></p>
				<?php endif; ?>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>
	</div>
</div>
